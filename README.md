# TA Cloud

> Sistem manajemen tugas akhir untuk kampus — mengelola proposal skripsi, bimbingan, permohonan sidang, penilaian, dokumen final, template dokumen final, dan notifikasi realtime.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white)

---

## Gambaran Singkat

TA Cloud adalah platform akademik berbasis role untuk mendigitalisasi seluruh lifecycle tugas akhir:
- pengajuan proposal
- penetapan pembimbing / penguji
- bimbingan dan revisi dokumen
- permohonan sidang
- input dan penguncian nilai
- request unlock nilai
- final submission
- final review approval
- pengelolaan template dokumen final
- notifikasi realtime

## Peran Pengguna

| Role | Scope | Kemampuan utama |
|------|-------|-----------------|
| Kaprodi | Global | CRUD master data, assignment, approve proposal/sidang/final review, unlock nilai, manage document templates |
| Dosen | Assigned only | Bimbingan, penilaian, sidang request, request unlock nilai |
| Mahasiswa | Own records only | Skripsi CRUD, upload proposal/dokumen, final submission, nilai, non-skripsi |

## Modul Aktif

### Kaprodi
- monitoring skripsi
- proposal submission approval
- sidang request approval
- final review approval
- grade unlock approval
- format penilaian management
- document template management
- dosen / mahasiswa / periode / tahun akademik CRUD
- import CSV dosen dan mahasiswa

### Dosen
- assigned skripsi dashboard
- bimbingan create/update/delete
- sidang request submission
- penilaian sidang
- request unlock nilai

### Mahasiswa
- skripsi CRUD
- proposal upload
- document versioning
- bimbingan response
- bimbingan export CSV/PDF
- final submission
- nilai view
- non-skripsi CRUD

## Realtime & Notifications
- Laravel Reverb aktif
- database-backed notifications aktif
- dropdown notifikasi persistent
- unlock request dan review event masuk ke flow notifikasi

## Reusable UI
Current UI reuse strategy memakai:
- Blade partials di `resources/views/partials`
- shared icons
- table/form/card/header widgets
- one anonymous component file exists, but project belum memakai Laravel Components secara aktif sebagai pattern utama

## Dokumentasi Pengguna
Panduan HTML multi-halaman tersedia di folder `docs/`:
- `docs/index.html`
- `docs/kaprodi.html`
- `docs/dosen.html`
- `docs/mahasiswa.html`
- `docs/common-features.html`
- `docs/faq.html`
- `docs/known-limitations.html`

## Status Saat Ini
**Stabil / locked**
- Kaprodi core operations
- Dosen core operations
- Mahasiswa core operations
- notifications realtime
- final submission routes
- document template module

**Masih terbuka**
- export / rekap global Kaprodi
- publish backend ke library
- automation phase board / keputusan akhir
- advanced RBAC
- program-specific workflow engine
- Google login

## Pengembangan Lokal
Project memakai Laravel 13 + PHP 8.3 + Vite + Tailwind v4 + Reverb.
Lihat konfigurasi lokal dan dokumen tambahan pada file markdown lain di root project serta folder `docs/`.
