<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\HttpException;
use PDO;

final class InventoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function roles(): array
    {
        return $this->pdo->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll();
    }

    public function roleExists(int $roleId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $roleId]);

        return (bool) $stmt->fetch();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email AND deleted_at IS NULL';
        $params = ['email' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM medical_items WHERE code = :code AND deleted_at IS NULL';
        $params = ['code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    public function listUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT users.id, users.name, users.email, users.role_id, users.is_active, roles.name AS role_name, roles.slug AS role_slug,
                    users.created_at, users.updated_at
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.deleted_at IS NULL
             ORDER BY users.created_at DESC, users.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.*, roles.name AS role_name, roles.slug AS role_slug
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.email = :email AND users.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findUser(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.id, users.name, users.email, users.role_id, users.is_active, roles.name AS role_name, roles.slug AS role_slug,
                    users.created_at, users.updated_at
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id AND users.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createUser(array $data): array
    {
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, is_active, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :role_id, :is_active, :created_at, :updated_at)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id' => (int) $data['role_id'],
            'is_active' => (int) ($data['is_active'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findUser((int) $this->pdo->lastInsertId());
    }

    public function updateUser(int $id, array $data): ?array
    {
        $user = $this->findUser($id);
        if ($user === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET name = :name, email = :email, password_hash = :password_hash, role_id = :role_id, is_active = :is_active, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'name' => $data['name'] ?? $user['name'],
            'email' => strtolower($data['email'] ?? $user['email']),
            'password_hash' => isset($data['password']) && $data['password'] !== ''
                ? password_hash($data['password'], PASSWORD_DEFAULT)
                : $this->currentPasswordHash($id),
            'role_id' => (int) ($data['role_id'] ?? $user['role_id']),
            'is_active' => (int) ($data['is_active'] ?? $user['is_active']),
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return $this->findUser($id);
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET deleted_at = :deleted_at, is_active = 0, updated_at = :updated_at WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['deleted_at' => $this->now(), 'updated_at' => $this->now(), 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function createToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_tokens (user_id, token_hash, created_at, expires_at, is_active, updated_at)
             VALUES (:user_id, :token_hash, :created_at, :expires_at, 1, :updated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'updated_at' => $now,
        ]);
    }

    public function deactivateToken(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE user_tokens SET is_active = 0, updated_at = :updated_at WHERE token_hash = :token_hash'
        );
        $stmt->execute(['updated_at' => $this->now(), 'token_hash' => $tokenHash]);
    }

    public function listMedicalItems(?string $search = null): array
    {
        $sql = 'SELECT * FROM medical_items WHERE deleted_at IS NULL';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (name LIKE :search OR code LIKE :search OR category LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $stmt = $this->pdo->prepare($sql . ' ORDER BY created_at DESC, id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findMedicalItem(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medical_items WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createMedicalItem(array $data): array
    {
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'INSERT INTO medical_items (name, code, category, description, stock, unit, `condition`, created_at, updated_at)
             VALUES (:name, :code, :category, :description, :stock, :unit, :condition, :created_at, :updated_at)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'code' => $data['code'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'stock' => (int) $data['stock'],
            'unit' => $data['unit'],
            'condition' => $data['condition'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findMedicalItem((int) $this->pdo->lastInsertId());
    }

    public function updateMedicalItem(int $id, array $data): ?array
    {
        $item = $this->findMedicalItem($id);
        if ($item === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE medical_items
             SET name = :name, code = :code, category = :category, description = :description, stock = :stock,
                 unit = :unit, `condition` = :condition, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'name' => $data['name'] ?? $item['name'],
            'code' => $data['code'] ?? $item['code'],
            'category' => $data['category'] ?? $item['category'],
            'description' => array_key_exists('description', $data) ? $data['description'] : $item['description'],
            'stock' => (int) ($data['stock'] ?? $item['stock']),
            'unit' => $data['unit'] ?? $item['unit'],
            'condition' => $data['condition'] ?? $item['condition'],
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return $this->findMedicalItem($id);
    }

    public function deleteMedicalItem(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE medical_items SET deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['deleted_at' => $this->now(), 'updated_at' => $this->now(), 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function listStockHistories(?int $medicalItemId = null): array
    {
        $sql = 'SELECT stock_histories.*, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
                FROM stock_histories
                INNER JOIN medical_items ON medical_items.id = stock_histories.medical_item_id';
        $params = [];
        if ($medicalItemId !== null) {
            $sql .= ' WHERE stock_histories.medical_item_id = :medical_item_id';
            $params['medical_item_id'] = $medicalItemId;
        }

        $stmt = $this->pdo->prepare($sql . ' ORDER BY stock_histories.created_at DESC, stock_histories.id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findStockHistory(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT stock_histories.*, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
             FROM stock_histories
             INNER JOIN medical_items ON medical_items.id = stock_histories.medical_item_id
             WHERE stock_histories.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createStockHistory(array $data, ?int $userId): array
    {
        $item = $this->findMedicalItem((int) $data['medical_item_id']);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $quantity = (int) $data['quantity'];
        $type = $data['type'];
        $newStock = match ($type) {
            'in', 'adjustment' => (int) $item['stock'] + $quantity,
            'out' => (int) $item['stock'] - $quantity,
            default => throw new HttpException('Validasi gagal', 422, ['type' => 'Tipe stok tidak valid']),
        };

        if ($newStock < 0) {
            throw new HttpException('Validasi gagal', 422, ['quantity' => 'Stok tidak mencukupi']);
        }

        $this->pdo->beginTransaction();
        try {
            $historyId = $this->insertStockHistory($data, $quantity, $newStock, $userId);
            $this->setMedicalItemStock((int) $item['id'], $newStock, $data['condition_after'] ?? null);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->findStockHistory($historyId);
    }

    public function listBorrowings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT borrowings.*, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
             FROM borrowings
             INNER JOIN medical_items ON medical_items.id = borrowings.medical_item_id
             WHERE borrowings.deleted_at IS NULL
             ORDER BY borrowings.created_at DESC, borrowings.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function findBorrowing(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT borrowings.*, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
             FROM borrowings
             INNER JOIN medical_items ON medical_items.id = borrowings.medical_item_id
             WHERE borrowings.id = :id AND borrowings.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createBorrowing(array $data, ?int $userId): array
    {
        $item = $this->findMedicalItem((int) $data['medical_item_id']);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $quantity = (int) $data['quantity'];
        if ((int) $item['stock'] < $quantity) {
            throw new HttpException('Validasi gagal', 422, ['quantity' => 'Jumlah peminjaman melebihi stok tersedia']);
        }

        $this->pdo->beginTransaction();
        try {
            $now = $this->now();
            $stmt = $this->pdo->prepare(
                'INSERT INTO borrowings (medical_item_id, borrower_name, borrower_unit, quantity, borrowed_at, returned_at, status, notes, created_by, created_at, updated_at)
                 VALUES (:medical_item_id, :borrower_name, :borrower_unit, :quantity, :borrowed_at, NULL, "borrowed", :notes, :created_by, :created_at, :updated_at)'
            );
            $stmt->execute([
                'medical_item_id' => (int) $data['medical_item_id'],
                'borrower_name' => $data['borrower_name'],
                'borrower_unit' => $data['borrower_unit'],
                'quantity' => $quantity,
                'borrowed_at' => $data['borrowed_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $borrowingId = (int) $this->pdo->lastInsertId();
            $newStock = (int) $item['stock'] - $quantity;
            $this->insertStockHistory([
                'medical_item_id' => (int) $data['medical_item_id'],
                'type' => 'out',
                'description' => 'Peminjaman alat medis #' . $borrowingId,
                'reference_type' => 'borrowing',
                'reference_id' => $borrowingId,
            ], $quantity, $newStock, $userId);
            $this->setMedicalItemStock((int) $item['id'], $newStock);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->findBorrowing($borrowingId);
    }

    public function updateBorrowing(int $id, array $data): ?array
    {
        $borrowing = $this->findBorrowing($id);
        if ($borrowing === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE borrowings
             SET borrower_name = :borrower_name, borrower_unit = :borrower_unit, borrowed_at = :borrowed_at,
                 notes = :notes, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'borrower_name' => $data['borrower_name'] ?? $borrowing['borrower_name'],
            'borrower_unit' => $data['borrower_unit'] ?? $borrowing['borrower_unit'],
            'borrowed_at' => $data['borrowed_at'] ?? $borrowing['borrowed_at'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $borrowing['notes'],
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return $this->findBorrowing($id);
    }

    public function deleteBorrowing(int $id, ?int $userId = null): bool
    {
        $borrowing = $this->findBorrowing($id);
        if ($borrowing === null) {
            return false;
        }

        if ($borrowing['status'] !== 'borrowed') {
            throw new HttpException('Validasi gagal', 422, ['status' => 'Peminjaman yang sudah dikembalikan tidak dapat dihapus']);
        }

        $item = $this->findMedicalItem((int) $borrowing['medical_item_id']);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->pdo->beginTransaction();
        try {
            $newStock = (int) $item['stock'] + (int) $borrowing['quantity'];
            $this->insertStockHistory([
                'medical_item_id' => (int) $item['id'],
                'type' => 'in',
                'description' => 'Pembatalan peminjaman #' . $id,
                'reference_type' => 'borrowing',
                'reference_id' => $id,
            ], (int) $borrowing['quantity'], $newStock, $userId);
            $this->setMedicalItemStock((int) $item['id'], $newStock);

            $stmt = $this->pdo->prepare(
                'UPDATE borrowings
                 SET status = "cancelled", deleted_at = :deleted_at, updated_at = :updated_at
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute(['deleted_at' => $this->now(), 'updated_at' => $this->now(), 'id' => $id]);
            $deleted = $stmt->rowCount() > 0;
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $deleted;
    }

    public function listReturns(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `returns`.*, borrowings.borrower_name, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
             FROM `returns`
             INNER JOIN borrowings ON borrowings.id = `returns`.borrowing_id
             INNER JOIN medical_items ON medical_items.id = borrowings.medical_item_id
             ORDER BY `returns`.created_at DESC, `returns`.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function findReturn(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `returns`.*, borrowings.borrower_name, medical_items.name AS medical_item_name, medical_items.code AS medical_item_code
             FROM `returns`
             INNER JOIN borrowings ON borrowings.id = `returns`.borrowing_id
             INNER JOIN medical_items ON medical_items.id = borrowings.medical_item_id
             WHERE `returns`.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createReturn(array $data, ?int $userId): array
    {
        $borrowing = $this->findBorrowing((int) $data['borrowing_id']);
        if ($borrowing === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $quantity = (int) $data['quantity'];
        $returned = $this->returnedQuantity((int) $borrowing['id']);
        if ($returned + $quantity > (int) $borrowing['quantity']) {
            throw new HttpException('Validasi gagal', 422, ['quantity' => 'Jumlah pengembalian melebihi jumlah yang dipinjam']);
        }

        $item = $this->findMedicalItem((int) $borrowing['medical_item_id']);
        if ($item === null) {
            throw new HttpException('Data tidak ditemukan', 404);
        }

        $this->pdo->beginTransaction();
        try {
            $now = $this->now();
            $stmt = $this->pdo->prepare(
                'INSERT INTO `returns` (borrowing_id, quantity, returned_at, condition_after, damage_notes, created_by, created_at, updated_at)
                 VALUES (:borrowing_id, :quantity, :returned_at, :condition_after, :damage_notes, :created_by, :created_at, :updated_at)'
            );
            $stmt->execute([
                'borrowing_id' => (int) $data['borrowing_id'],
                'quantity' => $quantity,
                'returned_at' => $data['returned_at'] ?? $now,
                'condition_after' => $data['condition_after'],
                'damage_notes' => $data['damage_notes'] ?? null,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $returnId = (int) $this->pdo->lastInsertId();
            $newStock = (int) $item['stock'] + $quantity;
            $this->insertStockHistory([
                'medical_item_id' => (int) $item['id'],
                'type' => 'in',
                'description' => 'Pengembalian alat medis #' . $returnId,
                'reference_type' => 'return',
                'reference_id' => $returnId,
                'condition_after' => $data['condition_after'],
            ], $quantity, $newStock, $userId);
            $this->setMedicalItemStock((int) $item['id'], $newStock, $data['condition_after']);

            $totalReturned = $returned + $quantity;
            $status = $totalReturned >= (int) $borrowing['quantity'] ? 'returned' : 'partially_returned';
            $updateBorrowing = $this->pdo->prepare(
                'UPDATE borrowings SET status = :status, returned_at = :returned_at, updated_at = :updated_at WHERE id = :id'
            );
            $updateBorrowing->execute([
                'status' => $status,
                'returned_at' => $status === 'returned' ? ($data['returned_at'] ?? $now) : null,
                'updated_at' => $now,
                'id' => (int) $borrowing['id'],
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->findReturn($returnId);
    }

    public function listActivityLogs(): array
    {
        $stmt = $this->pdo->query(
            'SELECT activity_logs.*, users.name AS user_name
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             ORDER BY activity_logs.created_at DESC, activity_logs.id DESC
             LIMIT 200'
        );

        return $stmt->fetchAll();
    }

    public function logActivity(?int $userId, string $action, ?string $tableName = null, ?int $recordId = null, ?string $description = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activity_logs (user_id, action, table_name, record_id, description, created_at)
             VALUES (:user_id, :action, :table_name, :record_id, :description, :created_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'description' => $description,
            'created_at' => $this->now(),
        ]);
    }

    private function currentPasswordHash(int $id): string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (string) $stmt->fetchColumn();
    }

    private function returnedQuantity(int $borrowingId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM `returns` WHERE borrowing_id = :borrowing_id');
        $stmt->execute(['borrowing_id' => $borrowingId]);

        return (int) $stmt->fetchColumn();
    }

    private function insertStockHistory(array $data, int $quantity, int $stockAfter, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO stock_histories
             (medical_item_id, type, quantity, stock_after, condition_after, reference_type, reference_id, description, created_by, created_at, updated_at)
             VALUES (:medical_item_id, :type, :quantity, :stock_after, :condition_after, :reference_type, :reference_id, :description, :created_by, :created_at, :updated_at)'
        );
        $stmt->execute([
            'medical_item_id' => (int) $data['medical_item_id'],
            'type' => $data['type'],
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'condition_after' => $data['condition_after'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function setMedicalItemStock(int $id, int $stock, ?string $condition = null): void
    {
        $sql = 'UPDATE medical_items SET stock = :stock, updated_at = :updated_at';
        $params = ['stock' => $stock, 'updated_at' => $this->now(), 'id' => $id];

        if ($condition !== null && $condition !== '') {
            $sql .= ', `condition` = :condition';
            $params['condition'] = $condition;
        }

        $stmt = $this->pdo->prepare($sql . ' WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute($params);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
