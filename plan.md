# TACLOUD Implementation Plan

## 1. Project Planning Summary
This document captures the active implementation tracks for TACLOUD and reflects current progress as of 20 Mei 2026.

---

## 2. Realtime Infrastructure
**Status: [DONE]**
- Laravel Reverb + Echo active.
- Private user notification channels active.
- Realtime unread badge and dropdown state stable.

## 3. Persistent Notification System
**Status: [DONE]**
- Database-backed notification flow active.
- Trigger matrix active for proposal, bimbingan, final submission, sidang flow, and unlock-request flow.

## 4. Academic Role Layers
**Status: [DONE]**
- **Kaprodi**: CRUD master data, assignment, approvals, unlock nilai, template dokumen final.
- **Dosen**: reviewer-scoped skripsi access, bimbingan, grading, sidang request, request unlock nilai.
- **Mahasiswa**: skripsi CRUD, proposal upload, document versions, bimbingan response, final submission, nilai, non-skripsi.

## 5. Reusable UI Framework
**Status: [DONE]**
- Shared Blade partial system active in `resources/views/partials`.
- Export/print icon widgets now present.
- Large-widget migration to Blade Components remains future cleanup, not blocker.

## 6. Proposal / Review Workflow Hardening
**Status: [DONE]**
- Proposal approval routes active.
- Final review approval routes active.
- Grade unlock flow active.

## 7. Document Template Management
**Status: [DONE]**
- Kaprodi document template CRUD active.
- Periode attach/detach flow active.
- Duplicate template flow active.

## 8. Google Login Integration
**Status: [DONE]**
- Socialite-based Google auth integrated.
- Google auth routes active in `routes/auth.php`.
- Dedicated callback controller active for Google sign-in/link flow.
- Users table already extended for Google account linkage metadata.

## 9. Super Admin & Advanced RBAC
**Status: [TODO]**
- Still basic role model.
- Program Admin / Super Admin / scoped capability matrix not yet implemented.

## 10. Program-Specific Workflow & Grading Variability
**Status: [TODO]**
- Phase board exists as workspace view.
- Full configurable workflow engine per program still pending.

## 11. Critical Gaps & Closures
**Status: [IN PROGRESS]**
- **[DONE]** Final submission wiring restored.
- **[DONE]** Proposal approval queue active.
- **[DONE]** Final review queue active.
- **[DONE]** Document template module active.
- **[IN PROGRESS]** Kaprodi export / global rekap backend.
- **[IN PROGRESS]** Library publication backend.
- **[IN PROGRESS]** Phase board / keputusan akhir automation.
- **[TODO]** Dosen-side explicit final document approval workflow.

## 12. Testing & Quality Assurance
**Status: [IN PROGRESS]**
- **[DONE]** Core CRUD tests and role middleware tests.
- **[TODO]** Final submission tests.
- **[TODO]** Document template tests.
- **[TODO]** Proposal/final review approval tests.
- **[TODO]** Notification assertions for unlock/final-review flows.

## 13. Progress Lock Snapshot (2026-05-20)
**Status: [LOCKED]**
- Notification infrastructure stable.
- Kaprodi operational layer stable.
- Dosen operational layer stable.
- Mahasiswa operational layer stable.
- Reusable Blade partial library stable.
- New document template module now part of locked scope.

---

## Execution Priority
1. Kaprodi export + rekap backend
2. Library publication backend
3. Dosen final document approval flow
4. Program-specific workflow engine
5. Advanced RBAC
6. API layer / token auth
