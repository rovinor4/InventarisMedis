# REST API Inventaris Medis

Backend REST API plain PHP + MySQL untuk mengelola inventaris alat medis rumah sakit, stok, peminjaman, pengembalian, user, token login, role, dan audit log.

## Teknologi

- PHP 8.1 atau lebih baru
- MySQL 8 atau MariaDB yang kompatibel
- PDO prepared statement
- Token Bearer berbasis `random_bytes()` dan hash SHA-256
- Password hash menggunakan `password_hash()` dan verifikasi dengan `password_verify()`

## Instalasi

1. Salin konfigurasi environment:

```bash
cp .env.example .env
```

2. Sesuaikan credential database di `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventaris_medis
DB_USERNAME=username_db
DB_PASSWORD=password_db
```

3. Jalankan migration:

```bash
mysql -u username_db -p < migration.sql
```

4. Jalankan server lokal:

```bash
php -S 127.0.0.1:8000 router.php
```

Atau jika memakai Composer:

```bash
composer run serve
```

## Akun Awal

Migration membuat akun Super Admin:

```text
Email: superadmin@example.com
Password: superadmin123
Role: Super Admin
```

Ganti password akun ini setelah login pertama.

## Header Request

Semua request JSON:

```http
Content-Type: application/json
Accept: application/json
```

Endpoint yang membutuhkan login:

```http
Authorization: Bearer {token}
```

## Login

Request:

```http
POST /auth/login
```

```json
{
  "email": "superadmin@example.com",
  "password": "superadmin123"
}
```

Response:

```json
{
  "message": "Login berhasil",
  "data": {
    "token": "token-mentah-hanya-muncul-sekali",
    "token_type": "Bearer",
    "expires_at": "2026-06-25 10:00:00"
  }
}
```

## Format Response

Success:

```json
{
  "message": "Data berhasil ditampilkan",
  "data": []
}
```

Validasi gagal:

```json
{
  "message": "Validasi gagal",
  "errors": {
    "name": "Nama alat medis tidak boleh kosong"
  }
}
```

Autentikasi gagal:

```json
{
  "message": "Token tidak valid atau belum login"
}
```

Role tidak sesuai:

```json
{
  "message": "Anda tidak memiliki akses untuk melakukan aksi ini"
}
```

## Endpoint

Auth:

- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`

User:

- `GET /users`
- `POST /users`
- `PUT /users/{id}`
- `DELETE /users/{id}`

Alat medis:

- `GET /medical-items`
- `POST /medical-items`
- `GET /medical-items/{id}`
- `PUT /medical-items/{id}`
- `DELETE /medical-items/{id}`

Stok:

- `GET /stock-histories`
- `POST /stock-histories`
- `GET /stock-histories/{id}`

Peminjaman:

- `GET /borrowings`
- `POST /borrowings`
- `GET /borrowings/{id}`
- `PUT /borrowings/{id}`
- `DELETE /borrowings/{id}`

Pengembalian:

- `GET /returns`
- `POST /returns`
- `GET /returns/{id}`

Audit log:

- `GET /activity-logs`

## Contoh Request

Tambah alat medis:

```http
POST /medical-items
Authorization: Bearer {token}
```

```json
{
  "name": "Stetoskop",
  "code": "MED-001",
  "category": "Diagnostik",
  "description": "Stetoskop untuk pemeriksaan umum",
  "stock": 20,
  "unit": "pcs",
  "condition": "baik"
}
```

Tambah riwayat stok:

```json
{
  "medical_item_id": 1,
  "type": "in",
  "quantity": 10,
  "condition_after": "baik",
  "description": "Stok masuk dari pengadaan"
}
```

Tambah peminjaman:

```json
{
  "medical_item_id": 1,
  "borrower_name": "Dr. Andi",
  "borrower_unit": "IGD",
  "quantity": 2,
  "borrowed_at": "2026-06-24"
}
```

Tambah pengembalian:

```json
{
  "borrowing_id": 1,
  "quantity": 2,
  "returned_at": "2026-06-25",
  "condition_after": "baik"
}
```

Jika `condition_after` bernilai `rusak`, isi `damage_notes` untuk mencatat status kerusakan.

## Role dan Hak Akses

- `Super Admin`: semua data, user, role, alat medis, stok, peminjaman, pengembalian, dan log.
- `Admin Inventaris`: alat medis, stok, peminjaman, dan pengembalian.
- `Petugas Gudang`: stok masuk, stok keluar, dan kondisi alat.
- `Petugas Medis`: melihat alat medis dan membuat peminjaman.
- `Auditor`: melihat data dan audit log tanpa mengubah data.

## Catatan Keamanan

- `.env` masuk `.gitignore` dan tidak boleh di-commit.
- Error detail database hanya ditulis ke `storage/logs/app.log`.
- Query memakai prepared statement.
- Token disimpan dalam bentuk hash, token mentah hanya diberikan saat login.
- Data alat medis dan user dihapus dengan soft delete.
