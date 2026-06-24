<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class MedicalItemController extends Controller
{
    private const CONDITIONS = ['baik', 'rusak', 'maintenance'];

    public function index(Request $request, array $params = []): void
    {
        Response::success('Data berhasil ditampilkan', $this->repository->listMedicalItems($request->query('search')));
    }

    public function store(Request $request, array $params = []): void
    {
        $data = $request->json();
        $errors = $this->validate($data);
        if (isset($data['code']) && $this->repository->codeExists((string) $data['code'])) {
            $errors['code'] = 'Kode alat medis sudah digunakan';
        }
        Validator::failIf($errors);

        $item = $this->repository->createMedicalItem($data);
        $this->repository->logActivity($this->userId(), 'create', 'medical_items', (int) $item['id'], 'Tambah alat medis');
        Response::success('Alat medis berhasil ditambahkan', $item, 201);
    }

    public function show(Request $request, array $params): void
    {
        $item = $this->repository->findMedicalItem((int) $params['id']);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        Response::success('Data berhasil ditampilkan', $item);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $data = $request->json();
        $errors = [];

        if (array_key_exists('stock', $data)) {
            $errors['stock'] = Validator::integerMin($data, 'stock', 0, 'Stok harus berupa angka dan tidak boleh negatif');
        }
        if (array_key_exists('condition', $data)) {
            $errors['condition'] = Validator::in($data, 'condition', self::CONDITIONS, 'Kondisi hanya boleh baik, rusak, atau maintenance');
        }
        if (array_key_exists('code', $data) && $this->repository->codeExists((string) $data['code'], $id)) {
            $errors['code'] = 'Kode alat medis sudah digunakan';
        }
        Validator::failIf($errors);

        $item = $this->repository->updateMedicalItem($id, $data);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'update', 'medical_items', $id, 'Ubah alat medis');
        Response::success('Alat medis berhasil diperbarui', $item);
    }

    public function destroy(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        if (!$this->repository->deleteMedicalItem($id)) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'delete', 'medical_items', $id, 'Hapus alat medis');
        Response::success('Alat medis berhasil dihapus');
    }

    private function validate(array $data): array
    {
        return [
            'name' => Validator::required($data, 'name', 'Nama alat medis tidak boleh kosong'),
            'code' => Validator::required($data, 'code', 'Kode alat medis tidak boleh kosong'),
            'category' => Validator::required($data, 'category', 'Kategori alat medis tidak boleh kosong'),
            'stock' => Validator::integerMin($data, 'stock', 0, 'Stok harus berupa angka dan tidak boleh negatif'),
            'unit' => Validator::required($data, 'unit', 'Satuan alat medis tidak boleh kosong'),
            'condition' => Validator::required($data, 'condition', 'Kondisi alat medis tidak boleh kosong')
                ?? Validator::in($data, 'condition', self::CONDITIONS, 'Kondisi hanya boleh baik, rusak, atau maintenance'),
        ];
    }
}
