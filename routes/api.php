<?php

declare(strict_types=1);

use App\Controllers\ActivityLogController;
use App\Controllers\AuthController;
use App\Controllers\BorrowingController;
use App\Controllers\MedicalItemController;
use App\Controllers\ReturnController;
use App\Controllers\StockHistoryController;
use App\Controllers\UserController;

$superAdmin = ['super_admin'];
$inventory = ['super_admin', 'admin_inventaris'];
$warehouse = ['super_admin', 'admin_inventaris', 'petugas_gudang'];
$medical = ['super_admin', 'admin_inventaris', 'petugas_medis'];
$readOnly = ['super_admin', 'admin_inventaris', 'petugas_gudang', 'petugas_medis', 'auditor'];

return [
    ['POST', '/auth/login', AuthController::class, 'login', false, []],
    ['POST', '/auth/logout', AuthController::class, 'logout', true, $readOnly],
    ['GET', '/auth/me', AuthController::class, 'me', true, $readOnly],

    ['GET', '/users', UserController::class, 'index', true, $superAdmin],
    ['POST', '/users', UserController::class, 'store', true, $superAdmin],
    ['PUT', '/users/{id}', UserController::class, 'update', true, $superAdmin],
    ['DELETE', '/users/{id}', UserController::class, 'destroy', true, $superAdmin],

    ['GET', '/medical-items', MedicalItemController::class, 'index', true, $readOnly],
    ['POST', '/medical-items', MedicalItemController::class, 'store', true, $inventory],
    ['GET', '/medical-items/{id}', MedicalItemController::class, 'show', true, $readOnly],
    ['PUT', '/medical-items/{id}', MedicalItemController::class, 'update', true, $inventory],
    ['DELETE', '/medical-items/{id}', MedicalItemController::class, 'destroy', true, $inventory],

    ['GET', '/stock-histories', StockHistoryController::class, 'index', true, $readOnly],
    ['POST', '/stock-histories', StockHistoryController::class, 'store', true, $warehouse],
    ['GET', '/stock-histories/{id}', StockHistoryController::class, 'show', true, $readOnly],

    ['GET', '/borrowings', BorrowingController::class, 'index', true, $readOnly],
    ['POST', '/borrowings', BorrowingController::class, 'store', true, $medical],
    ['GET', '/borrowings/{id}', BorrowingController::class, 'show', true, $readOnly],
    ['PUT', '/borrowings/{id}', BorrowingController::class, 'update', true, $inventory],
    ['DELETE', '/borrowings/{id}', BorrowingController::class, 'destroy', true, $inventory],

    ['GET', '/returns', ReturnController::class, 'index', true, $readOnly],
    ['POST', '/returns', ReturnController::class, 'store', true, $warehouse],
    ['GET', '/returns/{id}', ReturnController::class, 'show', true, $readOnly],

    ['GET', '/activity-logs', ActivityLogController::class, 'index', true, ['super_admin', 'auditor']],
];
