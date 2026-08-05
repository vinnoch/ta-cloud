# TA Cloud

> Sistem manajemen tugas akhir untuk kampus — mengelola proposal, bimbingan, sidang, penilaian, dokumen final, library, notifikasi realtime, dan operasi teknis yang diaudit.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white)

---

## Gambaran Singkat

TA Cloud adalah platform akademik berbasis role yang mendigitalisasi seluruh lifecycle tugas akhir:

- pengajuan proposal
- penetapan pembimbing / penguji
- bimbingan dan revisi dokumen
- permohonan sidang
- input dan penguncian nilai
- pengajuan request unlock nilai
- final submission
- approval final review
- penyelesaian historis untuk lulusan periode lama
- publikasi otomatis skripsi selesai ke library publik
- pengelolaan template dokumen final
- notifikasi realtime
- administrasi akun, branding, audit, dan health check oleh Superadmin

## Peran Pengguna

| Role | Scope | Fitur |
|------|-------|-----------------|
| Superadmin | Operasi sistem | Bootstrap aman, lifecycle akun dan role, branding, audit log, serta status database dan server |
| Admin | Reserved | Disiapkan untuk Faculty Admin; kewenangan bisnis belum diaktifkan |
| Kaprodi | Global | CRUD master data, assignment, approval proposal/sidang/final review, unlock nilai, dan document template management |
| Dosen | Assigned only | Bimbingan, penilaian, pengajuan sidang, dan request unlock nilai |
| Mahasiswa | Own records only | CRUD skripsi, upload proposal/dokumen, final submission, nilai, dan non-skripsi |

## Modul Aktif

### Superadmin

- dashboard teknis serta status database dan server yang aman
- invite akun institusi dan pengelolaan role
- penonaktifan dan pengaktifan kembali akun dengan proteksi last-superadmin
- pengaturan nama aplikasi dan logo
- audit log lintas role dengan retensi 500 aktivitas terbaru
- Google re-authentication untuk perubahan sensitif

### Kaprodi

- monitoring skripsi
- monitoring non-skripsi
- approval pengajuan proposal
- approval pengajuan sidang
- approval final review
- approval grade unlock
- pengelolaan format penilaian
- pengelolaan document template
- export data skripsi
- penyelesaian dokumen final historis pada periode nonaktif
- CRUD dosen, mahasiswa, periode, dan tahun akademik
- import CSV dosen dan mahasiswa

### Dosen

- dashboard skripsi yang ditugaskan
- create/update/delete bimbingan
- pengajuan sidang
- penilaian sidang
- request unlock nilai

### Mahasiswa

- CRUD skripsi
- upload proposal
- document versioning
- respons bimbingan
- export bimbingan ke CSV/PDF
- final submission
- tampilan nilai
- CRUD non-skripsi

## Realtime & Notifications

- Laravel Reverb aktif
- notifications berbasis database aktif
- dropdown notifikasi persisten
- event request unlock dan review masuk ke flow notifikasi

## Reusable UI

Strategi reusable UI saat ini memakai:

- Blade partials di `resources/views/partials`
- shared icons
- table/form/card/header widgets
- Blade partials sebagai pola utama reusable UI
- tidak ada anonymous Blade component yang tersisa pada layer UI aktif

## Progress UI/UX Terkini

- penyegaran layout dan styling lintas halaman utama
- perapian tampilan detail skripsi Kaprodi
- penyesuaian navigasi role-based agar lebih konsisten dengan fase skripsi
- pembaruan ikon, tabel, filter, dan elemen interaksi pada workspace utama

## Dokumentasi Pengguna

Panduan HTML multi-halaman tersedia di folder `public/docs/`:

- `public/docs/index.html`
- `public/docs/superadmin.html`
- `public/docs/kaprodi.html`
- `public/docs/dosen.html`
- `public/docs/mahasiswa.html`
- `public/docs/common-features.html`
- `public/docs/faq.html`
- `public/docs/known-limitations.html`
- `public/docs/visual-check.html`

## Status Saat Ini

**Siap diluncurkan pada scope yang diuji (rilis `eb15955`)**

- operasi Superadmin, fresh Google re-authentication, audit, branding, dan system information
- core operations Kaprodi
- core operations Dosen
- core operations Mahasiswa
- realtime notifications
- route final submission
- modul document template
- Google login
- monitoring non-skripsi Kaprodi
- library publik otomatis untuk skripsi selesai dengan dokumen final
- export CSV skripsi Kaprodi
- production seed tanpa akun atau password default

**Masih terbuka**

- Faculty Admin (role `admin` masih reserved)
- automation lanjutan untuk phase board / keputusan akhir
- advanced RBAC
- program-specific workflow engine
- API / token auth layer

## Instalasi Produksi Ringkas

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
php artisan superadmin:bootstrap nama@widyakarya.ac.id
```

Seeder hanya membuat master data dan lima role; seeder tidak membuat akun atau password default. Daftarkan kedua callback Google pada production host:

```text
https://domain-produksi/auth/google/callback
https://domain-produksi/superadmin/reauth/google/callback
```

Jangan pernah menjalankan `php artisan migrate:fresh` pada production server.

## Pengembangan Lokal

Project ini memakai Laravel 13 + PHP 8.4 + Vite + Tailwind v4 + Reverb.
Lihat `.env.example` dan panduan pada folder `public/docs/` untuk konfigurasi lanjutan.
