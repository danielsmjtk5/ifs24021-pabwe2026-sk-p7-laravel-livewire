# Studi Kasus P7 — Aplikasi Stock Barang (Laravel 13 & Livewire 4, Feature-First)

Aplikasi inventaris stok barang dengan arsitektur **Clean Architecture + Feature-First**, dibangun dengan **Laravel 13**, **Livewire 4**, **MySQL/MariaDB**, **PhpOffice/PhpSpreadsheet**, dan integrasi **OpenAI** untuk generate draf item stok. Pola mengikuti latihan Todolist P7, domain `Todo` diganti menjadi `Stock`.

- Nama proyek: `ifs24021-pabwe2026-sk-p7-laravel-livewire`
- Database: `pabwe_p7_stock` (`utf8mb4_unicode_ci`)
- Akun seeder: **admin / admin** dan **member / member** (+ 4 contoh stok untuk member)

---

## Fitur

| Peran  | Kemampuan |
|--------|-----------|
| Guest  | Login (username/email) & register; `/` diarahkan ke `/login` |
| Member | Kelola stok miliknya sendiri: daftar (cari, filter status, pagination), CRUD via modal, **adjust quantity (+/−)** dari list & detail, detail + cover image + deskripsi Markdown (Toast UI), import/export Excel, generate item dengan AI (preview → pilih → simpan), profil (data, password, foto) |
| Admin  | `/admin/users`: daftar user + ringkasan stok (jenis barang, total qty, menipis/habis). `/admin/users/{user}/stocks`: daftar stok user **read-only** (+ export Excel). Admin tidak dapat mengubah/menghapus stok user lain |

### Status stok otomatis

`StockStatus` (`available` / `low_stock` / `out_of_stock`) punya `label()` dan `badgeClass()` (hijau / kuning / merah).
Status diturunkan dari `quantity` vs `min_stock`:

- `quantity <= 0` → **out_of_stock**
- `quantity <= min_stock` → **low_stock**
- selain itu → **available**

Setiap adjust quantity selalu menyinkronkan status. Form create/edit menyediakan pilihan **Otomatis** (default) atau status manual.

### Format Excel

Header (kolom A–J): `No | Name | SKU | Description | Quantity | Unit | Price | Min Stock | Status | Created At`.
File hasil export dapat langsung di-import kembali. Saat import, status kosong/tidak dikenal dihitung otomatis; `-` dianggap kosong.

---

## Struktur Feature-First

```
app/
├── Features/
│   ├── Admin/    Livewire/{AdminUserStockList,UserList}.php · Services/AdminUserService.php
│   ├── Ai/       Contracts/AiClientInterface.php · DTO/GeneratedStockItem.php · Services/OpenAiStockGeneratorService.php
│   ├── Auth/     Actions/{LoginUserAction,RegisterUserAction}.php · Livewire/{Login,Register}.php
│   ├── Profile/  Livewire/Profile.php · Services/ProfileService.php
│   └── Stock/    Contracts/StockRepositoryInterface.php · Enums/StockStatus.php
│                 Livewire/{StockDetail,StockList}.php · Models/Stock.php · Policies/StockPolicy.php
│                 Repositories/StockRepository.php · Services/{StockExcelService,StockService}.php
├── Http/Middleware/{EnsureRole,EnsureStockOwnership}.php
├── Models/User.php
└── Providers/AppServiceProvider.php
```

Middleware alias (di `bootstrap/app.php`): `role` → `EnsureRole` (mendukung `role:admin|member`), `owns.stock` → `EnsureStockOwnership`.

## Rute

| Method | URI | Keterangan |
|--------|-----|------------|
| GET | `/` | Guest → login; admin → `/admin/users`; member → `/stocks` |
| GET | `/login`, `/register` | Guest |
| POST | `/logout` | Auth |
| GET | `/stocks` | Daftar stok (`role:admin\|member`, `owns.stock`) |
| GET | `/stocks/{stock}` | Detail stok (`owns.stock` + `StockPolicy`) |
| GET | `/profile` | Profil |
| GET | `/admin/users` | Admin |
| GET | `/admin/users/{user}/stocks` | Admin, read-only |

Semua rute dibungkus `throttle:1000-per-second`. `FORCE_HTTPS=true` memaksa skema https.

---

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
php artisan storage:link
npm install --ignore-scripts && npm run build
php artisan serve
```

Isi `OPENAI_ENDPOINT`, `OPENAI_KEY`, `OPENAI_MODEL` di `.env` untuk fitur AI. Ekstensi PHP yang dibutuhkan: `gd`, `zip`, `mbstring`, `pdo_mysql` (dan `pdo_sqlite` + `pcov`/`xdebug` untuk test).

## Pengujian

```bash
composer test     # PHPUnit + coverage text (target app/ 100%)
composer audit    # tidak boleh ada advisory
```

`phpunit.xml` memakai SQLite in-memory dan mengaktifkan `failOnWarning`, `failOnNotice`, `failOnRisky` sehingga notice PHPUnit (mis. mock tanpa expectation) langsung menggagalkan test. Mock AI di test memakai `createStub()`.

## CI/CD

`Jenkinsfile`: install Composer + `composer audit` (gagal jika ada kerentanan) → `npm run build` → PHPUnit dengan gate coverage 100% (lines, methods, elements) → Trivy → paket zip tanpa `vendor/`/`node_modules/` → publish & deploy.

Artefak uji (`coverage-*`, `coverage.xml`, `junit.xml`, `.phpunit.cache`, `*.txt`) dan `.env` diabaikan oleh `.gitignore`.
