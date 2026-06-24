<?php

declare(strict_types=1);

require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/InventoryRepository.php';
require __DIR__ . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$config = require __DIR__ . '/config.php';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', trim($path, '/'))));

if ($segments === []) {
    json_response([
        'success' => true,
        'message' => 'REST API inventaris medis siap digunakan.',
        'endpoints' => [
            'GET /api/alat-medis',
            'POST /api/alat-medis',
            'GET /api/alat-medis/{id}',
            'PUT /api/alat-medis/{id}',
            'DELETE /api/alat-medis/{id}',
            'POST /api/alat-medis/{id}/stok',
            'POST /api/alat-medis/{id}/pinjam',
            'POST /api/peminjaman/{id}/kembali',
            'GET /api/peminjaman',
            'GET /api/stok',
        ],
    ]);
    exit;
}

try {
    $database = new Database($config);
    $inventory = new InventoryRepository($database->pdo());
    dispatch($inventory, $segments);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'message' => $e->getMessage(),
    ], 500);
}

function dispatch(InventoryRepository $inventory, array $segments): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($segments[0] !== 'api') {
        json_response([
            'success' => false,
            'message' => 'Endpoint tidak ditemukan.',
        ], 404);
        return;
    }

    $resource = $segments[1] ?? '';
    $id = isset($segments[2]) ? (int) $segments[2] : null;
    $action = $segments[3] ?? null;

    try {
        if ($resource === 'alat-medis') {
            handleEquipmentRoutes($inventory, $method, $id, $action);
            return;
        }

        if ($resource === 'peminjaman') {
            handleBorrowRoutes($inventory, $method, $id, $action);
            return;
        }

        if ($resource === 'stok') {
            if ($method !== 'GET') {
                json_response(['success' => false, 'message' => 'Method tidak didukung.'], 405);
                return;
            }

            $equipmentId = int_or_null($_GET['equipment_id'] ?? null);
            json_response(['success' => true, 'data' => $inventory->listStockMovements($equipmentId)]);
            return;
        }

        json_response([
            'success' => false,
            'message' => 'Endpoint tidak ditemukan.',
        ], 404);
    } catch (InvalidArgumentException $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (RuntimeException $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleEquipmentRoutes(InventoryRepository $inventory, string $method, ?int $id, ?string $action): void
{
    if ($id === null) {
        if ($method === 'GET') {
            json_response(['success' => true, 'data' => $inventory->listEquipment($_GET['search'] ?? null)]);
            return;
        }

        if ($method === 'POST') {
            $data = read_json_body();
            require_fields($data, ['code', 'name']);
            json_response(['success' => true, 'data' => $inventory->createEquipment($data)], 201);
            return;
        }

        json_response(['success' => false, 'message' => 'Method tidak didukung.'], 405);
        return;
    }

    if ($action === 'stok' && $method === 'POST') {
        $data = read_json_body();
        require_fields($data, ['quantity', 'type']);
        $updated = $inventory->adjustStock(
            $id,
            (int) $data['quantity'],
            (string) $data['type'],
            $data['notes'] ?? null
        );

        if ($updated === null) {
            json_response(['success' => false, 'message' => 'Alat medis tidak ditemukan.'], 404);
            return;
        }

        json_response(['success' => true, 'data' => $updated]);
        return;
    }

    if ($action === 'pinjam' && $method === 'POST') {
        $data = read_json_body();
        require_fields($data, ['borrower_name', 'quantity']);
        json_response(['success' => true, 'data' => $inventory->borrowEquipment($id, $data)], 201);
        return;
    }

    if ($method === 'GET') {
        $equipment = $inventory->getEquipment($id);
        if ($equipment === null) {
            json_response(['success' => false, 'message' => 'Alat medis tidak ditemukan.'], 404);
            return;
        }

        json_response(['success' => true, 'data' => $equipment]);
        return;
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = read_json_body();
        $updated = $inventory->updateEquipment($id, $data);
        if ($updated === null) {
            json_response(['success' => false, 'message' => 'Alat medis tidak ditemukan.'], 404);
            return;
        }

        json_response(['success' => true, 'data' => $updated]);
        return;
    }

    if ($method === 'DELETE') {
        $deleted = $inventory->deleteEquipment($id);
        if (!$deleted) {
            json_response(['success' => false, 'message' => 'Alat medis tidak ditemukan.'], 404);
            return;
        }

        json_response(['success' => true, 'message' => 'Data alat medis berhasil dihapus.']);
        return;
    }

    json_response(['success' => false, 'message' => 'Method tidak didukung.'], 405);
}

function handleBorrowRoutes(InventoryRepository $inventory, string $method, ?int $id, ?string $action): void
{
    if ($id === null) {
        if ($method === 'GET') {
            json_response(['success' => true, 'data' => $inventory->listBorrowTransactions()]);
            return;
        }

        json_response(['success' => false, 'message' => 'Method tidak didukung.'], 405);
        return;
    }

    if ($action === 'kembali' && $method === 'POST') {
        $data = read_json_body();
        $updated = $inventory->returnBorrowing($id, $data['notes'] ?? null);
        json_response(['success' => true, 'data' => $updated]);
        return;
    }

    json_response(['success' => false, 'message' => 'Endpoint tidak ditemukan.'], 404);
}
