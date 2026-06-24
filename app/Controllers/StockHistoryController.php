<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class StockHistoryController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $medicalItemId = $request->query('medical_item_id');
        Response::success('Data berhasil ditampilkan', $this->repository->listStockHistories($medicalItemId === null ? null : (int) $medicalItemId));
    }

    public function store(Request $request, array $params = []): void
    {
        $data = $request->json();
        Validator::failIf([
            'medical_item_id' => Validator::required($data, 'medical_item_id', 'Alat medis wajib dipilih'),
            'type' => Validator::required($data, 'type', 'Tipe stok wajib diisi') ?? Validator::in($data, 'type', ['in', 'out', 'adjustment'], 'Tipe stok tidak valid'),
            'quantity' => Validator::integerMin($data, 'quantity', 1, 'Jumlah stok harus lebih dari 0'),
            'condition_after' => Validator::in($data, 'condition_after', ['baik', 'rusak', 'maintenance'], 'Kondisi hanya boleh baik, rusak, atau maintenance'),
        ]);

        $history = $this->repository->createStockHistory($data, $this->userId());
        $this->repository->logActivity($this->userId(), 'create', 'stock_histories', (int) $history['id'], 'Catat stok');
        Response::success('Riwayat stok berhasil ditambahkan', $history, 201);
    }

    public function show(Request $request, array $params): void
    {
        $history = $this->repository->findStockHistory((int) $params['id']);
        if ($history === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        Response::success('Data berhasil ditampilkan', $history);
    }
}
