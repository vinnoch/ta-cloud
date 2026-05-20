# 1. Project Overview
- **App name**: TA Cloud / `tacloud`
- **Purpose**: Sistem manajemen tugas akhir untuk mengelola proposal, bimbingan, permohonan sidang, penilaian, dokumen final, template dokumen final, dan arsip akademik.
- **Current implementation scope**: Kaprodi, Dosen, dan Mahasiswa sudah aktif dengan alur inti. Realtime notifications, request unlock nilai, final submission, dan template dokumen final sudah masuk ke alur aktif.
- **Tech stack**:
  - **Backend**: Laravel 13, PHP 8.3
  - **Frontend**: Blade, Tailwind CSS v4, Vite, Alpine-style server-rendered UI
  - **Realtime**: Laravel Reverb
  - **Testing**: Pest
  - **Database**: Eloquent ORM untuk `users`, `skripsis`, `periodes`, `bimbingans`, `document_versions`, `grades`, `notifications`, `document_templates`, dan relasi pendukung
- **Auth**: Session auth dengan Breeze package tersedia, login request custom, pembatasan akses via `RoleMiddleware`.

# 2. User Roles & Permissions

| Role | Permissions | Inherits From |
|------|-------------|---------------|
| Kaprodi | CRUD data master, monitor semua skripsi, approve/reject proposal, assign pembimbing/penguji, atur jadwal sidang, approve final review, unlock nilai, kelola template dokumen final | None |
| Dosen | Lihat skripsi yang ditugaskan, isi/ubah/hapus bimbingan, ajukan sidang, input nilai, request unlock nilai | None |
| Mahasiswa | CRUD skripsi sendiri, upload proposal/dokumen revisi, lihat bimbingan, export log bimbingan, final submission, lihat nilai, CRUD non-skripsi | None |

## Role hierarchy summary
- **Kaprodi**: operator akademik global.
- **Dosen**: akses berbasis reviewer assignment.
- **Mahasiswa**: akses berbasis ownership record sendiri.
- **Implementation note**: role dibaca dari `User -> level -> users_level`, bukan inheritance class.

# 3. Functional Requirements

## Kaprodi
- **[DONE]** Dashboard monitoring jumlah skripsi, assignment, sidang, dan distribusi fase.
- **[DONE]** CRUD Dosen, Mahasiswa, Tahun Akademik, Periode, Format Penilaian.
- **[DONE]** Import Dosen dan Mahasiswa via CSV.
- **[DONE]** Monitor proposal, bimbingan, logbook, dan dokumen skripsi.
- **[DONE]** Assign pembimbing dan penguji.
- **[DONE]** Approve / reject proposal submission.
- **[DONE]** Approve / reject sidang request.
- **[DONE]** Review nilai final dan unlock nilai atas request dosen.
- **[DONE]** Kelola template dokumen final (`kaprodi.document-templates.*`).
- **[DONE]** Final review approval flow sudah aktif.
- **[UPDATED]** Phase board dan keputusan akhir masih campuran antara data aktif dan halaman workspace / monitoring.
- **[TODO]** Export rekap global skripsi dari workspace Kaprodi.
- **[TODO]** Publish backend ke library final.

## Dosen
- **[DONE]** Dashboard dan daftar skripsi yang ditugaskan.
- **[DONE]** View detail skripsi yang assigned.
- **[DONE]** Create / update / delete bimbingan.
- **[DONE]** Submit permohonan sidang.
- **[DONE]** Input penilaian sidang dan simpan item nilai.
- **[DONE]** Request unlock nilai setelah grade terkunci.
- **[UPDATED]** Flow notifikasi grading dan unlock berjalan melalui `NotificationService`.
- **[TODO]** Approval/reject final document oleh dosen masih belum jadi workflow terpisah yang eksplisit.

## Mahasiswa
- **[DONE]** Dashboard progres tugas akhir.
- **[DONE]** Full CRUD skripsi sendiri.
- **[DONE]** Upload proposal dan dokumen revisi / versi dokumen.
- **[DONE]** View dan update response bimbingan.
- **[DONE]** Export bimbingan ke CSV dan PDF.
- **[DONE]** Final submission route aktif (`mahasiswa.final-submission.*`, `mahasiswa.skripsi.final-document.*`).
- **[DONE]** View nilai sendiri.
- **[DONE]** CRUD non-skripsi.
- **[UPDATED]** Non-skripsi bukan lagi konsep; sudah jadi controller-backed flow aktif.

## Cross-role / platform
- **[DONE]** Notifications dropdown persistent + realtime.
- **[DONE]** Preview / download dokumen via `DocumentAccessController`.
- **[DONE]** Search route pada workspace tertentu (`dosen.skripsi.search`, `mahasiswa.skripsi.search`).
- **[DONE]** Reusable Blade partial/widget layer aktif di `resources/views/partials`.

# 4. Non-Functional Requirements
- **Performance**:
  - Eager loading dipakai di controller utama.
  - AJAX list partials dipakai pada beberapa table/index pages.
  - Route/controller dipisah berdasarkan bounded workspace role.
- **Security**:
  - Session auth dengan rate-limited login request.
  - Role authorization via `app/Http/Middleware/RoleMiddleware.php`.
  - Ownership / assignment guard di controller Mahasiswa dan Dosen.
  - Dokumen diakses via controller, bukan direct public file URL.
- **Auditability**:
  - Versioning dokumen via `DocumentVersion`.
  - Bimbingan tersimpan per meeting.
  - Notifications persisted di database.
  - Unlock nilai punya request + approval state.
- **Reliability**:
  - Validation aktif pada create/update utama.
  - Test coverage inti sudah ada untuk banyak role flows.
- **Auth stack status**:
  - Breeze package ada.
  - Session auth aktif.
  - Sanctum / API token flow belum jadi layer aktif.

# 5. Data Models

## Core user & access
- **`User`**
  - Fillable: `name`, `email`, `password`, `users_id`, `role`, `nim`, `nidn_nip`
  - Relations: `level`, `skripsi`, `reviewerAssignments`, `reviewedGrades`, `sidangRequests`, `reviewedBimbingans`, `uploadedDocumentVersions`
- **`UserLevel`**
  - Fillable: `users_level`
  - Relations: `users`

## Thesis domain
- **`Skripsi`**
  - Fillable: `student_id`, `periode_id`, `title`, `type`, `current_phase`, `journal_article_url`
  - Relations: `student`, `periode`, `assignments`, `reviewers`, `bimbingans`, `documentVersions`, `grades`, `sidangRequests`, `nonSkripsiRecord`
- **`Periode`**
  - Fillable: `tahun_akademik_id`, `kode_periode`, `semester`, `sk_nomor`, `sk_dokumen_url`, `tgl_mulai`, `tgl_selesai`, `is_aktif`, `status`
  - Relations: `tahunAkademik`, `formats`, `skripsis`
- **`TahunAkademik`**
  - Relations: `periodes`
- **`ReviewerAssignment`**
  - Fillable: `skripsi_id`, `lecturer_id`, `role_type`
  - Relations: `skripsi`, `lecturer`
- **`Bimbingan`**
  - Fillable: `skripsi_id`, `reviewer_id`, `phase`, `meeting_date`, `student_notes`, `lecturer_notes`, `reviewed_version_id`
  - Relations: `skripsi`, `reviewer`, `reviewedVersion`
- **`DocumentVersion`**
  - Fillable: `skripsi_id`, `phase`, `version_number`, `file_path`, `mime_type`, `size`, `uploaded_by`
  - Relations: `skripsi`, `uploader`
- **`NonSkripsiRecord`**
  - Fillable: `skripsi_id`, `summary`, `abstract`, `report_path`, `publication_url`, `final_score`
  - Relations: `skripsi`

## Grading domain
- **`Grade`**
  - Fillable: `skripsi_id`, `format_penilaian_id`, `reviewer_id`, `role_type`, `grade_event`, `status`, `score`, `unlock_requested_at`
  - Relations: `skripsi`, `template`, `reviewer`, `items`
- **`GradeItem`**
  - Relations: `grade`, `itemPenilaian`
- **`FormatPenilaian`**
  - Fillable: `study_program_id`, `nama`, `template_type`, `is_published`, `is_locked`, `is_default`
  - Relations: `studyProgram`, `items`, `periodes`
- **`ItemPenilaian`**
  - Relations: `format`

## Template & support domain
- **`DocumentTemplate`**
  - Used by Kaprodi final document checklist/template flow
  - Managed by `Kaprodi\DocumentTemplateController`
- **`StudyProgram`**
  - Relations: `formatPenilaians`
- **`SidangRequest`**
  - Relations: `skripsi`, `lecturer`

# 6. Routes & Endpoints

## Middleware groups
- `auth + role:kaprodi`
- `auth + role:dosen`
- `auth + role:mahasiswa`
- `auth` only for profile/overview
- public/general for login and library pages

## Kaprodi routes
- **Monitoring & workflow**:
  - `kaprodi.dashboard`
  - `kaprodi.skripsi.index`, `kaprodi.skripsi.show`
  - `kaprodi.skripsi.proposal`, `kaprodi.skripsi.proposal.approve`, `kaprodi.skripsi.proposal.reject`
  - `kaprodi.skripsi.bimbingan`, `kaprodi.skripsi.bimbingan.show`
  - `kaprodi.skripsi.logbook`
  - `kaprodi.skripsi.documents.download`
  - `kaprodi.skripsi.status.update`
  - `kaprodi.skripsi.sidang-schedule.update`
  - `kaprodi.skripsi.final-review.approve`
- **Reviewer management**:
  - `kaprodi.skripsi.reviewers.search`
  - `kaprodi.skripsi.reviewers.store`
  - `kaprodi.skripsi.reviewers.destroy`
  - `kaprodi.skripsi.assign.pembimbing`
  - `kaprodi.skripsi.assign.penguji`
- **Approval queues**:
  - `kaprodi.proposal-submissions.index`
  - `kaprodi.sidang-requests.index`
  - `kaprodi.final-reviews.index`
- **Grading & templates**:
  - `kaprodi.formats.*`
  - `kaprodi.formats.grades.show`
  - `kaprodi.formats.grades.unlock`
  - `kaprodi.document-templates.*`
  - `kaprodi.document-templates.add-periode`
  - `kaprodi.document-templates.remove-periode`
- **Masters / import / recap**:
  - `kaprodi.dosen.*`
  - `kaprodi.mahasiswa.*`
  - `kaprodi.tahun-akademik.*`
  - `kaprodi.periode.*`
  - `kaprodi.import.dosen*`
  - `kaprodi.import.mahasiswa*`
  - `kaprodi.nilai.index`
- **Workspace/static monitoring**:
  - `kaprodi.fase.index`
  - `kaprodi.keputusan.show`
  - `kaprodi.library.index`

## Dosen routes
- `dosen.dashboard`
- `dosen.skripsi.index`, `dosen.skripsi.show`, `dosen.skripsi.search`
- `dosen.bimbingan.store`, `dosen.bimbingan.update`, `dosen.bimbingan.destroy`
- `dosen.sidang-request.index`, `dosen.sidang-request.store`
- `dosen.penilaian.index`, `dosen.penilaian.show`, `dosen.penilaian.store`, `dosen.penilaian.request-unlock`

## Mahasiswa routes
- `mahasiswa.dashboard` / `mahasiswa.progres.index`
- `mahasiswa.skripsi.*`
- `mahasiswa.skripsi.search`
- `mahasiswa.skripsi.proposal.upload`, `mahasiswa.skripsi.proposal.file`
- `mahasiswa.skripsi.documents.store`, `mahasiswa.skripsi.documents.destroy`
- `mahasiswa.skripsi.bimbingan.index`, `mahasiswa.skripsi.bimbingan.update`, `mahasiswa.skripsi.bimbingan.revision.destroy`
- `mahasiswa.skripsi.bimbingan.export.csv`, `mahasiswa.skripsi.bimbingan.export.pdf`
- `mahasiswa.skripsi.final-document.*`
- `mahasiswa.final-submission.*`
- `mahasiswa.skripsi.nilai.index`
- `mahasiswa.non-skripsi.*`

## Shared routes
- `documents.preview`, `documents.download`
- `notifications.index`, `notifications.read`, `notifications.read-all`
- `profile.edit`, `profile.update`
- `library.index`, `library.show`

# 7. Updates & Changes (Current Delta)
- Final submission route gap is closed. Flow now active for Mahasiswa final submission.
- Bimbingan export CSV/PDF added for Mahasiswa.
- Proposal submission queue and approval routes are active for Kaprodi.
- Final review queue and approval route are active for Kaprodi.
- Document template module added for Kaprodi final document management.
- Request unlock nilai now fully visible in current routing and controller graph.
- Reusable widget layer still relies mainly on Blade partials, not Laravel class-based components.
- Search routes now exist for Dosen and Mahasiswa skripsi pages.

# 8. Testing Plan
- **Existing coverage**:
  - Auth / login flow
  - Kaprodi CRUD and import
  - Dosen bimbingan, penilaian, skripsi view
  - Mahasiswa skripsi, bimbingan view, document versions, nilai, non-skripsi
  - Role middleware and cross-role tests
- **Current next additions**:
  - Final submission flow tests
  - Document template controller tests
  - Proposal submission / final review approval tests
  - Notification delivery assertions around unlock requests and final review
  - Export endpoint tests where export logic becomes stable

# 9. Implementation Gap Report

| Requirement | Status | Missing File/Method |
|-------------|--------|---------------------|
| Kaprodi global export / rekap skripsi | Partial | No full export controller / route set for Kaprodi workspace |
| Publish final thesis to public library | Partial | No backend publication workflow connecting final approval to `library` records |
| Explicit dosen final document approve/reject workflow | Gap | No separate dosen-side approval controller/method for final doc state |
| Full business logic for phase board | Partial | `routes/web/kaprodi.php` still exposes workspace monitoring page without full workflow engine |
| Full business logic for keputusan akhir | Partial | Page exists but not yet full end-state automation workflow |
| API/mobile auth and token endpoints | Gap | No active API/Sanctum endpoint layer |
| Final submission automated tests | Gap | No dedicated final submission feature test file yet |
| Document template automated tests | Gap | No dedicated feature test coverage found for `DocumentTemplateController` |

# 10. Reusable UI Components (Blade Partials / Widgets)

Current project mostly uses reusable Blade partials instead of Laravel Components.

## Active reusable partials
- `page-header.blade.php`
- `data-table.blade.php`
- `field.blade.php`
- `metric.blade.php`
- `feature.blade.php`
- `info.blade.php`
- `skripsi/detail-template.blade.php`
- `pdf-viewer-modal.blade.php`
- `ajax-list-script.blade.php`
- `sidebar.blade.php`
- `topbar.blade.php`
- `partials/icons/*.blade.php`

## New widget / icon signals in current build
- `csv-export-badge.blade.php`
- `pdf-export-badge.blade.php`
- `print.blade.php`
- `plus.blade.php`
- `download-arrow.blade.php`
- `file-plain.blade.php`

## Current architecture note
- One anonymous Blade component exists: `resources/views/components/card.blade.php`
- No active class-based Laravel Component usage found.
- No active `<x-...>` component pattern found in current scan.
- Best future cleanup path: keep small icons/partials as-is, migrate medium/large widgets to Blade Components incrementally.

# 11. Progress Lock Snapshot (2026-05-20)
- **Locked scope**:
  - Realtime notifications
  - Persistent notifications dropdown and read-state
  - Kaprodi CRUD, assignment, proposal approval, final review approval, grade unlock
  - Dosen bimbingan, grading, request unlock
  - Mahasiswa skripsi, proposal upload, document revisions, final submission, nilai, non-skripsi
  - Reusable Blade partial/widget library
- **Recently stabilized**:
  - Final submission routes
  - Proposal submission approval flow
  - Final review approval flow
  - Document template management module
  - Mahasiswa bimbingan export CSV/PDF
- **Still open**:
  - Kaprodi export backend
  - Library publication backend
  - Full phase-board / keputusan automation
  - Dosen-side explicit final doc approval flow
  - Advanced RBAC and program-specific workflow variability
