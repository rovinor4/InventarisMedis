<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class BorrowingController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        Response::success('Data berhasil ditampilkan', $this->repository->listBorrowings());
    }

    public function store(Request $request, array $params = []): void
    {
        $data = $request->json();
        Validator::failIf([
            'medical_item_id' => Validator::required($data, 'medical_item_id', 'Alat medis wajib dipilih'),
            'borrower_name' => Validator::required($data, 'borrower_name', 'Nama peminjam tidak boleh kosong'),
            'borrower_unit' => Validator::required($data, 'borrower_unit', 'Unit peminjam tidak boleh kosong'),
            'quantity' => Validator::integerMin($data, 'quantity', 1, 'Jumlah pinjam harus lebih dari 0'),
            'borrowed_at' => Validator::required($data, 'borrowed_at', 'Tanggal pinjam wajib diisi') ?? Validator::date($data, 'borrowed_at', 'Tanggal pinjam tidak valid'),
        ]);

        $borrowing = $this->repository->createBorrowing($data, $this->userId());
        $this->repository->logActivity($this->userId(), 'create', 'borrowings', (int) $borrowing['id'], 'Peminjaman alat medis');
        Response::success('Peminjaman berhasil ditambahkan', $borrowing, 201);
    }

    public function show(Request $request, array $params): void
    {
        $borrowing = $this->repository->findBorrowing((int) $params['id']);
        if ($borrowing === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        Response::success('Data berhasil ditampilkan', $borrowing);
    }

    public function update(Request $request, array $params): void
    {
        $data = $request->json();
        Validator::failIf([
            'borrowed_at' => Validator::date($data, 'borrowed_at', 'Tanggal pinjam tidak valid'),
        ]);

        $borrowing = $this->repository->updateBorrowing((int) $params['id'], $data);
        if ($borrowing === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'update', 'borrowings', (int) $params['id'], 'Ubah peminjaman');
        Response::success('Peminjaman berhasil diperbarui', $borrowing);
    }

    public function destroy(Request $request, array $params): void
    {
        if (!$this->repository->deleteBorrowing((int) $params['id'], $this->userId())) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'delete', 'borrowings', (int) $params['id'], 'Hapus peminjaman');
        Response::success('Peminjaman berhasil dihapus');
    }
}
