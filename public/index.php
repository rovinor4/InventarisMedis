<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\InventoryRepository;

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$config = require dirname(__DIR__) . '/bootstrap/app.php';
$request = new Request();
$logger = new Logger($config['app']['log_path']);

try {
    if ($request->path() === '/') {
        Response::success('REST API inventaris medis siap digunakan', [
            'endpoints' => [
                'POST /auth/login',
                'POST /auth/logout',
                'GET /auth/me',
                'GET /medical-items',
                'POST /medical-items',
                'GET /medical-items/{id}',
                'PUT /medical-items/{id}',
                'DELETE /medical-items/{id}',
                'GET /stock-histories',
                'POST /stock-histories',
                'GET /stock-histories/{id}',
                'GET /borrowings',
                'POST /borrowings',
                'GET /borrowings/{id}',
                'PUT /borrowings/{id}',
                'DELETE /borrowings/{id}',
                'GET /returns',
                'POST /returns',
                'GET /returns/{id}',
                'GET /users',
                'POST /users',
                'PUT /users/{id}',
                'DELETE /users/{id}',
            ],
        ]);
        exit;
    }

    $pdo = (new Database($config['database']))->pdo();
    $repository = new InventoryRepository($pdo);
    $auth = new Auth($pdo);
    $routes = require dirname(__DIR__) . '/routes/api.php';

    foreach ($routes as [$method, $pattern, $controllerClass, $action, $requiresAuth, $roles]) {
        $params = matchRoute($method, $pattern, $request);
        if ($params === null) {
            continue;
        }

        validateJsonHeaders($request);

        if ($requiresAuth) {
            $auth->authenticate($request);
            $auth->authorize($roles);
        }

        $controller = new $controllerClass($repository, $auth, $logger);
        $controller->{$action}($request, $params);
        exit;
    }

    Response::error('Data tidak ditemukan', 404);
} catch (HttpException $e) {
    Response::error($e->getMessage(), $e->status(), $e->errors());
} catch (Throwable $e) {
    $logger->error('Terjadi kesalahan server', ['exception' => $e::class, 'message' => $e->getMessage()]);
    Response::error('Terjadi kesalahan pada server', 500);
}

function matchRoute(string $method, string $pattern, Request $request): ?array
{
    if ($request->method() !== $method) {
        return null;
    }

    $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', static function (array $matches): string {
        return '(?P<' . $matches[1] . '>[0-9]+)';
    }, $pattern);

    if (!preg_match('#^' . $regex . '$#', $request->path(), $matches)) {
        return null;
    }

    $params = [];
    foreach ($matches as $key => $value) {
        if (!is_int($key)) {
            $params[$key] = $value;
        }
    }

    return $params;
}

function validateJsonHeaders(Request $request): void
{
    if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
        return;
    }

    $contentType = $request->header('Content-Type') ?? '';
    if (!str_contains(strtolower($contentType), 'application/json')) {
        throw new HttpException('Validasi gagal', 422, ['Content-Type' => 'Header Content-Type wajib application/json']);
    }

    $accept = $request->header('Accept');
    if ($accept !== null && !str_contains(strtolower($accept), 'application/json') && !str_contains($accept, '*/*')) {
        throw new HttpException('Validasi gagal', 422, ['Accept' => 'Header Accept wajib application/json']);
    }
}
