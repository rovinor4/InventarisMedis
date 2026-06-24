<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class AuthController extends Controller
{
    public function login(Request $request, array $params = []): void
    {
        $data = $request->json();
        Validator::failIf([
            'email' => Validator::required($data, 'email', 'Email tidak boleh kosong') ?? Validator::email($data, 'email', 'Email tidak valid'),
            'password' => Validator::required($data, 'password', 'Password tidak boleh kosong'),
        ]);

        $user = $this->repository->findUserByEmail((string) $data['email']);
        if ($user === null || (int) $user['is_active'] !== 1 || !password_verify((string) $data['password'], $user['password_hash'])) {
            $this->repository->logActivity($user['id'] ?? null, 'login_failed', 'users', $user['id'] ?? null, 'Login gagal');
            $this->logger->info('Login gagal', ['email' => $data['email']]);
            throw new HttpException('Token tidak valid atau belum login', 401);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ((int) getenv('TOKEN_TTL_HOURS') ?: 24) * 3600);
        $this->repository->createToken((int) $user['id'], hash('sha256', $token), $expiresAt);
        $this->repository->logActivity((int) $user['id'], 'login_success', 'users', (int) $user['id'], 'Login berhasil');

        unset($user['password_hash']);
        Response::success('Login berhasil', [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'user' => $user,
        ]);
    }

    public function logout(Request $request, array $params = []): void
    {
        $tokenHash = $this->auth->tokenHash();
        if ($tokenHash !== null) {
            $this->repository->deactivateToken($tokenHash);
        }

        $this->repository->logActivity($this->userId(), 'logout', 'user_tokens', null, 'Logout berhasil');
        Response::success('Logout berhasil');
    }

    public function me(Request $request, array $params = []): void
    {
        Response::success('Data user berhasil ditampilkan', $this->auth->user());
    }
}
