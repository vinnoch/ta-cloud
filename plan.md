# TACLOUD Implementation Plan

## 1. Project Planning Summary

This document captures the implementation tracks for TACLOUD. The goal is to transition the system from a role-based prototype to a production-ready academic management system.

---

## 2. Realtime Infrastructure
**Status: [DONE]**
- **Implemented**: Laravel Reverb + Echo integration.
- **Current State**: Private user channels (`users.{userId}`) are active. Payload supports notification details and unread counts.

## 3. Persistent Notification System
**Status: [DONE]**
- **Implemented**: Database-backed notifications via Laravel's native system.
- **Current State**: Trigger matrix for Proposal Submit, Final Submit, and Bimbingan notes is active. Dropdown UI with read/unread state is implemented. Unlock-request notifications are now part of grading flow.

## 4. User Role Implementation (Academic Layer)
**Status: [DONE]**
- **Kaprodi Layer**: Full CRUD for Master Data, Assignment logic, Sidang Request approvals, and grade unlock approval.
- **Dosen Layer**: Reviewer-scoped access, Bimbingan notes management, grading input, and grade unlock requests.
- **Mahasiswa Layer**: Own skripsi CRUD, Document Versioning, Bimbingan responses, and Non-Skripsi record management.

## 5. Reusable UI Framework
**Status: [DONE]**
- **Implemented**: Modular Blade partials in `resources/views/partials`.
- **Components**: Page headers, data tables, form fields, metric cards, role-aware sidebars/topbars, and expanded export/print icon widgets.

## 6. Google Login Integration
**Status: [TODO]**
- **Objective**: Integrate `laravel/socialite` for Google OAuth.
- **Next Steps**: Install package, configure `services.php`, and implement callback controller.

## 7. Super Admin & Advanced RBAC
**Status: [TODO]**
- **Objective**: Implement Super Admin for global governance and Program Admin for scoped operations.
- **Next Steps**: Design `role_permissions` and `user_program_scopes` tables to replace simple role strings.

## 8. Program-Specific Workflow & Grading Variability
**Status: [TODO]**
- **Objective**: Allow different study programs to define their own phases and grading weights.
- **Next Steps**: Implement Workflow Profiles and versioned Grading Templates.

## 9. Critical Fixes & Gap Closures (Short-term)
**Status: [IN PROGRESS]**
- **[ ] Final Submission Wiring**: Restore `mahasiswa.final.*` routes to connect `FinalSubmissionController` to the UI.
- **[ ] Kaprodi Export Logic**: Implement backend for CSV/PDF export of skripsi data.
- **[ ] Workflow Automation**: Add logic for "Keputusan Akhir" and "Phase Board" (move from static views to active state management).
- **[ ] Dosen Final Approval**: Add specific approval/rejection methods for final document review.
- **[ ] Library Backend**: Connect `library.*` routes to actual finalized skripsi records.

## 10. Testing & Quality Assurance
**Status: [IN PROGRESS]**
- **[DONE]** Basic CRUD and Role Middleware tests.
- **[TODO]** Final Submission flow tests.
- **[TODO]** Notification delivery assertions.
- **[TODO]** Cross-role edge case tests (e.g., Dosen modifying others' notes).

---

## Execution Priority
1. **Fix Final Submission Flow** (Unblocks Mahasiswa completion)
2. **Restore/Build RBAC Foundation** (Unblocks scaling to multi-program)
3. **Implement Workflow/Grading Variability** (Core academic requirement)
4. **Close Kaprodi Gaps** (Export, Decisions, Phase Board)
5. **Google Login & API layer** (Polish/Expansion)

## 11. Progress Lock Snapshot (2026-05-19)
**Status: [LOCKED]**
- **Stable / keep**:
  - Notification infrastructure and dropdown UX
  - Kaprodi CRUD + reviewer assignment + grade unlock handling
  - Dosen grading + unlock request handling
  - Mahasiswa CRUD/progress/document/non-skripsi flows
  - Blade partial/widget layer in `resources/views/partials`
- **Do not regress**:
  - Role middleware ownership checks
  - Grade lock/unlock workflow
  - Reusable partial-based layout strategy
- **Open next**:
  - Final submission route restore
  - Export backend
  - Library publication backend
  - Advanced RBAC
  - Program-specific workflow config
