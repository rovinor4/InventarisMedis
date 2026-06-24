# REST API Inventaris Medis

Backend REST API plain PHP + MySQL untuk inventaris medis rumah sakit.

## Setup

1. Import `migration.sql` ke MySQL.
2. Sesuaikan `config.php` jika credential database berbeda.
3. Jalankan:

```bash
php -S 127.0.0.1:8000 router.php
```

## Endpoint

- `GET /api/alat-medis`
- `POST /api/alat-medis`
- `GET /api/alat-medis/{id}`
- `PUT /api/alat-medis/{id}`
- `DELETE /api/alat-medis/{id}`
- `POST /api/alat-medis/{id}/stok`
- `POST /api/alat-medis/{id}/pinjam`
- `POST /api/peminjaman/{id}/kembali`
- `GET /api/peminjaman`
- `GET /api/stok`

## Catatan

- Hapus data alat medis menggunakan soft delete agar riwayat stok dan peminjaman tetap aman.
- Endpoint mengembalikan JSON dan sudah disiapkan untuk CORS dasar.
