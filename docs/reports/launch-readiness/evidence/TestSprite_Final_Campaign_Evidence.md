# Paket Bukti Kampanye Final TACLOUD: Poin 1–20

**Dokumen:** bukti teknis untuk kompilasi ulang Laporan Kesiapan Peluncuran TACLOUD  
**Versi:** 1.0  
**Tanggal verifikasi terakhir:** 20 Juli 2026 (Asia/Jakarta)  
**Ruang lingkup:** dokumentasi dan verifikasi; tidak mengubah perilaku aplikasi  
**Task sumber utama:** TestSprite `019f682d-88e8-7932-bfd3-c1939880b4f3`  
**Task peminta/kompilasi:** Project Docs and Tracking `019f680a-af38-7b82-8daf-0b64b9a51885`

## 1. Ringkasan eksekutif

Kampanye final terdiri dari 20 poin berurutan. Nama “kampanye TestSprite” dipakai sebagai nama kampanye, tetapi tidak berarti seluruh poin dijalankan oleh browser TestSprite. Metode yang benar adalah:

| Metode | Pemakaian yang terbukti |
|---|---|
| TestSprite browser/E2E | Poin 3: lima skenario library publik. Retest tambahan setelah kampanye: TC029 pembatalan dan pengajuan ulang permohonan sidang Dosen. |
| Laravel/Pest | Autentikasi, otorisasi peran, ownership/IDOR, CSRF, mass assignment, upload, workflow, notifikasi, route registration, security headers, dan respons XSS. |
| Inspeksi kode dan impact graph | Penentuan root cause, caller/route/controller/view terkait, serta pemeriksaan dampak perubahan. |
| Build/cache/audit | Vite build, Laravel `optimize`, route cache, Composer validate/audit, npm audit, dan `git diff --check`. |
| Audit aksesibilitas | Regression markup, Blade cache, build, dan helper keyboard/focus. Browser-level accessibility audit penuh tidak tersedia karena bootstrap browser lokal gagal. |

Status terkini: **20/20 poin ditutup**. Business-flow correction menghapus workflow approval dokumen final yang tidak digunakan beserta model, test, UI status, dan tabelnya. Completion kini memeriksa dokumen final terbaru dan nilai reviewer published. Ringkasan final pada Bagian 10 menimpa addendum diagnosis lama pada Bagian 6.

Dua poin kampanye memiliki batasan eksplisit:

- Poin 12: CSP belum diaktifkan; secure cookie dan HSTS bergantung konfigurasi produksi HTTPS.
- Poin 18: semantics dan keyboard helper teruji secara markup/build, tetapi browser accessibility audit penuh belum direkam.

Gate workspace terkini pada 20 Juli 2026:

| Gate | Hasil terkini | Status |
|---|---|---|
| `composer test` | **133 test, 442 assertion**, durasi 3,69 detik | Lulus |
| `npm run build` | Vite 8.0.8; **56 modul**; build 399 ms | Lulus |
| `composer validate --strict` | `./composer.json is valid` | Lulus |
| `composer audit --locked` | `No security vulnerability advisories found.` | Lulus |
| `npm audit --omit=dev` | `found 0 vulnerabilities` | Lulus |
| `APP_ENV=testing APP_DEBUG=true CACHE_STORE=array php artisan optimize` | config/events/routes/views berhasil dicache | Lulus |
| `CACHE_STORE=array php artisan optimize:clear` | seluruh cache bootstrap dibersihkan | Lulus |
| `git diff --check` | tanpa output/error | Lulus |
| Warning build | `/images/login-wave.jpg` tidak di-resolve saat build dan tetap di-resolve saat runtime | Non-blocking; file publik tersedia |

Catatan audit: instruksi susulan menyebut percobaan audit 20 Juli sempat terhalang DNS sandbox. Setelah izin jaringan diberikan, audit diulang dan **berhasil**. Hasil nol advisory tanggal 20 Juli menggantikan keterbatasan percobaan awal; hasil independen 18 Juli tetap menjadi bukti historis.

## 2. Kronologi dan matriks ringkas

Urutan Poin 1–20 adalah kronologi otoritatif kampanye. Tanggal kalender per poin tidak selalu tersimpan; dokumen tidak mengarang tanggal yang hilang. Bukti bertanggal yang tersedia:

- Artefak library TestSprite dibuat/disinkronkan 16 Juli 2026.
- TC029 valid dibuat 18 Juli 2026 pukul 04:44 UTC dan selesai 04:53 UTC.
- Gate agregat dalam laporan awal diverifikasi 18 Juli 2026.
- Gate agregat pada dokumen ini diulang 20 Juli 2026.

| Poin | Fokus | Metode utama | Temuan awal | Status final |
|---:|---|---|---|---|
| 1 | Isolasi lingkungan/data uji | Seed, migrasi, config, auth test | SQLite schema squash dan onboarding Google | Lulus |
| 2 | Baseline suite/build | Pest, Vite, route render | 19 lulus/74 gagal; stale tests/Blade/icon | Lulus |
| 3 | Infrastruktur TestSprite | Browser TestSprite | Sinkronisasi tunnel/progres lokal | Lulus, 5/5 |
| 4 | Autentikasi | Pest auth/security | Coverage eksplisit belum lengkap | Lulus |
| 5 | Otorisasi peran | Pest middleware/cross-role | Probe ID palsu memberi 404 sebelum middleware | Lulus |
| 6 | Ownership/IDOR | Direct request/Pest | Dosen assigned dapat ubah bimbingan peer | Lulus |
| 7 | CSRF | Middleware aktif/direct request | Tidak ada defect | Lulus |
| 8 | Validasi/mass assignment | Boundary/forged input tests | Coverage privileged fields belum eksplisit | Lulus |
| 9 | Upload/download | MIME/path/phase/owner tests | Phase `skripsi_final` dapat dipalsukan | Lulus |
| 10 | XSS/escaping | Payload, DOM audit, build | Shared table merender cell sebagai raw HTML | Lulus |
| 11 | Query/sort manipulation | QuerySecurity tests | Sort direction berbahaya memicu 500 | Lulus |
| 12 | Security headers/session posture | Response tests/config audit | Header baseline belum ada | Lulus terbatas |
| 13 | Workflow Mahasiswa | Full Mahasiswa suite | Guard final prematur belum diuji langsung | Lulus |
| 14 | Workflow Dosen | Full Dosen suite | Submit sidang kritis belum punya coverage | Lulus |
| 15 | Workflow Kaprodi/legacy | Workflow probes | Penyelesaian dapat dilakukan tanpa bukti lengkap | Lulus |
| 16 | Lifecycle lintas peran/library | Integration/workflow/library | Completion-to-library belum eksplisit | Lulus |
| 17 | Notifikasi | Feature/cross-role producers | Tidak ada defect | Lulus |
| 18 | Aksesibilitas/UI | Markup/cache/build | 27 modal tanpa semantics; keyboard tidak konsisten | Lulus terbatas |
| 19 | Cache/kesiapan produksi | route cache/optimize | Nama route duplikat memblokir optimize | Lulus |
| 20 | Gate rilis/dependensi | Full gates/audits | 20 advisory pada 11 package | Lulus |

## 3. Bukti per poin

### Poin 1 — Isolasi lingkungan dan data uji

**Tujuan/ruang lingkup**

Menyiapkan lingkungan produksi-semu yang terpisah, deterministik, dapat direseed, menggunakan debug nonaktif dan data untuk seluruh peran/alur.

**Metode**

- Pemeriksaan konfigurasi environment, koneksi DB, migrasi, health/redirect.
- Percobaan database SQLite bersih.
- Fallback terkontrol ke MySQL terisolasi.
- Reseed dan pemeriksaan akun/role/skripsi.
- Test autentikasi Google institusi dan onboarding pengguna pertama.

**Hasil awal dan risiko**

- SQLite bersih gagal karena schema squash tidak mencakup seluruh struktur yang dibutuhkan suite.
- Alur awal mengasumsikan akun sudah tersedia. Requirement dikonfirmasi ulang: pengguna domain institusi yang valid harus dapat dibuat saat login Google pertama.
- Risiko: hasil test tidak deterministik jika fixture tidak merepresentasikan fase/role/status yang diuji.

**Owner/remediasi**

- Owner utama: **Database & CRUD**.
- Dukungan auth: task auth/general troubleshooting.

**File/ringkasan fix**

- [`database/schema/sqlite-schema.sql`](../../../../database/schema/sqlite-schema.sql) — schema test terkompilasi.
- [`database/seeders/DatabaseSeeder.php`](../../../../database/seeders/DatabaseSeeder.php) — fixture role/workflow deterministik.
- [`app/Http/Controllers/Auth/GoogleController.php`](../../../../app/Http/Controllers/Auth/GoogleController.php) — domain institusi dan onboarding first login.
- [`app/Models/User.php`](../../../../app/Models/User.php) — dukungan atribut/role pengguna.
- [`tests/Feature/Auth/GoogleSecurityTest.php`](../../../../tests/Feature/Auth/GoogleSecurityTest.php) — domain, normalization, first login, least privilege, duplicate prevention, session.

**Rerun/hasil tercatat**

- Seed tercatat: 34 pengguna dan 55 migrasi.
- Debug nonaktif dan redirect login HTTP normal.
- First-time valid-domain user tercakup dan lulus pada suite terkini.

**Status final:** **LULUS**

**Batasan residual**

TestSprite membutuhkan fixture live yang tepat; drift status dapat menghasilkan UI berbeda walaupun guard aplikasi benar. Kasus konkret terlihat pada TC029 dan ditutup dengan seed idempotent.

---

### Poin 2 — Baseline statis, suite, dan build

**Tujuan/ruang lingkup**

Mendapat baseline regresi menyeluruh, route rendering, Blade, dan frontend build sebelum security/workflow probes berikutnya.

**Metode**

- `composer test`.
- `npm run build`.
- Route registration/render tests.
- Triage failure terhadap route/controller/view yang berlaku saat ini.

**Hasil awal dan risiko**

- Baseline awal: **19 test lulus, 74 gagal**.
- Penyebab bukan satu defect: schema test, test stale terhadap flow lama, shared Blade tidak lengkap, icon partial hilang, dan assertion UI lama.
- Risiko terbesar: “memperbaiki aplikasi” agar sesuai test lama. Triage memilih flow aktif sebagai sumber kebenaran.

**Owner/remediasi**

- **General Troubleshooting**: baseline triage dan route tests.
- **UI/UX**: icon partial/UI-owned rendering.
- **Database & CRUD**: schema/flow authoritative.

**File/ringkasan fix**

- [`resources/views/partials/tables/data-table.blade.php`](../../../../resources/views/partials/tables/data-table.blade.php) — `@endif` yang hilang dan shared table.
- [`resources/views/partials/icons/chat.blade.php`](../../../../resources/views/partials/icons/chat.blade.php), [`grid.blade.php`](../../../../resources/views/partials/icons/grid.blade.php), [`help.blade.php`](../../../../resources/views/partials/icons/help.blade.php), [`upload.blade.php`](../../../../resources/views/partials/icons/upload.blade.php) — partial UI.
- [`tests/Feature/RouteRegistrationTest.php`](../../../../tests/Feature/RouteRegistrationTest.php) — route/view authoritative.
- Test auth, bimbingan, final approval, penilaian, cross-role, dan Dosen CRUD diselaraskan dengan flow aktif.

**Rerun/hasil tercatat**

- Triage pertama: **92 lulus, 1 gagal, 291 assertion**.
- Satu failure tersisa: assertion RouteRegistration terhadap heading lama; UI aktual benar.
- Setelah assertion diperbarui: **93 lulus, 298 assertion, 0 failure**.

**Status final:** **LULUS**

**Batasan residual**

Working tree berisi perubahan gabungan banyak task; bukti menggunakan hasil eksekusi, bukan asumsi clean branch.

---

### Poin 3 — Infrastruktur dan eksekusi browser TestSprite

**Tujuan/ruang lingkup**

Membuktikan TestSprite MCP, tunnel, browser remote, sinkronisasi artefak, dan penggunaan kredit Starter secara terkendali.

**Metode**

- Generate standardized PRD/frontend plan.
- Menjalankan batch kecil, bukan seluruh ±30/50 case sekaligus.
- Menunggu worker remote selesai walaupun terminal lokal tetap 0/N.
- Skenario library publik: TC019, lalu TC018/TC022/TC030/TC040.

**Hasil awal dan risiko**

- Generate/execute sempat tampak gagal atau tertahan karena tunnel Yamux dan sinkronisasi status.
- Dashboard menunjukkan success saat terminal lokal belum sinkron.
- Risiko konsumsi kredit jika seluruh plan dijalankan sekaligus; saldo saat kampanye terbatas.

**Owner/remediasi**

- Owner: **TestSprite task**.
- Tidak ada perubahan perilaku aplikasi.

**File/ringkasan**

- [`testsprite_tests/testsprite_frontend_test_plan.json`](../../../../testsprite_tests/testsprite_frontend_test_plan.json)
- [`testsprite_tests/TC018_Open_a_thesis_detail_from_the_library.py`](../../../../testsprite_tests/TC018_Open_a_thesis_detail_from_the_library.py)
- [`testsprite_tests/TC019_Browse_the_public_thesis_library.py`](../../../../testsprite_tests/TC019_Browse_the_public_thesis_library.py)
- [`testsprite_tests/TC022_Browse_public_thesis_listings.py`](../../../../testsprite_tests/TC022_Browse_public_thesis_listings.py)
- [`testsprite_tests/TC030_Open_a_public_thesis_detail.py`](../../../../testsprite_tests/TC030_Open_a_public_thesis_detail.py)
- [`testsprite_tests/TC040_Return_to_the_library_after_viewing_a_thesis.py`](../../../../testsprite_tests/TC040_Return_to_the_library_after_viewing_a_thesis.py)

**Rerun/hasil tercatat**

- **5/5 browser test library lulus**.
- Dashboard yang pernah dikonfirmasi pengguna: `https://www.testsprite.com/dashboard/mcp/tests/22c1e474-d6fb-4d5b-a4f6-606a6955705b`.
- Artefak lokal tersinkron setelah worker berhenti natural.

**Status final:** **LULUS**

**Batasan residual**

- Artefak lokal terakhir sekarang berisi rerun TC029, bukan keseluruhan riwayat dashboard library.
- Progres terminal TestSprite tidak selalu merefleksikan progres remote secara real time.
- Browser TestSprite hanya dipakai pada subset bernilai tinggi; bukan bukti tunggal seluruh kampanye.

---

### Poin 4 — Autentikasi

**Tujuan/ruang lingkup**

Memverifikasi password login, Google institusi, first-time registration, session rotation, logout, throttle, disabled user, disclosure, intended redirect, dan role dashboard.

**Metode**

- Suite `AuthenticationTest` dan `GoogleSecurityTest`.
- Coverage eksplisit ditambah untuk invalid password, logout, rate limit, soft-deleted user, dan enumeration.

**Hasil awal dan risiko**

Coverage eksplisit awal belum mencakup seluruh negative path. Tidak ada bypass auth yang dikonfirmasi.

**Owner/remediasi**

- **General Troubleshooting/Auth**.

**File/ringkasan fix**

- [`app/Http/Controllers/Auth/AuthenticatedSessionController.php`](../../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php)
- [`app/Http/Controllers/Auth/GoogleController.php`](../../../../app/Http/Controllers/Auth/GoogleController.php)
- [`tests/Feature/Auth/AuthenticationTest.php`](../../../../tests/Feature/Auth/AuthenticationTest.php)
- [`tests/Feature/Auth/GoogleSecurityTest.php`](../../../../tests/Feature/Auth/GoogleSecurityTest.php)

Domain valid adalah `@widyakarya.ac.id`; uppercase dinormalisasi; suffix spoof/subdomain/foreign domain ditolak; user baru diberi role least privilege; sesi diregenerasi; external intended URL ditolak.

**Rerun/hasil tercatat**

- Penutupan poin: **32 test, 153 assertion**.
- Gate 20 Juli: seluruh test auth/Google lulus dalam **133/442**.

**Status final:** **LULUS**

**Batasan residual**

OAuth provider nyata bergantung konfigurasi Google production; suite menggunakan mocking/provider response terkendali.

---

### Poin 5 — Otorisasi berbasis peran

**Tujuan/ruang lingkup**

Mencegah akses silang Kaprodi, Dosen, dan Mahasiswa, termasuk direct URL, forged write, serta perubahan role di DB setelah sesi terbentuk.

**Metode**

- `RoleMiddlewareTest`.
- `CrossRoleTest`.
- Route authorization dengan record nyata.
- Forged writes dan stale-role session.

**Hasil awal dan risiko**

Probe awal memakai ID palsu sehingga route model binding mengembalikan 404 sebelum middleware; hasil itu tidak membuktikan role guard.

**Owner/remediasi**

- **General Troubleshooting** dan **Database & CRUD**.

**File/ringkasan fix**

- [`tests/Feature/Middleware/RoleMiddlewareTest.php`](../../../../tests/Feature/Middleware/RoleMiddlewareTest.php) — semua pasangan role, forged writes, database role recheck.
- [`tests/Feature/Integration/CrossRoleTest.php`](../../../../tests/Feature/Integration/CrossRoleTest.php)
- [`bootstrap/app.php`](../../../../bootstrap/app.php) dan route files per role.

Fixture diubah menggunakan record nyata agar 403 menguji guard, bukan 404 binding.

**Rerun/hasil tercatat**

- **36 test, 95 assertion**.
- Gate 20 Juli: seluruh matrix role lulus.

**Status final:** **LULUS**

**Batasan residual**

404 tetap sengaja dipakai untuk beberapa ownership-sensitive resource agar tidak membocorkan keberadaan record; ini berbeda dari role boundary 403.

---

### Poin 6 — Ownership dan IDOR

**Tujuan/ruang lingkup**

Mencegah pengguna yang memiliki role sah mengubah record milik peer pada role yang sama.

**Metode**

- Direct PUT/DELETE terhadap bimbingan.
- Upload/submission/grading ownership tests.
- Assigned vs owner distinction.

**Hasil awal dan defect**

Dosen yang assigned pada skripsi dapat memperbarui atau menghapus bimbingan yang dibuat Dosen lain. Assignment ke skripsi saja terlalu luas.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix**

- [`app/Http/Controllers/Dosen/BimbinganController.php`](../../../../app/Http/Controllers/Dosen/BimbinganController.php) — `update()` dan `destroy()` mewajibkan `bimbingan.reviewer_id` sama dengan Dosen terautentikasi setelah skripsi assignment check.
- [`tests/Feature/Dosen/BimbinganControllerTest.php`](../../../../tests/Feature/Dosen/BimbinganControllerTest.php) — regresi PUT dan DELETE cross-owner.

**Rerun/hasil tercatat**

- Focused: **3 test, 14 assertion**.
- Dosen suites + RoleMiddleware: **26 test, 47 assertion**.
- Rekap kampanye P6: **35 test, 95 assertion**; cross-owner update/delete 403.

**Status final:** **LULUS**

**Batasan residual**

Guard spesifik bimbingan memakai 403; TC029 sidang request memakai 404 untuk non-owner agar resource tidak terungkap.

---

### Poin 7 — CSRF

**Tujuan/ruang lingkup**

Membuktikan POST/PUT/DELETE dan login tanpa token ditolak ketika middleware CSRF aktif, serta token valid tetap diproses.

**Metode**

- Direct requests dengan middleware CSRF aktif.
- Kontrol token valid.
- Pemeriksaan form Blade.

**Hasil awal dan risiko**

Tidak ditemukan defect aplikasi. “Missing CSRF token pada login” yang terlihat dalam probe harus dibedakan dari form browser aktual: request palsu tanpa token memang diharapkan 419.

**Owner/remediasi**

- Owner verifikasi: **TestSprite/Security campaign**.
- Patch: **tidak ada**.

**Rerun/hasil tercatat**

- Request forged tanpa token: 419.
- Token valid: diproses.

**Status final:** **LULUS**

**Batasan residual**

Jumlah test/assertion khusus P7 tidak tercatat pada task history yang tersedia; claim dibatasi pada hasil perilaku.

---

### Poin 8 — Validasi input dan mass assignment

**Tujuan/ruang lingkup**

Mencegah privileged fields, ownership changes, role escalation, workflow/status forgery, serta nilai di luar batas.

**Metode**

- Malformed/boundary/forged field tests lintas role.
- Real records dan direct write.

**Hasil awal dan risiko**

Coverage ownership/status/role/grade boundary belum eksplisit. Tidak ada exploit terkonfirmasi sebelum test ditambah.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix/test**

- [`tests/Feature/Kaprodi/DosenCrudTest.php`](../../../../tests/Feature/Kaprodi/DosenCrudTest.php) — forged privileged role diabaikan.
- [`tests/Feature/Mahasiswa/SkripsiControllerTest.php`](../../../../tests/Feature/Mahasiswa/SkripsiControllerTest.php) — forged owner/workflow fields diabaikan.
- [`tests/Feature/Dosen/PenilaianControllerTest.php`](../../../../tests/Feature/Dosen/PenilaianControllerTest.php) — grade range.
- [`tests/Feature/Middleware/RoleMiddlewareTest.php`](../../../../tests/Feature/Middleware/RoleMiddlewareTest.php) — forged cross-role writes.

**Rerun/hasil tercatat**

- **71 test, 275 assertion**.
- Privileged escalation dan out-of-bound input ditolak/diabaikan sesuai contract.

**Status final:** **LULUS**

**Batasan residual**

Tidak ada fuzzing corpus eksternal; coverage berbasis trust boundary dan field kritis yang ditemukan di code review.

---

### Poin 9 — Keamanan upload dan download

**Tujuan/ruang lingkup**

Menguji MIME, ukuran, extension/path, phase, private storage, ownership upload, dan authorization download.

**Metode**

- Direct forged upload.
- Invalid MIME/oversize.
- Unauthorized owner.
- Forged phase/path traversal style input.

**Hasil awal dan defect**

Endpoint proposal menerima arbitrary string phase, sehingga Mahasiswa dapat mengirim `skripsi_final` melalui jalur proposal.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix**

- [`app/Http/Controllers/Mahasiswa/DocumentVersionController.php`](../../../../app/Http/Controllers/Mahasiswa/DocumentVersionController.php) — validasi phase diubah dari string bebas menjadi exact `in:proposal`.
- [`tests/Feature/Mahasiswa/DocumentVersionControllerTest.php`](../../../../tests/Feature/Mahasiswa/DocumentVersionControllerTest.php) — forged phase ditolak dan zero record.
- Student document path/storage service yang sudah ada tetap dipakai; tidak membuat abstraksi baru.

**Rerun/hasil tercatat**

- Focused: **4 test, 8 assertion**.
- Mahasiswa + CrossRole: **31 test, 84 assertion**.
- Rekap kampanye P9: **26 test, 77 assertion** pada scope yang dilaporkan.

**Status final:** **LULUS**

**Batasan residual**

Malware content scanning/antivirus tidak tercatat sebagai fitur; validasi yang terbukti adalah MIME, size, phase, owner, path, dan storage boundary.

---

### Poin 10 — XSS dan escaping

**Tujuan/ruang lingkup**

Mencegah stored XSS, reflected rendering, dan DOM XSS pada library, shared table, dan suggestion search.

**Metode**

- Payload tersimpan pada konten library.
- Audit semua caller shared table.
- Audit JS sink untuk suggestion.
- Build dan route render.

**Hasil awal dan defect**

Shared data table merender semua cell sebagai raw HTML. Search suggestion membuat sink DOM dari data dinamis.

**Owner/remediasi**

- **UI/UX**.

**File/ringkasan fix**

- [`resources/views/partials/tables/data-table.blade.php`](../../../../resources/views/partials/tables/data-table.blade.php) — ordinary cells di-escape; server-built action markup memakai `HtmlString`.
- [`resources/js/app.js`](../../../../resources/js/app.js) — suggestion nodes memakai DOM creation/`textContent`.
- [`tests/Feature/LibrarySecurityTest.php`](../../../../tests/Feature/LibrarySecurityTest.php) — stored user content di-escape.

**Rerun/hasil tercatat**

- LibrarySecurity + RouteRegistration + Example: **4 test, 17 assertion**.
- Full pada saat poin: **108 test, 351 assertion**.
- `npm run build` lulus.

**Status final:** **LULUS**

**Batasan residual**

CSP belum aktif; P12 mencatat strategi nonce/hash migration sebagai pekerjaan terpisah, bukan hot patch.

---

### Poin 11 — Query dan sort manipulation

**Tujuan/ruang lingkup**

Memastikan SQL-like input diperlakukan sebagai data dan sort/filter tidak membentuk query direction berbahaya.

**Metode**

- `QuerySecurityTest`.
- Public search payload.
- Index dan show path dengan malformed sort direction.

**Hasil awal dan defect**

Malformed sort direction menyebabkan 500. Tidak ada bukti SQL execution, tetapi server error tetap defect reliability/security.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix**

- [`app/Http/Controllers/Kaprodi/FormatPenilaianController.php`](../../../../app/Http/Controllers/Kaprodi/FormatPenilaianController.php) — `direction` dan `assigned_direction` dinormalisasi hanya `asc`/`desc`, malformed fallback ke `asc`.
- [`tests/Feature/QuerySecurityTest.php`](../../../../tests/Feature/QuerySecurityTest.php) — index/show regressions.

**Rerun/hasil tercatat**

- Focused: **3 test, 4 assertion**.
- Kaprodi + RouteRegistration: **15 test, 58 assertion**.
- Rekap P11: **18 test, 62 assertion**.

**Status final:** **LULUS**

**Batasan residual**

Fallback `asc` disengaja; malformed value tidak menghasilkan error kepada pengguna.

---

### Poin 12 — Security headers dan session/browser posture

**Tujuan/ruang lingkup**

Memberi baseline browser hardening pada normal response, redirect, dan error; mendokumentasikan HTTPS cookie/HSTS/CSP posture.

**Metode**

- Login, redirect, dan 404 response assertions.
- Audit environment production requirements.

**Hasil awal dan defect**

Header baseline belum ada secara global.

**Owner/remediasi**

- **General Troubleshooting**.

**File/ringkasan fix**

- [`app/Http/Middleware/AddSecurityHeaders.php`](../../../../app/Http/Middleware/AddSecurityHeaders.php)
- [`bootstrap/app.php`](../../../../bootstrap/app.php)
- [`.env.example`](../../../../.env.example)
- [`tests/Feature/HttpSecurityHeadersTest.php`](../../../../tests/Feature/HttpSecurityHeadersTest.php)

Header:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`

**Rerun/hasil tercatat**

- Focused: **2 test, 19 assertion**.
- Auth + route: **34 test, 166 assertion**.
- Full saat poin: **113 test, 374 assertion**.
- Rekap scope P12 dalam laporan awal: **18 test, 83 assertion**.

**Status final:** **LULUS TERBATAS**

**Batasan residual**

- `SESSION_SECURE_COOKIE=true` hanya pada HTTPS.
- HSTS harus dikonfigurasi di web server/reverse proxy HTTPS.
- CSP ditunda karena inline scripts/styles masih luas; rollout aman membutuhkan nonce/hash migration.

---

### Poin 13 — Workflow Mahasiswa

**Tujuan/ruang lingkup**

Memvalidasi lifecycle Mahasiswa: skripsi milik sendiri, proposal, dokumen, bimbingan/revisi, nilai, final submission, dan non-skripsi.

**Metode**

- Seluruh suite Mahasiswa.
- Guard final prematur, ownership, MIME, phase, dan penyimpanan final.

**Hasil awal dan risiko**

Guard transisi final sebelum nilai lengkap belum diuji langsung.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix/test**

- [`app/Http/Controllers/Mahasiswa/FinalSubmissionController.php`](../../../../app/Http/Controllers/Mahasiswa/FinalSubmissionController.php)
- [`tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php`](../../../../tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php)
- [`tests/Feature/Mahasiswa/SkripsiControllerTest.php`](../../../../tests/Feature/Mahasiswa/SkripsiControllerTest.php)
- [`tests/Feature/Mahasiswa/NonSkripsiControllerTest.php`](../../../../tests/Feature/Mahasiswa/NonSkripsiControllerTest.php)

Regression memastikan form/submit final ditolak sebelum grade lengkap dan file/fase tidak berubah.

**Rerun/hasil tercatat**

- **32 test, 88 assertion**.
- Gate 20 Juli: lima final-submission cases dan seluruh suite Mahasiswa lulus.

**Status final:** **LULUS**

**Batasan residual**

Test browser end-to-end seluruh workflow Mahasiswa tidak dijalankan sebagai satu chain TestSprite; bukti utama server-side Pest.

---

### Poin 14 — Workflow Dosen

**Tujuan/ruang lingkup**

Memvalidasi assignment, proposal queue, bimbingan, sidang request, grading/publish lock, final approval read-only, dan unlock-related paths.

**Metode**

- Seluruh Dosen suite.
- Direct submission oleh `pembimbing_1`.
- Negative path reviewer non-advisor.

**Hasil awal dan risiko**

Coverage submit permohonan sidang kritis belum ada.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix/test**

- [`app/Http/Controllers/Dosen/SidangRequestController.php`](../../../../app/Http/Controllers/Dosen/SidangRequestController.php)
- [`tests/Feature/Dosen/SidangRequestControllerTest.php`](../../../../tests/Feature/Dosen/SidangRequestControllerTest.php)
- [`tests/Feature/Dosen/BimbinganControllerTest.php`](../../../../tests/Feature/Dosen/BimbinganControllerTest.php)
- [`tests/Feature/Dosen/PenilaianControllerTest.php`](../../../../tests/Feature/Dosen/PenilaianControllerTest.php)
- [`tests/Feature/Dosen/FinalApprovalControllerTest.php`](../../../../tests/Feature/Dosen/FinalApprovalControllerTest.php)

**Rerun/hasil tercatat**

- Penutupan P14: **17 test, 49 assertion**.
- Gate 20 Juli: submit, non-advisor block, totals, pending cancellation, dan processed-state cancellation semuanya lulus.

**Status final:** **LULUS**

**Batasan residual**

TC029 cancellation adalah retest tambahan setelah P1–P20; kronologi khusus ada di Bagian 5.

---

### Poin 15 — Workflow Kaprodi dan penyelesaian legacy

**Tujuan/ruang lingkup**

Memastikan penyelesaian normal membutuhkan bukti workflow lengkap, sambil mendukung pencatatan skripsi lulusan lama pada periode nonaktif/tertutup.

**Metode**

- Kaprodi workflow suite.
- Direct-action transition probes.
- Valid/invalid legacy upload, active/inactive period, role boundary.

**Hasil awal dan defect**

Final review endpoint dapat menyelesaikan skripsi tanpa memastikan dokumen final terbaru, reviewer assignment, published grades, dan approvals lengkap.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix**

- [`app/Http/Controllers/Kaprodi/FinalReviewController.php`](../../../../app/Http/Controllers/Kaprodi/FinalReviewController.php) — normal completion guard dan Kaprodi-only legacy completion.
- [`routes/web/kaprodi.php`](../../../../routes/web/kaprodi.php)
- [`resources/views/kaprodi/skripsi/show.blade.php`](../../../../resources/views/kaprodi/skripsi/show.blade.php)
- [`tests/Feature/Kaprodi/WorkflowControllerTest.php`](../../../../tests/Feature/Kaprodi/WorkflowControllerTest.php)

Normal path mensyaratkan fase `review_dokumen_final`, latest `skripsi_final`, reviewer assignments, published `sidang_skripsi` grade setiap assignment, dan approval latest document. Legacy path:

- hanya Kaprodi;
- hanya periode nonaktif/closed;
- PDF/DOC/DOCX maksimum 20 MB;
- phase dipaksa `skripsi_final`;
- private local storage;
- `uploaded_by` Kaprodi;
- active period ditolak.

**Rerun/hasil tercatat**

- Focused: **6 test, 19 assertion**.
- Relevant suites: **55 test, 161 assertion**.
- Full saat poin: **122 test, 404 assertion**.

**Status final:** **LULUS**

**Batasan residual**

Legacy completion adalah exception terkontrol, bukan bypass normal workflow; operasional production harus membatasi pemakaian untuk arsip historis.

---

### Poin 16 — Lifecycle lintas peran sampai library

**Tujuan/ruang lingkup**

Membuktikan handoff Mahasiswa → Dosen → Kaprodi → selesai → library publik.

**Metode**

- Integration/cross-role tests.
- Workflow transition.
- Public library assertion.

**Hasil awal dan risiko**

Coverage completion-to-library belum eksplisit. Manual QA juga mencatat skripsi final yang approved tidak otomatis muncul di library.

**Owner/remediasi**

- **Database & CRUD**.

**File/ringkasan fix/test**

- [`app/Http/Controllers/LibraryController.php`](../../../../app/Http/Controllers/LibraryController.php)
- [`app/Models/Skripsi.php`](../../../../app/Models/Skripsi.php)
- [`tests/Feature/Integration/CrossRoleTest.php`](../../../../tests/Feature/Integration/CrossRoleTest.php)
- [`tests/Feature/Kaprodi/WorkflowControllerTest.php`](../../../../tests/Feature/Kaprodi/WorkflowControllerTest.php)
- [`tests/Feature/LibrarySecurityTest.php`](../../../../tests/Feature/LibrarySecurityTest.php)

**Rerun/hasil tercatat**

- **21 test, 66 assertion**.
- Skripsi selesai terlihat publik; fake DB-backed slug tetap 404.

**Status final:** **LULUS**

**Batasan residual**

Browser library P3 menguji browse/detail, sedangkan completion-to-library terutama dibuktikan oleh integration/workflow tests.

---

### Poin 17 — Notifikasi

**Tujuan/ruang lingkup**

Memastikan recipient scope, unread/read/all-read, normalized link, producer lintas role, dan ownership notifikasi.

**Metode**

- Notification feature tests.
- Cross-role producers.
- Attempt membaca notifikasi pengguna lain.

**Hasil awal**

Tidak ada defect terkonfirmasi.

**Owner/remediasi**

- Owner verifikasi: **General Troubleshooting**.
- Patch perilaku: **tidak ada**.

**File/bukti**

- [`tests/Feature/NotificationControllerTest.php`](../../../../tests/Feature/NotificationControllerTest.php)
- [`tests/Feature/Integration/CrossRoleTest.php`](../../../../tests/Feature/Integration/CrossRoleTest.php)

**Rerun/hasil tercatat**

- **7 test, 22 assertion** pada scope kampanye.
- Gate 20 Juli: scoped creation/link normalization, cross-user denial, mark-one/all lulus.

**Status final:** **LULUS**

**Batasan residual**

Test membuktikan storage/controller behavior; realtime transport/websocket production bukan claim yang dibuat dokumen ini.

---

### Poin 18 — Aksesibilitas dan UI

**Tujuan/ruang lingkup**

Memberi semantics dialog, accessible names, keyboard focus, Tab trap, Escape, restoration, dan menjaga build/render.

**Metode**

- Audit seluruh `.acss-modal`.
- Regression markup.
- Blade cache dan Vite build.
- Percobaan browser runtime.

**Hasil awal dan defect**

- 27 modal aktual pada 17 template tidak memiliki semantics dialog lengkap.
- Focus trap, Escape close, dan focus restoration tidak konsisten.

**Owner/remediasi**

- **UI/UX**.

**File/ringkasan fix**

- 27 modal mendapat `role="dialog"`, `aria-modal="true"`, dan `aria-labelledby` dengan heading ID stabil/dinamis.
- [`resources/js/app.js`](../../../../resources/js/app.js) — satu `initModalAccessibility()` shared: remember trigger, first meaningful focus/fallback, Tab/Shift+Tab trap, Escape top dismissible modal, trigger focus restoration; menghormati disabled close/`aria-busy`.
- [`tests/Feature/AccessibilityMarkupTest.php`](../../../../tests/Feature/AccessibilityMarkupTest.php)

**Rerun/hasil tercatat**

- Markup phase: Accessibility + RouteRegistration + Example **4 test, 15 assertion**.
- Keyboard continuation: full **126 test, 418 assertion**.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` lulus.

**Status final:** **LULUS TERBATAS**

**Batasan residual**

Browser runtime bootstrap gagal dengan `Cannot redefine property: process`. Tidak ada DOM-unit runner di repo dan dependency baru tidak ditambahkan. Karena itu keyboard behavior telah diimplementasikan dan build/regression hijau, tetapi belum ada rekaman browser accessibility audit penuh.

---

### Poin 19 — Kesiapan produksi, route uniqueness, dan cache

**Tujuan/ruang lingkup**

Memastikan route/config/event/view cache dapat dibangun dan route names unik.

**Metode**

- `php artisan route:cache`.
- `php artisan optimize`.
- Route uniqueness regression.
- `optimize:clear`.

**Hasil awal dan defect**

Nama route `add-periode` duplikat memblokir route cache/optimize. Ada pula deklarasi `formats.remove-periode` redundan.

**Owner/remediasi**

- **General Troubleshooting**.

**File/ringkasan fix**

- [`routes/web/kaprodi.php`](../../../../routes/web/kaprodi.php) — canonical route mempertahankan `kaprodi.document-templates.add-periode`; legacy route menjadi `.legacy`; duplicate formats route dihapus.
- [`tests/Feature/RouteRegistrationTest.php`](../../../../tests/Feature/RouteRegistrationTest.php) — unique route names dan exact URLs.

**Rerun/hasil tercatat**

- RouteRegistration + Kaprodi Workflow: **9 test, 37 assertion**.
- `php artisan route:cache` lulus.
- `APP_ENV=testing APP_DEBUG=true php artisan optimize` lulus.
- Full saat poin: **127 test, 421 assertion**.
- Gate 20 Juli: optimize kembali lulus untuk config/events/routes/views dan dibersihkan sesudahnya.

**Status final:** **LULUS**

**Batasan residual**

Percobaan `optimize:clear` awal pernah menyentuh cache MySQL dalam sandbox; penggunaan `CACHE_STORE=array` menjadi prosedur aman untuk verifikasi lokal.

---

### Poin 20 — Gerbang rilis final dan audit dependensi

**Tujuan/ruang lingkup**

Menutup seluruh gate: suite penuh, build, optimize/cache, Composer/npm audit, Composer validation, dan diff hygiene.

**Metode**

- `composer audit --locked`.
- Controlled dependency resolution/update.
- `composer validate --strict`.
- `composer test`.
- `npm run build` dan audit.
- Laravel optimize/clear.
- `git diff --check`.

**Hasil awal dan defect/risk**

Audit awal menemukan **20 advisory pada 11 package**. `laravel/socialite` memakai wildcard `*`, menghasilkan warning validasi dan constraint yang terlalu luas.

**Owner/remediasi**

- **General Troubleshooting**.

**File/ringkasan fix**

- [`composer.json`](../../../../composer.json) — `laravel/socialite` dari `*` menjadi `^5.27`.
- [`composer.lock`](../../../../composer.lock) — 44 patch/minor resolver-coupled updates; tidak ada install/remove dan tidak ada major upgrade yang disengaja.

Versi security-relevant yang tercatat setelah update:

- `laravel/framework` v13.12.0
- `guzzlehttp/guzzle` 7.12.1
- `guzzlehttp/promises` 2.5.1
- `guzzlehttp/psr7` 2.12.1
- `phpseclib/phpseclib` 3.0.54
- `symfony/http-foundation` v8.0.13
- `symfony/http-kernel` v8.0.12
- `symfony/mailer` v8.0.12
- `symfony/mime` v8.0.12
- `symfony/polyfill-intl-idn` v1.38.1
- `symfony/routing` v8.0.13
- `symfony/yaml` v8.0.12

**Rerun/hasil tercatat saat penutupan kampanye**

- `composer audit --locked`: zero advisory.
- `composer validate --strict`: valid; wildcard warning hilang.
- `composer test`: **127 test, 421 assertion**.
- `npm run build`: 56 modules; lulus dengan warning runtime image.
- Laravel optimize/clear: lulus.
- `git diff --check`: lulus.

**Verifikasi ulang 20 Juli 2026**

- `composer test`: **133 test, 442 assertion**.
- `composer audit --locked`: **No security vulnerability advisories found**.
- `npm audit --omit=dev`: **found 0 vulnerabilities**.
- `composer validate --strict`: valid.
- `npm run build`: Vite 8.0.8, 56 modules, lulus.
- Laravel optimize dan optimize:clear: lulus.
- `git diff --check`: lulus.

**Status final:** **LULUS**

**Batasan residual**

- Warning `/images/login-wave.jpg` tetap non-blocking dan di-resolve saat runtime.
- Post-update hook `php artisan boost:update` pernah keluar 1 karena Boost belum diinisialisasi dengan `boost:install`; dependency install, audit, test, build, dan optimize tetap berhasil. Boost setup tidak diperluas karena di luar scope.

## 4. Cakupan keamanan lintas poin

| Kontrol | Poin | Bukti utama | Hasil |
|---|---:|---|---|
| Institutional authentication | 1, 4 | Google domain strict match, first login, least privilege, session rotation | Lulus |
| Password auth/session/throttle | 4 | Invalid password, logout invalidation, rate limit, soft delete, enumeration | Lulus |
| Role authorization | 5 | Full role matrix, direct URL, forged write, stale role | Lulus |
| Ownership/IDOR | 6, 9, 14 | Bimbingan owner, document owner, sidang request owner | Lulus |
| CSRF | 7 | Tokenless mutations 419; valid token control | Lulus |
| Mass assignment | 8 | Forged owner/role/workflow/status/grade | Lulus |
| Upload trust boundary | 9, 15 | MIME, size, phase, path, owner, private storage | Lulus |
| Stored/reflected/DOM XSS | 10 | Escaped table/library cells, `HtmlString` allowlist, `textContent` | Lulus |
| SQL/query manipulation | 11 | Search payload as data; sort normalization | Lulus |
| Browser security headers | 12 | nosniff, SAMEORIGIN, referrer policy on normal/redirect/error | Lulus terbatas |
| Workflow integrity | 13–16 | Grade/final guards, assignments, approvals, completion, library | Lulus |
| Notification privacy | 17 | Recipient scope and cross-user denial | Lulus |
| UI accessibility | 18 | Dialog semantics and shared keyboard helper | Lulus terbatas |
| Route/cache integrity | 19 | Unique names; optimize/route cache | Lulus |
| Dependency vulnerabilities | 20 | Composer/npm audit | Zero advisory/vulnerability |

## 5. Retest tambahan TC029 — pembatalan permohonan sidang Dosen

TC029 dilakukan setelah kampanye P1–P20 untuk memverifikasi flow baru:

1. `pembimbing_1` yang mengirim request berstatus `submitted` melihat **Batalkan pengajuan sidang** pada fase `bimbingan_skripsi`.
2. Dosen membatalkan pending request.
3. Aplikasi kembali ke detail skripsi dan pending request hilang.
4. Dosen dapat mengajukan ulang.
5. Processed request tidak dapat dibatalkan.
6. Dosen lain menerima 404.

### 5.1 Run pertama: false positive

TestSprite memberi badge **1/1 PASS**, tetapi generated script:

- tidak mengklik tombol pembatalan;
- berputar antara detail/list;
- hanya meng-assert bahwa current URL tidak kosong.

Kesimpulan: hasil ditolak sebagai bukti feature. Ini defect test generation, bukan bukti aplikasi lulus.

### 5.2 Diagnosis fixture drift

Live MySQL sebelum koreksi:

- skripsi 3: `bimbingan_skripsi`;
- `pembimbing_1`: user 2, `sarah.wijaya@tacloud.test`;
- sidang request 2: lecturer 2/`pembimbing_1`;
- status request: `approved`.

Blade benar menyembunyikan tombol karena status bukan `submitted`. Root cause: **fixture drift**, bukan UI/authorization-condition defect.

### 5.3 Koreksi fixture dan regression

- [`database/seeders/DatabaseSeeder.php`](../../../../database/seeders/DatabaseSeeder.php) sekarang idempotently mengatur request pada seeded `bimbingan_skripsi` menjadi `submitted`, membersihkan approval/rejection fields.
- [`tests/Feature/Dosen/SidangRequestControllerTest.php`](../../../../tests/Feature/Dosen/SidangRequestControllerTest.php) menguji:
  - visible exact label untuk submitting `pembimbing_1`;
  - deletion, redirect, dan session success;
  - lecturer lain 404;
  - processed-state label absent dan DELETE ditolak.

Focused sidang + route setelah koreksi: **8 test, 36 assertion**.

### 5.4 Run valid

TestSprite generated script yang valid:

- login lewat Dosen Pembimbing shortcut;
- membuka `/dosen/skripsi/3`;
- mengklik tombol pembatalan;
- menemukan tombol **Ajukan Permohonan Sidang** setelah pembatalan;
- mengirim **Kirim Permohonan Sidang**;
- kembali ke `/dosen/skripsi/3`;
- meng-assert exact text **Batalkan pengajuan sidang** muncul lagi.

**Hasil:** PASSED  
**Dashboard:** https://www.testsprite.com/dashboard/mcp/tests/d01df0fb-6922-40ee-97be-68c33cc39f0d/f5d15013-838c-4426-92d8-3eb050491f49  
**Test ID:** `f5d15013-838c-4426-92d8-3eb050491f49`  
**Project ID:** `d01df0fb-6922-40ee-97be-68c33cc39f0d`  
**Created:** 18 Juli 2026 04:44 UTC  
**Modified/finished:** 18 Juli 2026 04:53 UTC

**Bukti lokal**

- [`testsprite_tests/TC029_Submit_a_defense_request_for_an_assigned_thesis.py`](../../../../testsprite_tests/TC029_Submit_a_defense_request_for_an_assigned_thesis.py)
- [`testsprite_tests/tmp/test_results.json`](../../../../testsprite_tests/tmp/test_results.json)
- [`testsprite_tests/tmp/raw_report.md`](../../../../testsprite_tests/tmp/raw_report.md)

### 5.5 Gap residual TC029

Generated browser script **tidak membuat assertion eksplisit atas success flash message**. Keberhasilan cancel dibuktikan secara tidak langsung tetapi kuat oleh munculnya tombol pengajuan baru dan keberhasilan resubmission. Session success, redirect, deletion, visibility, processed state, dan cross-lecturer 404 dibuktikan oleh Laravel feature test.

Status TC029: **LULUS DENGAN GAP ASSERTION BROWSER NON-BLOCKING**.

## 6. Addendum blocker — approval reviewer dokumen final tidak dapat diproses

**Tanggal verifikasi:** 20 Juli 2026  
**Status:** **BLOCKER TERKONFIRMASI — NO-GO**  
**Perubahan perilaku aplikasi oleh verifikasi ini:** tidak ada

### 6.1 Skenario dan fixture

Skenario yang diuji adalah alur normal periode aktif:

1. Mahasiswa mengirim dokumen terbaru berfase `skripsi_final`.
2. Sistem membuat satu `final_document_approvals` berstatus `pending` untuk setiap reviewer assignment.
3. Setiap Dosen reviewer membuka detail skripsi dan mencoba approve/reject.
4. Kaprodi mencoba menyelesaikan skripsi.

Live fixture yang dipakai untuk diagnosis browser:

- skripsi `7`;
- document version terbaru `31`, fase `skripsi_final`;
- reviewer user `2`, `sarah.wijaya@tacloud.test`, role `pembimbing_1`;
- reviewer user `4`, `dosen3@tacloud.test`, role `penguji_1`;
- approval `1` dan `2`, keduanya `pending` dan menunjuk document version `31`;
- published `sidang_skripsi` grade tersedia untuk kedua assignment.

Skripsi `7` awalnya sudah `skripsi_selesai`. Fasenya sementara diubah menjadi `review_dokumen_final` hanya untuk diagnosis browser, kemudian dipulihkan ke `skripsi_selesai`. Kedua approval tetap `pending`; tidak ada data approval yang diubah.

### 6.2 Bukti route, controller, dan UI

- `php artisan route:list --name=approval` menghasilkan **tidak ada route yang cocok**.
- [`routes/web/dosen.php`](../../../../routes/web/dosen.php) tidak mendaftarkan endpoint approve/reject dokumen final.
- Tidak ada `app/Http/Controllers/Dosen/FinalApprovalController.php`.
- [`resources/views/dosen/skripsi/show.blade.php`](../../../../resources/views/dosen/skripsi/show.blade.php) hanya menampilkan tabel status approval pada `review_dokumen_final`; tidak ada form/tombol approve atau reject.
- [`tests/Feature/Dosen/FinalApprovalControllerTest.php`](../../../../tests/Feature/Dosen/FinalApprovalControllerTest.php) secara eksplisit mengharapkan `Route::has('dosen.approval.store')` bernilai `false`.
- [`app/Http/Controllers/Mahasiswa/FinalSubmissionController.php`](../../../../app/Http/Controllers/Mahasiswa/FinalSubmissionController.php) membuat/memperbarui approval reviewer menjadi `pending` setelah final submission.
- [`app/Http/Controllers/Kaprodi/FinalReviewController.php`](../../../../app/Http/Controllers/Kaprodi/FinalReviewController.php) mensyaratkan approval untuk document version terbaru, reviewer, dan role yang sama dengan status `approved`.
- UI Kaprodi hanya mencoba final completion. Jika approval belum lengkap, controller mengembalikan: `Dokumen final, nilai, atau persetujuan reviewer belum lengkap.`

Jadi ini bukan desain “Kaprodi-only approval”. Kaprodi hanya memiliki kewenangan completion setelah approval tersedia; Kaprodi juga tidak mempunyai action untuk memutasi approval `pending`.

### 6.3 Verifikasi Laravel/Pest

Suite pendukung terkecil:

```text
php artisan test \
  tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php \
  tests/Feature/Dosen/FinalApprovalControllerTest.php \
  tests/Feature/Kaprodi/WorkflowControllerTest.php
```

Hasil: **14 test lulus, 43 assertion**.

Probe sementara tambahan pada test final submission membuktikan satu submission membuat tepat tiga approval untuk ketiga reviewer assignment dan seluruh statusnya `pending`: **1 test lulus, 7 assertion**. Probe tersebut kemudian dibuang agar verifikasi dokumentasi tidak mengubah source aplikasi/test secara permanen.

Existing workflow success test dapat menyelesaikan skripsi hanya karena fixture test langsung membuat approval `approved`; test itu tidak membuktikan adanya transisi UI/API dari `pending` ke `approved`.

### 6.4 TestSprite browser

TestSprite TC023 dijalankan sebagai diagnostic browser run, tetapi hasilnya **BLOCKED oleh test generation**, bukan hasil fitur:

- generated script memilih option akun kosong;
- script memakai password `password123`, sedangkan seeded password adalah `password`;
- autentikasi Sarah dan Kaprodi gagal dengan `Email atau password tidak sesuai.`;
- karena itu TestSprite tidak mencapai halaman/action target dan tidak boleh dihitung PASS maupun FAIL fitur.

**Dashboard:** https://www.testsprite.com/dashboard/mcp/tests/0006433d-c794-4070-8ef8-fe0230df8792/f0ea6239-1333-4757-94c7-2681f411e533  
**Project ID:** `0006433d-c794-4070-8ef8-fe0230df8792`  
**Test ID:** `f0ea6239-1333-4757-94c7-2681f411e533`  
**Status:** `BLOCKED`  
**Dibuat:** 20 Juli 2026 01:39 UTC  
**Selesai:** 20 Juli 2026 01:43 UTC  
**Video/log:** tersedia melalui dashboard dan field `testVisualization` pada hasil JSON.

Bukti lokal run:

- [`testsprite_tests/TC023_Approve_a_final_document_review.py`](../../../../testsprite_tests/TC023_Approve_a_final_document_review.py)
- [`testsprite_tests/tmp/test_results.json`](../../../../testsprite_tests/tmp/test_results.json)
- [`testsprite_tests/tmp/raw_report.md`](../../../../testsprite_tests/tmp/raw_report.md)

Kegagalan autentikasi TestSprite adalah false-negative lingkungan/generator yang terpisah. Ia tidak membatalkan bukti deterministik route, view, controller, database, dan Pest.

**Rerun setelah login shortcut dikonfirmasi manual:** TestSprite memberi badge **1/1 PASSED**, tetapi hasil ditolak sebagai false positive. Generated script masih mengeksekusi `select_option("")`, tidak mengklik action approve/reject reviewer, hanya membuka modal Kaprodi tanpa mengonfirmasi completion, lalu meng-assert teks `2/2` dan bahwa URL tidak kosong. Verifikasi database sesudah run menunjukkan skripsi tetap `review_dokumen_final` dan kedua approval tetap `pending`.

**Dashboard rerun:** https://www.testsprite.com/dashboard/mcp/tests/415a44da-d735-460c-86dd-4a597094ae48/6d3d4709-f755-41da-a720-ccc1f25f4216  
**Project ID:** `415a44da-d735-460c-86dd-4a597094ae48`  
**Test ID:** `6d3d4709-f755-41da-a720-ccc1f25f4216`  
**Status dashboard:** `PASSED` — **tidak diterima sebagai bukti fitur**  
**Dibuat:** 20 Juli 2026 01:53 UTC  
**Selesai:** 20 Juli 2026 02:06 UTC

### 6.5 Dampak dan keputusan

Alur UI normal **tidak dapat** mengubah seluruh approval reviewer dari `pending` menjadi `approved`. Akibatnya, skripsi periode aktif yang masuk `review_dokumen_final` tidak dapat memenuhi guard completion Kaprodi dan tidak dapat mencapai `skripsi_selesai` melalui workflow normal.

Route legacy-complete Kaprodi untuk periode inactive/closed adalah jalur migrasi data lulusan lama dan bukan solusi bagi workflow periode aktif.

Keputusan: **true launch blocker**, bukan intentional Kaprodi-only design dan bukan fixture drift. Sebelum go-live, aplikasi perlu menyediakan mutation approve/reject yang terotorisasi bagi reviewer (atau mengubah rule bisnis secara konsisten), UI yang dapat dioperasikan, serta regression dan browser retest untuk pending → approved/rejected → Kaprodi completion.

## 7. Indeks bukti lokal

### 7.1 Artefak TestSprite

- [`testsprite_tests/testsprite_frontend_test_plan.json`](../../../../testsprite_tests/testsprite_frontend_test_plan.json)
- [`testsprite_tests/standard_prd.json`](../../../../testsprite_tests/standard_prd.json)
- [`testsprite_tests/tmp/config.json`](../../../../testsprite_tests/tmp/config.json)
- [`testsprite_tests/tmp/code_summary.yaml`](../../../../testsprite_tests/tmp/code_summary.yaml)
- [`testsprite_tests/tmp/test_results.json`](../../../../testsprite_tests/tmp/test_results.json)
- [`testsprite_tests/tmp/raw_report.md`](../../../../testsprite_tests/tmp/raw_report.md)

### 7.2 Test suite utama

- [`tests/Feature/Auth/AuthenticationTest.php`](../../../../tests/Feature/Auth/AuthenticationTest.php)
- [`tests/Feature/Auth/GoogleSecurityTest.php`](../../../../tests/Feature/Auth/GoogleSecurityTest.php)
- [`tests/Feature/Middleware/RoleMiddlewareTest.php`](../../../../tests/Feature/Middleware/RoleMiddlewareTest.php)
- [`tests/Feature/Integration/CrossRoleTest.php`](../../../../tests/Feature/Integration/CrossRoleTest.php)
- [`tests/Feature/Dosen/BimbinganControllerTest.php`](../../../../tests/Feature/Dosen/BimbinganControllerTest.php)
- [`tests/Feature/Dosen/SidangRequestControllerTest.php`](../../../../tests/Feature/Dosen/SidangRequestControllerTest.php)
- [`tests/Feature/Mahasiswa/DocumentVersionControllerTest.php`](../../../../tests/Feature/Mahasiswa/DocumentVersionControllerTest.php)
- [`tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php`](../../../../tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php)
- [`tests/Feature/Kaprodi/WorkflowControllerTest.php`](../../../../tests/Feature/Kaprodi/WorkflowControllerTest.php)
- [`tests/Feature/LibrarySecurityTest.php`](../../../../tests/Feature/LibrarySecurityTest.php)
- [`tests/Feature/QuerySecurityTest.php`](../../../../tests/Feature/QuerySecurityTest.php)
- [`tests/Feature/HttpSecurityHeadersTest.php`](../../../../tests/Feature/HttpSecurityHeadersTest.php)
- [`tests/Feature/AccessibilityMarkupTest.php`](../../../../tests/Feature/AccessibilityMarkupTest.php)
- [`tests/Feature/NotificationControllerTest.php`](../../../../tests/Feature/NotificationControllerTest.php)
- [`tests/Feature/RouteRegistrationTest.php`](../../../../tests/Feature/RouteRegistrationTest.php)

### 7.3 Sumber rekonstruksi

- [`docs/reports/launch-readiness/build_launch_report.py`](../build_launch_report.py) — tabel P1–P20 dan laporan awal.
- [`docs/reports/launch-readiness/Laporan_Kesiapan_Peluncuran_TACLOUD.pdf`](../Laporan_Kesiapan_Peluncuran_TACLOUD.pdf) — kompilasi awal.
- Task TestSprite `019f682d-88e8-7932-bfd3-c1939880b4f3`.
- Task General Troubleshooting `019f6806-0dfe-7f72-a146-bbc4eb845b1b`.
- Task Database & CRUD `019f680a-a4da-79a2-887c-520a0899d5c8`.
- Task UI/UX `019f680a-a221-7360-b5bc-9b1caa23ad2c`.

## 8. Data hilang atau claim yang sengaja tidak dibuat

Untuk mencegah overclaim:

- Tanggal kalender exact setiap P1–P20 tidak tersedia; urutan poin dipertahankan sebagai kronologi.
- Dashboard URLs individual untuk lima library cases tidak seluruhnya tersimpan pada artefak lokal terakhir; dashboard batch yang dikonfirmasi dicantumkan.
- Jumlah test/assertion P7 tidak tercatat secara terpisah.
- Browser E2E bukan metode utama P4–P20; jangan menyebut semua 20 sebagai 20 browser tests.
- Browser accessibility audit penuh P18 tidak tersedia.
- TC029 tidak meng-assert flash success message di browser.
- Tidak ada claim malware scanning file.
- Tidak ada claim realtime/websocket notification transport production.
- Tidak ada claim “zero risk”; hasil menunjukkan gate aplikasi hijau dengan persyaratan konfigurasi deployment dan smoke test produksi.

## 9. Kesimpulan untuk kompilasi laporan

Bukti kampanye historis menunjukkan seluruh P1–P20 ditutup dan gate teknis 20 Juli tetap hijau. Namun, addendum Bagian 6 menemukan coverage gap yang menghasilkan blocker workflow nyata. Karena itu bukti terkini mendukung status **BELUM SIAP DILUNCURKAN / NO-GO**:

- gate 20 Juli 2026 hijau: 133/442, build, optimize, validation, Composer/npm security audit, dan diff check;
- tidak ada advisory dependency terkunci;
- route cache dan production optimize berhasil.
- final submission membuat approval reviewer `pending`;
- tidak ada actor UI/API yang dapat mengubah approval tersebut;
- completion Kaprodi mensyaratkan semuanya `approved`.

Blocker wajib ditutup dan diretest sebelum checklist deployment di bawah dijalankan. Test suite hijau tidak cukup untuk mengesahkan launch karena existing success test menyuntikkan status `approved` langsung ke database.

Syarat non-kode sebelum go-live tetap wajib:

1. HTTPS aktif.
2. `SESSION_SECURE_COOKIE=true`.
3. HSTS pada web server/reverse proxy.
4. Backup database/storage dan rollback plan diuji.
5. Migration/seed production tidak menjalankan fixture test.
6. Smoke test tiga role dan flow prioritas setelah deploy.
7. Monitoring error log, queue, storage, email/notification, OAuth callback, dan health endpoint.
8. CSP direncanakan sebagai hardening lanjutan setelah nonce/hash migration.

## 10. Addendum final — business-flow correction dan hasil rilis

Bagian ini **menimpa status NO-GO pada Bagian 6 dan Kesimpulan Bagian 9**.

Keputusan produk menetapkan bahwa approval dokumen final Dosen bukan bagian workflow aktif. Koreksi diterapkan konsisten:

- pembuatan record `final_document_approvals` dihapus dari final submission;
- guard completion Kaprodi sekarang memerlukan dokumen final terbaru dan nilai `sidang_skripsi` published untuk setiap assignment;
- model, test, dan status UI approval lama dihapus;
- migration `2026_07_20_000000_drop_final_document_approvals_table` dijalankan;
- TC023 lama dikeluarkan karena menguji workflow yang sudah dihapus.

### Bukti final

| Stream | Hasil |
|---|---:|
| Kampanye final | **20/20 skenario ditutup** |
| Accepted TestSprite browser | **7/7** — library 5, cancellation/resubmit 1, sidang dua pembimbing 1 |
| Laravel suite | **133 test / 452 assertion** |
| Focused workflow | **22 test / 90 assertion** |
| Citra manual QA | **38/38 recorded Passed** — Kaprodi 19, Dosen 7, Mahasiswa 7, General 5 |

Sebelas baris Citra memuat remark dan ditandai **PASS***. Later technical verification mencakup path yang dikoreksi, tetapi identical Citra retest tidak terdokumentasi untuk setiap remark.

### Kesimpulan final

**READY IN TESTED SCOPE.** Tidak ada blocker terbuka pada workflow authentication, role, sidang request, grading, final document, completion, migration, atau library yang telah diverifikasi. HTTPS, secure cookie, HSTS, backup/rollback, migration check, dan production smoke test tetap wajib sebagai kontrol deployment.
