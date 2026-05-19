# 1. Project Overview
- **App name**: TA Cloud / `tacloud`
- **Purpose**: Sistem manajemen tugas akhir untuk mengelola pengajuan skripsi, bimbingan, reviewer assignment, penilaian, dokumen revisi, dan arsip non-skripsi.
- **Current implementation scope**: Kaprodi layer aktif, Dosen layer aktif including grade unlock request flow, Mahasiswa layer aktif at CRUD/progress level.
- **Tech stack**:
  - **Backend**: Laravel 13, PHP 8.3
  - **Frontend**: Blade views, Tailwind CSS v4, Vite, Alpine-style server-rendered UI patterns
  - **Realtime/notifications infra**: Laravel Reverb, notification table, notification service
  - **Testing**: Pest
  - **Database**: Laravel Eloquent over relational DB schema (`users`, `skripsis`, `periodes`, `grades`, `document_versions`, `bimbingans`, `reviewer_assignments`, `non_skripsi_records`, `format_penilaians`)
- **Auth**: Session-based auth with Laravel Breeze package present, custom login controller/request, role gate enforced by `RoleMiddleware`.

# 2. User Roles & Permissions

| Role | Permissions | Inherits From |
|------|-------------|---------------|
| Kaprodi | Full CRUD for Dosen, Mahasiswa, Tahun Akademik, Periode, Format Penilaian; import Dosen/Mahasiswa CSV; monitor all skripsi; assign pembimbing/penguji; approve/reject permohonan sidang; update skripsi phase/status; view final nilai recap | None |
| Dosen | View assigned skripsi only; create/edit/delete bimbingan notes for assigned skripsi; submit permohonan sidang; input and finalize penilaian for assigned sidang queue | None |
| Mahasiswa | Create/edit/delete own skripsi; upload/delete own document versions; view/update own bimbingan revision response; view own grades; CRUD own non-skripsi record | None |

## Role hierarchy summary
- **Kaprodi**: global academic operator. Highest access.
- **Dosen**: reviewer-scoped access. Only assigned skripsi.
- **Mahasiswa**: owner-scoped access. Only own records.
- **Implementation note**: hierarchy exists as access level, not class inheritance. Code resolves role from `User -> level -> users_level`.

# 3. Functional Requirements

## Kaprodi
- **[DONE]** Dashboard monitoring for skripsi counts, pending assignment, pending sidang requests, and phase distribution.
- **[DONE]** Full CRUD for Dosen via `Kaprodi\DosenController`.
- **[DONE]** Full CRUD for Mahasiswa via `Kaprodi\MahasiswaController`.
- **[DONE]** Full CRUD for Tahun Akademik via `Kaprodi\TahunAkademikController`.
- **[DONE]** Full CRUD for Periode via `Kaprodi\PeriodeController`.
- **[DONE]** Full CRUD for Format Penilaian via `Kaprodi\FormatPenilaianController`.
- **[DONE]** Import Dosen and Mahasiswa CSV via `Kaprodi\ImportUserController`.
- **[DONE]** Global skripsi tracking, proposal view, bimbingan view, logbook download, document download via `Kaprodi\SkripsiController`.
- **[DONE]** Assign pembimbing and penguji to skripsi via reviewer assignment routes.
- **[DONE]** Approve/reject sidang requests via `Kaprodi\SidangRequestController`.
- **[DONE]** Final nilai recap listing via `Kaprodi\NilaiController`.
- **[DONE]** Unlock finalized grade on reviewer request via `Kaprodi\FormatPenilaianController::unlockGrade`.
- **[UPDATED]** Phase board and keputusan akhir pages exist, but mainly presentation/workspace support and not full business workflow automation.
- **[TODO]** Export skripsi report / rekap data.
- **[TODO]** Backend publish-to-library workflow from kaprodi workspace.

## Dosen
- **[DONE]** Dosen dashboard and skripsi queue.
- **[DONE]** View only assigned skripsi via `Dosen\SkripsiViewController`.
- **[DONE]** Create bimbingan note for assigned skripsi via `Dosen\BimbinganController::store`.
- **[DONE]** Edit and delete bimbingan note via `Dosen\BimbinganController::update` and `destroy`.
- **[DONE]** Submit permohonan sidang via `Dosen\SidangRequestController`.
- **[DONE]** Input penilaian sidang and persist grade items via `Dosen\PenilaianController`.
- **[DONE]** Request grade unlock after lock via `Dosen\PenilaianController::requestUnlock`.
- **[UPDATED]** Notification send on new bimbingan note exists through `NotificationService`.
- **[TODO]** Explicit reviewer approve/reject final document workflow from dosen workspace.

## Mahasiswa
- **[DONE]** Dashboard for active tugas akhir, document count, and bimbingan summary.
- **[DONE]** Full CRUD own skripsi via `Mahasiswa\SkripsiController`.
- **[DONE]** Upload and delete document versions via `Mahasiswa\DocumentVersionController`.
- **[DONE]** View bimbingan list and detail via `Mahasiswa\BimbinganController`.
- **[DONE]** Submit/update student revision response on bimbingan via `Mahasiswa\BimbinganController::update`.
- **[DONE]** Delete uploaded revision file via `Mahasiswa\BimbinganController::destroyRevision`.
- **[DONE]** View own nilai via `Mahasiswa\NilaiController`.
- **[DONE]** Full CRUD own non-skripsi record via `Mahasiswa\NonSkripsiController`.
- **[UPDATED]** Non-skripsi flow now implemented as real controller-backed CRUD, not concept only.
- **[TODO]** Final submission route wiring currently missing from active `routes/web/mahasiswa.php` even though `FinalSubmissionController` and final view still exist.

## Cross-role / platform
- **[DONE]** Notification center routes for index/read/read-all.
- **[DONE]** Shared topbar notification dropdown is available to authenticated Kaprodi, Dosen, Mahasiswa, and Admin pages through `resources/views/partials/topbar.blade.php`.
- **[DONE]** Workflow notifications are persisted through Laravel database notifications and broadcast realtime through `NotificationService` + `RealtimeNotificationCreated`.
- **[DONE]** Public or general library index/detail prototype under `/library`.
- **[DONE]** Role-based access restriction via middleware and ownership checks in controllers.

## Notifications
- **Infrastructure**: `NotificationService` writes `ThesisWorkflowNotification` database rows, broadcasts `notification.created` on private `users.{id}` channels, and exposes notification-center data through `/notifications`.
- **Mahasiswa -> Kaprodi**: `proposal_submitted` when mahasiswa uploads/submits proposal; `proposal_final_submitted` when final proposal document is submitted; `skripsi_final_submitted` when final skripsi document is submitted.
- **Dosen -> Kaprodi**: `sidang_request_submitted` when Pembimbing 1 submits a sidang request for a skripsi.
- **Dosen -> Mahasiswa**: `bimbingan_note_added` when dosen adds a new bimbingan note.
- **Kaprodi -> Dosen**: `reviewer_assigned` when Kaprodi assigns a dosen as pembimbing/penguji/reviewer on a skripsi.
- **Kaprodi -> Mahasiswa**: `proposal_approved` when proposal is approved; `proposal_rejected` when proposal needs revision/rejection; `skripsi_finished` when final document review completes and skripsi is marked selesai.
- **Coverage status**: Kaprodi, Dosen, and Mahasiswa have active workflow notifications; Admin currently only has the shared notification UI and routes, with no admin-specific workflow sender implemented yet.
- **Known gap**: Sidang request approval currently updates workflow state and redirects with flash feedback; it does not yet send a dedicated `sidang_request_approved` notification to mahasiswa/dosen.

# 4. Non-Functional Requirements
- **Performance**:
  - Eager loading used in key controllers (`with(['student', 'periode', 'assignments.lecturer', ...])`).
  - Paginated kaprodi nilai list.
  - Route/controller structure keeps list/detail split.
- **Security**:
  - Session login with request validation in `app/Http/Requests/Auth/LoginRequest.php`.
  - Login rate limiting via `RateLimiter`.
  - Role authorization via `app/Http/Middleware/RoleMiddleware.php`.
  - Ownership checks inside Mahasiswa and Dosen controllers.
  - Soft delete enabled on important records: `User`, `Skripsi`, `Periode`, `DocumentVersion` relation domain, `NonSkripsiRecord`.
- **Auditability**:
  - Document revisions stored as separate `DocumentVersion` rows.
  - Bimbingan notes stored by meeting.
  - Notifications persisted in database via notification migration.
- **Reliability**:
  - Validation enforced on create/update flows.
  - Tests already cover major role access and Mahasiswa flows.
- **Auth stack status**:
  - Breeze package installed.
  - Session auth active.
  - Sanctum API token flow not present in current code path.

# 5. Data Models

## `User`
- **Fillable**: `name`, `email`, `password`, `users_id`, `role`, `nim`, `nidn_nip`
- **Relationships**:
  - `level()` -> belongsTo `UserLevel`
  - `skripsi()` -> hasOne `Skripsi`
  - `reviewerAssignments()` -> hasMany `ReviewerAssignment`
  - `reviewedGrades()` -> hasMany `Grade`
  - `sidangRequests()` -> hasMany `SidangRequest`
  - `reviewedBimbingans()` -> hasMany `Bimbingan`
  - `uploadedDocumentVersions()` -> hasMany `DocumentVersion`
- **Notes**: role derived from linked `UserLevel`.

## `UserLevel`
- **Fillable**: `users_level`
- **Relationships**:
  - `users()` -> hasMany `User`

## `Skripsi`
- **Fillable**: `student_id`, `periode_id`, `title`, `type`, `current_phase`, `journal_article_url`
- **Relationships**:
  - `student()` -> belongsTo `User`
  - `periode()` -> belongsTo `Periode`
  - `assignments()` -> hasMany `ReviewerAssignment`
  - `reviewers()` -> belongsToMany `User` through `reviewer_assignments`
  - `bimbingans()` -> hasMany `Bimbingan`
  - `documentVersions()` -> hasMany `DocumentVersion`
  - `grades()` -> hasMany `Grade`
  - `sidangRequests()` -> hasMany `SidangRequest`
  - `nonSkripsiRecord()` -> hasOne `NonSkripsiRecord`

## `Periode`
- **Fillable**: `tahun_akademik_id`, `kode_periode`, `semester`, `sk_nomor`, `sk_dokumen_url`, `tgl_mulai`, `tgl_selesai`, `is_aktif`, `status`
- **Relationships**:
  - `tahunAkademik()` -> belongsTo `TahunAkademik`
  - `formats()` -> belongsToMany `FormatPenilaian`
  - `skripsis()` -> hasMany `Skripsi`

## `TahunAkademik`
- **Fillable**: current code not re-opened here; used by kaprodi CRUD and periode relation
- **Relationships**:
  - `periodes()` -> hasMany `Periode`

## `ReviewerAssignment`
- **Fillable**: `skripsi_id`, `lecturer_id`, `role_type`
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`
  - `lecturer()` -> belongsTo `User`

## `Bimbingan`
- **Fillable**: `skripsi_id`, `reviewer_id`, `phase`, `meeting_date`, `student_notes`, `lecturer_notes`, `reviewed_version_id`
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`
  - `reviewer()` -> belongsTo `User`
  - `reviewedVersion()` -> belongsTo `DocumentVersion`

## `DocumentVersion`
- **Fillable**: `skripsi_id`, `phase`, `version_number`, `file_path`, `mime_type`, `size`, `uploaded_by`
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`
  - `uploader()` -> belongsTo `User`

## `Grade`
- **Fillable**: `skripsi_id`, `format_penilaian_id`, `reviewer_id`, `role_type`, `grade_event`, `status`, `score`
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`
  - `template()` -> belongsTo `FormatPenilaian`
  - `reviewer()` -> belongsTo `User`
  - `items()` -> hasMany `GradeItem`

## `GradeItem`
- **Fillable**: `grade_id`, `item_penilaian_id`, `score`, plus item-specific fields per migration/domain
- **Relationships**:
  - `grade()` -> belongsTo `Grade`
  - `itemPenilaian()` -> belongsTo `ItemPenilaian`

## `FormatPenilaian`
- **Fillable**: `study_program_id`, `nama`, `template_type`, `is_published`, `is_locked`, `is_default`
- **Relationships**:
  - `studyProgram()` -> belongsTo `StudyProgram`
  - `items()` -> hasMany `ItemPenilaian`
  - `periodes()` / `periods()` -> belongsToMany `Periode`
- **Notes**: exposes accessor aliases `name` and `format_type`.

## `ItemPenilaian`
- **Fillable**: defined in model; used as child items of format penilaian
- **Relationships**:
  - `format()` -> belongsTo `FormatPenilaian`

## `NonSkripsiRecord`
- **Fillable**: `skripsi_id`, `summary`, `abstract`, `report_path`, `publication_url`, `final_score`
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`

## `StudyProgram`
- **Fillable**: defined in model; used by format penilaian relation
- **Relationships**:
  - `formatPenilaians()` -> hasMany `FormatPenilaian`

## `SidangRequest`
- **Fillable**: defined in model; used by dosen submission and kaprodi approval flow
- **Relationships**:
  - `skripsi()` -> belongsTo `Skripsi`
  - `lecturer()` -> belongsTo `User`

# 6. Routes & Endpoints

## Middleware groups
- `auth + role:kaprodi`
- `auth + role:dosen`
- `auth + role:mahasiswa`
- `auth` only for profile/overview
- public/general for library and login

## Kaprodi routes
- **Dashboard**: `kaprodi.dashboard`
- **Skripsi management**:
  - `kaprodi.skripsi.index`
  - `kaprodi.skripsi.show`
  - `kaprodi.skripsi.proposal`
  - `kaprodi.skripsi.bimbingan`
  - `kaprodi.skripsi.bimbingan.show`
  - `kaprodi.skripsi.logbook`
  - `kaprodi.skripsi.documents.download`
  - `kaprodi.skripsi.proposal.show`
  - `kaprodi.skripsi.status.update`
- **Reviewer assignment**:
  - `kaprodi.skripsi.reviewers.search`
  - `kaprodi.skripsi.reviewers.store`
  - `kaprodi.skripsi.reviewers.destroy`
  - `kaprodi.skripsi.assign.pembimbing`
  - `kaprodi.skripsi.assign.penguji`
- **Sidang requests**:
  - `kaprodi.skripsi.sidang-request.approve`
  - `kaprodi.skripsi.sidang-request.reject`
- **CRUD masters**:
  - `kaprodi.dosen.*`
  - `kaprodi.mahasiswa.*`
  - `kaprodi.tahun-akademik.*`
  - `kaprodi.periode.*`
  - `kaprodi.formats.*`
- **Import**:
  - `kaprodi.import.dosen`, `kaprodi.import.dosen.store`
  - `kaprodi.import.mahasiswa`, `kaprodi.import.mahasiswa.store`
- **Other kaprodi pages**:
  - `kaprodi.nilai.index`
  - `kaprodi.fase.index`
  - `kaprodi.keputusan.show`
  - `kaprodi.library.index`

## Dosen routes
- **Dashboard / queue**:
  - `dosen.dashboard`
  - `dosen.skripsi.index`
  - `dosen.skripsi.show`
  - `dosen.bimbingan.index`
- **Bimbingan**:
  - `dosen.bimbingan.create`
  - `dosen.bimbingan.store`
  - `dosen.bimbingan.edit`
  - `dosen.bimbingan.update`
  - `dosen.bimbingan.destroy`
- **Sidang request**:
  - `dosen.sidang-request.create`
  - `dosen.sidang-request.store`
- **Penilaian**:
  - `dosen.penilaian.index`
  - `dosen.penilaian.show`
  - `dosen.penilaian.store`

## Mahasiswa routes
- **Dashboard / progress**:
  - `mahasiswa.dashboard`
  - `mahasiswa.progres.index`
- **Skripsi CRUD**:
  - `mahasiswa.skripsi.index`
  - `mahasiswa.skripsi.create`
  - `mahasiswa.skripsi.store`
  - `mahasiswa.skripsi.show`
  - `mahasiswa.skripsi.edit`
  - `mahasiswa.skripsi.update`
  - `mahasiswa.skripsi.destroy`
- **Document versions**:
  - `mahasiswa.skripsi.documents.store`
  - `mahasiswa.skripsi.documents.destroy`
- **Bimbingan**:
  - `mahasiswa.skripsi.bimbingan.index`
  - `mahasiswa.skripsi.bimbingan.show`
  - `mahasiswa.skripsi.bimbingan.update`
  - `mahasiswa.skripsi.bimbingan.revision.destroy`
- **Nilai**:
  - `mahasiswa.skripsi.nilai.index`
- **Non-skripsi CRUD**:
  - `mahasiswa.non-skripsi.index`
  - `mahasiswa.non-skripsi.create`
  - `mahasiswa.non-skripsi.store`
  - `mahasiswa.non-skripsi.show`
  - `mahasiswa.non-skripsi.edit`
  - `mahasiswa.non-skripsi.update`
  - `mahasiswa.non-skripsi.destroy`

## General routes
- `login`, `logout`
- `profile.edit`, `profile.update`
- `dashboard.index`
- `notifications.index`, `notifications.read`, `notifications.read-all`
- `library.index`, `library.show`

# 7. Updates & Changes (Delta from old PRD)
- Old PRD mostly conceptual. Current code now has real controller-backed implementation for Kaprodi, Dosen, and Mahasiswa.
- Mahasiswa level now materially implemented:
  - own skripsi CRUD
  - own document version upload/delete
  - own bimbingan follow-up flow
  - own nilai view
  - own non-skripsi CRUD
- Dosen layer now includes editable bimbingan management, assigned-skripsi listing, penilaian flow, and sidang request submission.
- Kaprodi layer now includes live CRUD controllers and import flows instead of frontend-only placeholders.
- Notification infrastructure now exists in codebase.
- Library route exists as general read-only prototype.
- Current code diverges from old PRD in one important place: **final submission flow controller/view exists, but active mahasiswa routes do not wire it anymore**.
- Current codebase uses **Blade + Vite + Tailwind**, not Livewire.
- Auth stack in practice is **session auth + Breeze package + custom login request**, not Sanctum token flow.

# 8. Testing Plan
- **Pest structure present** under `tests/Feature` and `tests/Unit`.
- **Kaprodi coverage present**:
  - `tests/Feature/Kaprodi/DosenCrudTest.php`
  - `tests/Feature/Kaprodi/ImportUserControllerTest.php`
  - additional Kaprodi CRUD/route coverage via route and integration tests
- **Dosen coverage present**:
  - `tests/Feature/Dosen/BimbinganControllerTest.php`
  - `tests/Feature/Dosen/PenilaianControllerTest.php`
  - `tests/Feature/Dosen/SkripsiViewControllerTest.php`
- **Mahasiswa coverage present**:
  - `tests/Feature/Mahasiswa/SkripsiControllerTest.php`
  - `tests/Feature/Mahasiswa/DocumentVersionControllerTest.php`
  - `tests/Feature/Mahasiswa/BimbinganViewTest.php`
  - `tests/Feature/Mahasiswa/NilaiControllerTest.php`
  - `tests/Feature/Mahasiswa/NonSkripsiControllerTest.php`
- **Middleware / integration coverage present**:
  - `tests/Feature/Middleware/RoleMiddlewareTest.php`
  - `tests/Feature/Integration/CrossRoleTest.php`
  - `tests/Feature/RouteRegistrationTest.php`
- **Next test extensions**:
  - Add final submission flow tests once route restored.
  - Add notification delivery assertions around proposal/final/bimbingan events.
  - Add kaprodi skripsi assignment and sidang approval deeper scenario tests.

# 9. Implementation Gap Report

| Requirement | Status | Missing File/Method |
|-------------|--------|---------------------|
| Final submission mahasiswa from active route tree | Gap | `routes/web/mahasiswa.php` missing `mahasiswa.final.index`, `mahasiswa.final.metadata`, `mahasiswa.final.submit` route wiring; orphaned `App\Http\Controllers\Mahasiswa\FinalSubmissionController` |
| Publish final thesis to public library from kaprodi workflow | Gap | No dedicated backend controller/method connecting kaprodi approval to `library` publication |
| Export / rekap skripsi data | Gap | No export controller, route, or command |
| Explicit dosen approve/reject final document workflow | Gap | No dedicated method on dosen side for final document approval state transition |
| Full business logic behind kaprodi keputusan akhir page | Partial | `routes/web/kaprodi.php` anonymous `kaprodi.keputusan.show` view only; no controller/service workflow |
| Full business logic behind kaprodi phase board automation | Partial | `routes/web/kaprodi.php` anonymous `kaprodi.fase.index` page only |
| Live public library data source from finalized records | Partial | `routes/web/global.php` library pages use static/sample view data |
| API/mobile auth and token endpoints | Gap | No active Sanctum/API route layer in current app flow |
| Final submission automated tests | Gap | No `tests/Feature/Mahasiswa/FinalSubmissionControllerTest.php` |
| Notification event assertions in tests | Partial | Controllers send notifications, but targeted feature assertions not fully visible in current test set |

# 10. Reusable UI Components (Blade Partials / Widgets)

The UI is built from a collection of Blade partials that act as reusable widgets across roles. These are located under `resources/views/partials` and include:

| Component | Category | Purpose |
|-----------|----------|---------|
| `page-header.blade.php` | Layout | Consistent page header with title and breadcrumbs |
| `data-table.blade.php` | Table | Generic table rendering with sortable columns |
| `field.blade.php` | Form | Render form fields with label, hint and validation errors |
| `metric.blade.php` | Card | Dashboard metric card (label/value) |
| `feature.blade.php` | Card | Feature description card used on dashboards |
| `info.blade.php` | Card | Informational card used for static messages |
| `skripsi/detail-template.blade.php` | Detail | Common skripsi detail layout used by Mahasiswa/Dosen/Kaprodi |
| `pdf-viewer-modal.blade.php` | Modal | Inline PDF viewer modal for document preview |
| `ajax-list-script.blade.php` | Script | Generic AJAX pagination script used by tables |
| `icons/*.blade.php` | Icon set | SVG icon components (e.g., `eye`, `edit`, `trash`, `bell`, etc.) |
| `crud/form.blade.php` | CRUD | Form wrapper for create/edit actions |
| `crud/index.blade.php` | CRUD | Index page wrapper with filters and table |
| `crud/import.blade.php` | CRUD | Import CSV UI for admin imports |
| `crud/show.blade.php` | CRUD | Show view wrapper for detail pages |
| `skripsi-phase-timeline.blade.php` | Timeline | Visual timeline of skripsi phases |
| `sidebar.blade.php` | Layout | Responsive navigation sidebar used across roles |
| `topbar.blade.php` | Layout | Top navigation bar with notifications and user menu |

These partials provide a consistent look‑and‑feel and are reused across Kaprodi, Dosen, and Mahasiswa pages, reducing duplication and keeping UI updates centralized.

# 11. Progress Lock Snapshot (2026-05-19)

- **Locked scope**:
  - Realtime notifications
  - Persistent notifications dropdown and read-state
  - Kaprodi master-data CRUD flows
  - Dosen bimbingan and grading flows
  - Mahasiswa skripsi, document, bimbingan, nilai, and non-skripsi flows
  - Reusable Blade partial/widget library under `resources/views/partials`
- **Recently stabilized**:
  - Grade unlock request flow from Dosen to Kaprodi
  - Kaprodi grade unlock action for locked grades
  - Expanded icon/widget set for export/print affordances
- **New reusable widget signals**:
  - `resources/views/partials/icons/csv-export-badge.blade.php`
  - `resources/views/partials/icons/pdf-export-badge.blade.php`
  - `resources/views/partials/icons/print.blade.php`
  - `resources/views/partials/icons/plus.blade.php`
  - `resources/views/partials/icons/download-arrow.blade.php`
  - `resources/views/partials/icons/file-plain.blade.php`
- **Still open**:
  - Mahasiswa final submission route wiring
  - Kaprodi export backend logic
  - Library publish backend
  - Full phase-board / keputusan automation
  - Advanced RBAC and workflow variability
