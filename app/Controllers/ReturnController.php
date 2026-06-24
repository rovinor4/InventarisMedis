<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class ReturnController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        Response::success('Data berhasil ditampilkan', $this->repository->listReturns());
    }

    public function store(Request $request, array $params = []): void
    {
        $data = $request->json();
        Validator::failIf([
            'borrowing_id' => Validator::required($data, 'borrowing_id', 'Data peminjaman wajib dipilih'),
            'quantity' => Validator::integerMin($data, 'quantity', 1, 'Jumlah kembali harus lebih dari 0'),
            'condition_after' => Validator::required($data, 'condition_after', 'Kondisi setelah kembali wajib dicatat')
                ?? Validator::in($data, 'condition_after', ['baik', 'rusak', 'maintenance'], 'Kondisi hanya boleh baik, rusak, atau maintenance'),
            'returned_at' => Validator::date($data, 'returned_at', 'Tanggal kembali tidak valid'),
        ]);

        $return = $this->repository->createReturn($data, $this->userId());
        $this->repository->logActivity($this->userId(), 'create', 'returns', (int) $return['id'], 'Pengembalian alat medis');
        Response::success('Pengembalian berhasil ditambahkan', $return, 201);
    }

    public function show(Request $request, array $params): void
    {
        $return = $this->repository->findReturn((int) $params['id']);
        if ($return === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        Response::success('Data berhasil ditampilkan', $return);
    }
}
