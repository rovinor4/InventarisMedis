<?php

final class InventoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listEquipment(?string $search = null): array
    {
        $sql = 'SELECT * FROM medical_equipment WHERE deleted_at IS NULL';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (code LIKE :search OR name LIKE :search OR category LIKE :search OR location LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getEquipment(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medical_equipment WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createEquipment(array $data): array
    {
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'INSERT INTO medical_equipment
            (code, name, category, unit, location, condition_status, min_stock, total_stock, notes, created_at, updated_at)
            VALUES
            (:code, :name, :category, :unit, :location, :condition_status, :min_stock, :total_stock, :notes, :created_at, :updated_at)'
        );
        $stmt->execute([
            'code' => $data['code'],
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'unit' => $data['unit'] ?? null,
            'location' => $data['location'] ?? null,
            'condition_status' => $data['condition_status'] ?? 'baik',
            'min_stock' => $data['min_stock'] ?? 0,
            'total_stock' => $data['total_stock'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->getEquipment((int) $this->pdo->lastInsertId());
    }

    public function updateEquipment(int $id, array $data): ?array
    {
        $equipment = $this->getEquipment($id);
        if ($equipment === null) {
            return null;
        }

        $fields = [
            'code' => $data['code'] ?? $equipment['code'],
            'name' => $data['name'] ?? $equipment['name'],
            'category' => $data['category'] ?? $equipment['category'],
            'unit' => $data['unit'] ?? $equipment['unit'],
            'location' => $data['location'] ?? $equipment['location'],
            'condition_status' => $data['condition_status'] ?? $equipment['condition_status'],
            'min_stock' => $data['min_stock'] ?? $equipment['min_stock'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $equipment['notes'],
        ];

        $stmt = $this->pdo->prepare(
            'UPDATE medical_equipment
             SET code = :code,
                 name = :name,
                 category = :category,
                 unit = :unit,
                 location = :location,
                 condition_status = :condition_status,
                 min_stock = :min_stock,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ...$fields,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return $this->getEquipment($id);
    }

    public function deleteEquipment(int $id): bool
    {
        $activeBorrowings = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM borrow_transactions
             WHERE equipment_id = :equipment_id AND status = "borrowed"'
        );
        $activeBorrowings->execute(['equipment_id' => $id]);
        $count = (int) ($activeBorrowings->fetch()['total'] ?? 0);
        if ($count > 0) {
            throw new RuntimeException('Masih ada peminjaman aktif. Selesaikan pengembalian sebelum menghapus data.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE medical_equipment
             SET deleted_at = :deleted_at, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'deleted_at' => $this->now(),
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function adjustStock(int $equipmentId, int $quantity, string $type, ?string $notes = null, ?string $referenceType = null, ?int $referenceId = null): ?array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $equipment = $this->getEquipment($equipmentId);
        if ($equipment === null) {
            return null;
        }

        $delta = match ($type) {
            'in' => $quantity,
            'out' => -$quantity,
            'adjustment' => $quantity,
            default => throw new InvalidArgumentException('Invalid stock movement type.'),
        };

        $newStock = (int) $equipment['total_stock'] + $delta;
        if ($newStock < 0) {
            throw new RuntimeException('Stok tidak mencukupi.');
        }

        $this->persistStockChange($equipmentId, $newStock, $quantity, $type, $notes, $referenceType, $referenceId);

        return $this->getEquipment($equipmentId);
    }

    public function borrowEquipment(int $equipmentId, array $data): array
    {
        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $equipment = $this->getEquipment($equipmentId);
        if ($equipment === null) {
            throw new RuntimeException('Alat medis tidak ditemukan.');
        }

        if ((int) $equipment['total_stock'] < $quantity) {
            throw new RuntimeException('Stok tidak mencukupi untuk peminjaman.');
        }

        $this->pdo->beginTransaction();
        try {
            $borrowedAt = $this->now();
            $stmt = $this->pdo->prepare(
                'INSERT INTO borrow_transactions
                (equipment_id, borrower_name, borrower_unit, quantity, borrowed_at, due_at, status, notes, created_at, updated_at)
                VALUES
                (:equipment_id, :borrower_name, :borrower_unit, :quantity, :borrowed_at, :due_at, :status, :notes, :created_at, :updated_at)'
            );
            $stmt->execute([
                'equipment_id' => $equipmentId,
                'borrower_name' => $data['borrower_name'],
                'borrower_unit' => $data['borrower_unit'] ?? null,
                'quantity' => $quantity,
                'borrowed_at' => $borrowedAt,
                'due_at' => $data['due_at'] ?? null,
                'status' => 'borrowed',
                'notes' => $data['notes'] ?? null,
                'created_at' => $borrowedAt,
                'updated_at' => $borrowedAt,
            ]);

            $borrowId = (int) $this->pdo->lastInsertId();
            $this->persistStockChange(
                $equipmentId,
                (int) $equipment['total_stock'] - $quantity,
                $quantity,
                'out',
                'Peminjaman alat medis',
                'borrow_transaction',
                $borrowId
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $this->getBorrowTransaction($borrowId);
    }

    public function returnBorrowing(int $borrowId, ?string $notes = null): array
    {
        $borrow = $this->getBorrowTransaction($borrowId);
        if ($borrow === null) {
            throw new RuntimeException('Transaksi peminjaman tidak ditemukan.');
        }

        if ($borrow['status'] === 'returned') {
            throw new RuntimeException('Transaksi sudah dikembalikan.');
        }

        $this->pdo->beginTransaction();
        try {
            $equipment = $this->getEquipment((int) $borrow['equipment_id']);
            if ($equipment === null) {
                throw new RuntimeException('Alat medis tidak ditemukan.');
            }

            $this->persistStockChange(
                (int) $borrow['equipment_id'],
                (int) $equipment['total_stock'] + (int) $borrow['quantity'],
                (int) $borrow['quantity'],
                'in',
                'Pengembalian alat medis',
                'borrow_transaction',
                $borrowId
            );

            $stmt = $this->pdo->prepare(
                'UPDATE borrow_transactions
                 SET status = :status, returned_at = :returned_at, return_notes = :return_notes, updated_at = :updated_at
                 WHERE id = :id'
            );
            $stmt->execute([
                'status' => 'returned',
                'returned_at' => $this->now(),
                'return_notes' => $notes,
                'updated_at' => $this->now(),
                'id' => $borrowId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $this->getBorrowTransaction($borrowId);
    }

    public function listBorrowTransactions(): array
    {
        $stmt = $this->pdo->query(
            'SELECT bt.*, me.code AS equipment_code, me.name AS equipment_name
             FROM borrow_transactions bt
             LEFT JOIN medical_equipment me ON me.id = bt.equipment_id
             ORDER BY bt.created_at DESC, bt.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function getBorrowTransaction(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bt.*, me.code AS equipment_code, me.name AS equipment_name
             FROM borrow_transactions bt
             LEFT JOIN medical_equipment me ON me.id = bt.equipment_id
             WHERE bt.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function listStockMovements(?int $equipmentId = null): array
    {
        $sql = 'SELECT sm.*, me.code AS equipment_code, me.name AS equipment_name
                FROM stock_movements sm
                LEFT JOIN medical_equipment me ON me.id = sm.equipment_id';
        $params = [];

        if ($equipmentId !== null) {
            $sql .= ' WHERE sm.equipment_id = :equipment_id';
            $params['equipment_id'] = $equipmentId;
        }

        $sql .= ' ORDER BY sm.created_at DESC, sm.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function persistStockChange(
        int $equipmentId,
        int $newStock,
        int $quantity,
        string $type,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $update = $this->pdo->prepare(
            'UPDATE medical_equipment
             SET total_stock = :total_stock, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $update->execute([
            'total_stock' => $newStock,
            'updated_at' => $this->now(),
            'id' => $equipmentId,
        ]);

        $insert = $this->pdo->prepare(
            'INSERT INTO stock_movements
            (equipment_id, movement_type, quantity, reference_type, reference_id, notes, created_at)
            VALUES
            (:equipment_id, :movement_type, :quantity, :reference_type, :reference_id, :notes, :created_at)'
        );
        $insert->execute([
            'equipment_id' => $equipmentId,
            'movement_type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_at' => $this->now(),
        ]);
    }
}
