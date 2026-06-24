<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    private ?array $user = null;
    private ?string $tokenHash = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function authenticate(Request $request): array
    {
        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            throw new HttpException('Token tidak valid atau belum login', 401);
        }

        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT users.id, users.name, users.email, users.role_id, users.is_active, roles.name AS role_name, roles.slug AS role_slug
             FROM user_tokens
             INNER JOIN users ON users.id = user_tokens.user_id
             INNER JOIN roles ON roles.id = users.role_id
             WHERE user_tokens.token_hash = :token_hash
               AND user_tokens.is_active = 1
               AND user_tokens.expires_at > NOW()
               AND users.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $hash]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new HttpException('Token tidak valid atau belum login', 401);
        }

        if ((int) $user['is_active'] !== 1) {
            throw new HttpException('Anda tidak memiliki akses untuk melakukan aksi ini', 403);
        }

        $this->user = $user;
        $this->tokenHash = $hash;

        return $user;
    }

    public function authorize(array $roles): void
    {
        if ($roles === []) {
            return;
        }

        $role = $this->user['role_slug'] ?? null;
        if (!in_array($role, $roles, true)) {
            throw new HttpException('Anda tidak memiliki akses untuk melakukan aksi ini', 403);
        }
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function tokenHash(): ?string
    {
        return $this->tokenHash;
    }
}
