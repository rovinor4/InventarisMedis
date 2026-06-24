# Instruksi SCoT REST API Inventaris Medis Rumah Sakit

## 0. Persiapan Awal (Wajib Dilakukan Sebelum Implementasi)

Sebelum mulai mengerjakan sistem, lakukan langkah berikut:

1. Pisahkan branch repository:

    * Buat branch untuk versi lama (misalnya: `instruksi-lama`).
    * Buat branch untuk versi baru (misalnya: `instruksi-baru`).
    * Pastikan kedua branch memiliki isi yang sesuai dengan masing-masing versi instruksi.

2. Buat issue di repository:

    * Issue pertama untuk mengupload dan mendokumentasikan `Instruksi.md` versi sebelumnya.
    * Issue kedua untuk mengupload dan mendokumentasikan `Instruksi.md` versi terbaru (dokumen ini).
    * Sertakan deskripsi perubahan antara versi lama dan versi baru jika memungkinkan.

Langkah ini wajib dilakukan untuk menjaga versioning, dokumentasi, dan histori perubahan.

## 1. Tujuan Sistem

Buat backend REST API untuk sistem inventaris medis rumah sakit menggunakan PHP dan MySQL. Sistem harus dibuat dengan struktur kode yang rapi, aman, mudah diuji, dan mudah dikembangkan.

Aplikasi digunakan untuk mengelola data alat medis, stok, peminjaman, pengembalian, pengguna, serta hak akses berdasarkan role.

## 2. Teknologi yang Digunakan

Gunakan PHP versi terbaru yang stabil.

Gunakan MySQL sebagai database.

Gunakan library PHP yang mendukung pembuatan API agar lebih rapi, seperti:

1. Library routing untuk mengatur endpoint API.
2. Library Eloquent ORM atau database query builder.
3. Library migration agar struktur database lebih mudah dikelola.
4. Library dotenv untuk membaca konfigurasi dari file `.env`.
5. Library validasi request jika tersedia.
6. Library token atau random generator yang aman untuk autentikasi.

Jangan menulis username database, password database, host, dan nama database secara langsung di dalam source code. Semua konfigurasi wajib disimpan di file `.env`.

## 3. Format Input

Semua endpoint yang menerima data wajib menggunakan format JSON.

Header request wajib:

```http
Content-Type: application/json
Accept: application/json
```

Untuk endpoint yang membutuhkan login, wajib menggunakan header:

```http
Authorization: Bearer {token}
```

Contoh input tambah alat medis:

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

Contoh input peminjaman alat:

```json
{
  "medical_item_id": 1,
  "borrower_name": "Dr. Andi",
  "borrower_unit": "IGD",
  "quantity": 2,
  "borrowed_at": "2026-06-24"
}
```

## 4. Format Output Success

Semua response berhasil wajib menggunakan format berikut:

```json
{
  "message": "Berhasil",
  "data": {}
}
```

Jika data berupa daftar:

```json
{
  "message": "Data berhasil ditampilkan",
  "data": []
}
```

Contoh response tambah data:

```json
{
  "message": "Alat medis berhasil ditambahkan",
  "data": {
    "id": 1,
    "name": "Stetoskop",
    "code": "MED-001",
    "category": "Diagnostik",
    "stock": 20,
    "unit": "pcs",
    "condition": "baik"
  }
}
```

## 5. Format Output Error

Semua response error wajib konsisten.

Error validasi:

```json
{
  "message": "Validasi gagal",
  "errors": {
    "name": "Nama alat medis tidak boleh kosong",
    "stock": "Stok harus berupa angka"
  }
}
```

Error autentikasi:

```json
{
  "message": "Token tidak valid atau belum login"
}
```

Error akses role:

```json
{
  "message": "Anda tidak memiliki akses untuk melakukan aksi ini"
}
```

Error data tidak ditemukan:

```json
{
  "message": "Data tidak ditemukan"
}
```

Error server:

```json
{
  "message": "Terjadi kesalahan pada server"
}
```

Jangan menampilkan detail error asli seperti query SQL, path file server, username database, atau stack trace kepada user.

## 6. Struktur Endpoint CRUD

Gunakan pola endpoint seperti Laravel REST API.

Endpoint alat medis:

```http
GET /medical-items
POST /medical-items
GET /medical-items/{id}
PUT /medical-items/{id}
DELETE /medical-items/{id}
```

Endpoint stok:

```http
GET /stock-histories
POST /stock-histories
GET /stock-histories/{id}
```

Endpoint peminjaman:

```http
GET /borrowings
POST /borrowings
GET /borrowings/{id}
PUT /borrowings/{id}
DELETE /borrowings/{id}
```

Endpoint pengembalian:

```http
GET /returns
POST /returns
GET /returns/{id}
```

Endpoint user dan auth:

```http
POST /auth/login
POST /auth/logout
GET /auth/me
GET /users
POST /users
PUT /users/{id}
DELETE /users/{id}
```

## 7. Role dan Hak Akses

Sistem wajib memiliki autentikasi dan otorisasi berbasis role.

Minimal role:

1. Super Admin
2. Admin Inventaris
3. Petugas Gudang
4. Petugas Medis
5. Auditor

Hak akses:

1. Super Admin dapat mengelola semua data, user, role, alat medis, stok, peminjaman, dan pengembalian.
2. Admin Inventaris dapat mengelola alat medis, stok, peminjaman, dan pengembalian.
3. Petugas Gudang dapat mengelola stok masuk, stok keluar, dan kondisi alat.
4. Petugas Medis hanya dapat melihat alat medis dan membuat peminjaman.
5. Auditor hanya dapat melihat data dan log tanpa mengubah data.

Setiap endpoint wajib diperiksa role-nya sebelum menjalankan proses utama.

## 8. Autentikasi Token

Login menggunakan email dan password.

Password tidak boleh disimpan dalam bentuk teks asli.

Password wajib dienkripsi menggunakan `password_hash()`.

Verifikasi password wajib menggunakan `password_verify()`.

Setelah login berhasil, sistem membuat token random yang aman menggunakan fungsi kriptografi, misalnya `random_bytes()`.

Token disimpan di database dengan informasi:

1. User ID.
2. Token yang sudah di-hash.
3. Waktu dibuat.
4. Waktu kedaluwarsa.
5. Status aktif atau tidak aktif.

Token mentah hanya diberikan satu kali kepada user saat login.

Setiap request yang membutuhkan login wajib membaca header:

```http
Authorization: Bearer {token}
```

Sistem wajib memeriksa:

1. Token ada atau tidak.
2. Token valid atau tidak.
3. Token belum kedaluwarsa.
4. User masih aktif.
5. Role user sesuai dengan endpoint yang diakses.

## 9. Logika Sequence dan Branching

Gunakan alur validasi berikut untuk setiap request yang membutuhkan login:

1. Terima request dari user.
2. Periksa header `Authorization`.
3. Jika token tidak ada, kembalikan response 401.
4. Jika token tidak valid, kembalikan response 401.
5. Jika token kedaluwarsa, kembalikan response 401.
6. Jika user tidak aktif, kembalikan response 403.
7. Periksa role user.
8. Jika role tidak sesuai, kembalikan response 403.
9. Validasi input.
10. Jika input tidak valid, kembalikan response 422.
11. Jalankan proses database.
12. Jika data tidak ditemukan, kembalikan response 404.
13. Jika proses berhasil, kembalikan response 200 atau 201.
14. Jika terjadi error server, catat log dan kembalikan response 500.

## 10. Validasi Input

Setiap input wajib divalidasi sebelum masuk ke database.

Validasi alat medis:

1. `name` wajib diisi.
2. `code` wajib diisi dan harus unik.
3. `category` wajib diisi.
4. `stock` wajib angka dan tidak boleh negatif.
5. `unit` wajib diisi.
6. `condition` hanya boleh berisi `baik`, `rusak`, atau `maintenance`.

Validasi user:

1. `name` wajib diisi.
2. `email` wajib diisi, harus valid, dan harus unik.
3. `password` wajib memiliki panjang minimal 8 karakter.
4. `role_id` wajib ada di tabel roles.

Validasi peminjaman:

1. `medical_item_id` wajib ada di tabel alat medis.
2. `borrower_name` wajib diisi.
3. `borrower_unit` wajib diisi.
4. `quantity` wajib angka dan lebih dari 0.
5. Jumlah peminjaman tidak boleh melebihi stok tersedia.
6. Tanggal peminjaman wajib valid.

Validasi pengembalian:

1. Data peminjaman wajib ada.
2. Jumlah pengembalian tidak boleh melebihi jumlah yang dipinjam.
3. Kondisi barang setelah kembali wajib dicatat.
4. Jika alat rusak, sistem wajib mencatat status kerusakan.

## 11. Keamanan Input dan Output

Semua input string wajib dibersihkan sebelum ditampilkan kembali.

Gunakan `htmlspecialchars()` saat data akan ditampilkan ke HTML.

Gunakan prepared statement, ORM, atau query builder agar tidak raw SQL sembarangan.

Dilarang menggabungkan input user langsung ke query SQL.

Contoh yang dilarang:

```php
$query = "SELECT * FROM users WHERE email = '$email'";
```

Gunakan query yang aman melalui ORM atau parameter binding.

Sistem harus mencegah:

1. SQL Injection.
2. Cross-Site Scripting.
3. Broken Authentication.
4. Broken Access Control.
5. Kebocoran konfigurasi database.
6. Error detail yang bocor ke user.
7. Manipulasi stok tanpa validasi.

## 12. Enkripsi dan Perlindungan Data

Password wajib di-hash menggunakan `password_hash()`.

Token wajib dibuat menggunakan random generator yang aman.

Token yang disimpan di database sebaiknya dalam bentuk hash.

Data sensitif tidak boleh ditulis di log.

File `.env` wajib masuk ke `.gitignore`.

Buat file `.env.example` tanpa credential asli.

Contoh `.env.example`:

```env
APP_NAME=MedicalInventoryAPI
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=database_name
DB_USERNAME=username_db
DB_PASSWORD=password_db
```

## 13. Struktur Folder

Gunakan struktur folder yang rapi agar mudah dikembangkan.

Contoh struktur:

```text
project/
  app/
    Controllers/
    Models/
    Middlewares/
    Requests/
    Services/
    Repositories/
  config/
  database/
    migrations/
    seeders/
  routes/
    api.php
  storage/
    logs/
  public/
    index.php
  .env.example
  .gitignore
  composer.json
  README.md
```

Penjelasan:

1. `Controllers` berisi penerima request dan pengirim response.
2. `Models` berisi representasi tabel database.
3. `Middlewares` berisi auth, role, dan validasi akses.
4. `Requests` berisi aturan validasi input.
5. `Services` berisi logic bisnis.
6. `Repositories` berisi query database.
7. `routes/api.php` berisi daftar endpoint.
8. `database/migrations` berisi struktur database.
9. `storage/logs` berisi catatan error aplikasi.

## 14. Database dan Migration

Buat file migration SQL untuk tabel minimal:

1. `users`
2. `roles`
3. `user_tokens`
4. `medical_items`
5. `stock_histories`
6. `borrowings`
7. `returns`
8. `activity_logs`

Setiap tabel wajib memiliki:

1. Primary key.
2. `created_at`.
3. `updated_at`.

Jika menggunakan soft delete, tambahkan `deleted_at`.

Tabel alat medis minimal berisi:

```text
id
name
code
category
description
stock
unit
condition
created_at
updated_at
deleted_at
```

Tabel peminjaman minimal berisi:

```text
id
medical_item_id
borrower_name
borrower_unit
quantity
borrowed_at
returned_at
status
created_at
updated_at
```

Tabel log aktivitas minimal berisi:

```text
id
user_id
action
table_name
record_id
description
created_at
```

## 15. Logging dan Error Handling

Setiap proses penting wajib memiliki logging.

Log minimal mencatat:

1. Login berhasil.
2. Login gagal.
3. Tambah data.
4. Ubah data.
5. Hapus data.
6. Peminjaman.
7. Pengembalian.
8. Error server.

Gunakan `try-catch` untuk proses database dan proses penting.

Error detail hanya dicatat di log server, bukan dikirim ke user.

Contoh response ke user:

```json
{
  "message": "Terjadi kesalahan pada server"
}
```

Contoh log internal:

```text
[2026-06-24 10:30:00] ERROR: gagal menambahkan alat medis pada MedicalItemService
```

## 16. Aturan Business Logic

Stok tidak boleh negatif.

Peminjaman hanya boleh dilakukan jika stok tersedia.

Saat peminjaman berhasil, stok alat medis berkurang.

Saat pengembalian berhasil, stok alat medis bertambah sesuai jumlah yang kembali.

Jika alat dikembalikan dalam kondisi rusak, status alat harus diperbarui atau dicatat dalam riwayat stok.

Data alat medis yang sudah memiliki riwayat peminjaman sebaiknya tidak dihapus permanen. Gunakan soft delete.

## 17. Output File yang Harus Dibuat

Buat project yang dapat langsung dijalankan.

File yang wajib dibuat:

1. Source code REST API.
2. `composer.json`.
3. `.env.example`.
4. `.gitignore`.
5. `README.md`.
6. `migration.sql`.
7. Dokumentasi endpoint API.
8. Contoh request dan response JSON.
9. Contoh akun awal untuk role Super Admin.

## 18. Isi README

README harus berisi:

1. Deskripsi aplikasi.
2. Teknologi yang digunakan.
3. Cara instalasi.
4. Cara konfigurasi `.env`.
5. Cara menjalankan aplikasi.
6. Cara menjalankan migration.
7. Daftar endpoint.
8. Contoh login.
9. Contoh penggunaan token Bearer.
10. Daftar role dan hak akses.

## 19. Kriteria Selesai

Aplikasi dianggap selesai jika:

1. Semua endpoint CRUD berjalan.
2. Login menggunakan token berjalan.
3. Password tersimpan dalam bentuk hash.
4. Role diperiksa di endpoint yang membutuhkan akses khusus.
5. Validasi input berjalan.
6. Response success dan error konsisten.
7. Migration SQL tersedia.
8. Struktur folder rapi.
9. Tidak ada credential database di source code.
10. Error server dicatat di log.
11. Stok tidak bisa menjadi negatif.
12. Peminjaman dan pengembalian memengaruhi stok secara benar.

## 20. Batasan Pengerjaan

Jangan membuat kode dalam satu file besar.

Jangan membuat query SQL dengan input user yang digabung langsung ke string.

Jangan menampilkan error asli database ke user.

Jangan menyimpan password dalam bentuk teks asli.

Jangan menyimpan credential database di GitHub.

Jangan membuat endpoint penting tanpa autentikasi.

Jangan membuat fitur delete permanen untuk data yang memiliki riwayat transaksi.

Jangan mengabaikan validasi stok pada peminjaman dan pengembalian.
