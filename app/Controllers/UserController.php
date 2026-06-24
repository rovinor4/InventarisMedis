<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class UserController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        Response::success('Data berhasil ditampilkan', $this->repository->listUsers());
    }

    public function store(Request $request, array $params = []): void
    {
        $data = $request->json();
        $errors = $this->validateUser($data);
        if (isset($data['email']) && $this->repository->emailExists((string) $data['email'])) {
            $errors['email'] = 'Email sudah digunakan';
        }
        Validator::failIf($errors);

        $user = $this->repository->createUser($data);
        $this->repository->logActivity($this->userId(), 'create', 'users', (int) $user['id'], 'Tambah user');
        Response::success('User berhasil ditambahkan', $user, 201);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $data = $request->json();
        $errors = [];

        if (array_key_exists('email', $data)) {
            $errors['email'] = Validator::email($data, 'email', 'Email tidak valid');
            if ($this->repository->emailExists((string) $data['email'], $id)) {
                $errors['email'] = 'Email sudah digunakan';
            }
        }
        if (array_key_exists('password', $data) && strlen((string) $data['password']) < 8) {
            $errors['password'] = 'Password minimal 8 karakter';
        }
        if (array_key_exists('role_id', $data) && !$this->repository->roleExists((int) $data['role_id'])) {
            $errors['role_id'] = 'Role tidak ditemukan';
        }
        Validator::failIf($errors);

        $user = $this->repository->updateUser($id, $data);
        if ($user === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'update', 'users', $id, 'Ubah user');
        Response::success('User berhasil diperbarui', $user);
    }

    public function destroy(Request $request, array $params): void
    {
        if (!$this->repository->deleteUser((int) $params['id'])) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->repository->logActivity($this->userId(), 'delete', 'users', (int) $params['id'], 'Hapus user');
        Response::success('User berhasil dihapus');
    }

    private function validateUser(array $data): array
    {
        $errors = [
            'name' => Validator::required($data, 'name', 'Nama user tidak boleh kosong'),
            'email' => Validator::required($data, 'email', 'Email tidak boleh kosong') ?? Validator::email($data, 'email', 'Email tidak valid'),
            'password' => Validator::required($data, 'password', 'Password tidak boleh kosong'),
            'role_id' => Validator::required($data, 'role_id', 'Role wajib dipilih'),
        ];

        if (isset($data['password']) && strlen((string) $data['password']) < 8) {
            $errors['password'] = 'Password minimal 8 karakter';
        }
        if (isset($data['role_id']) && !$this->repository->roleExists((int) $data['role_id'])) {
            $errors['role_id'] = 'Role tidak ditemukan';
        }

        return $errors;
    }
}
