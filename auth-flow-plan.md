# TACLOUD Registration & Google Login Plan

## Summary
Add Google OAuth integration via Laravel Socialite and enable mahasiswa self-registration, while maintaining strict role-based access control (RBAC) and data governance.

## 1. Authentication Strategy (Google OAuth)
- **Engine**: Laravel Socialite.
- **Identity Provider**: Google Workspace (Campus domain: `@widyakarya.ac.id`).
- **Policy**:
  - **Account Linking**: Existing TACLOUD users are linked automatically on their first successful Google login (via email match).
  - **Auto-Provisioning**: Disabled (No auto-account-creation for new, unknown Google users).
  - **Domain Restriction**: Only `@widyakarya.ac.id` addresses are accepted.
- **Login Mode**: Password and Google authentication coexist.

## 2. Registration Strategy (Mahasiswa)
- **Model**: Administrative-provisioning mixed with Mahasiswa self-registration.
- **Identity Proof**: NIM + University Email.
- **Approval Flow**:
  - Mahasiswa self-registers with NIM and Email.
  - System checks against imported master data.
  - If match found: Auto-approve and activate.
  - If no match: Pending status for Program Admin/Kaprodi review.
- **Constraint**: Dosen and Kaprodi accounts remain exclusively Admin-created (no self-registration).

## 3. Data & Policy Model
- **User Status**: Accounts must be `active` to access system features.
- **Fields**: Add `google_id` (unique), `google_avatar`, and `account_status` (`pending`, `active`, `rejected`) to `users` table.
- **Validation**:
  - `nim` must be unique.
  - `email` must be unique and end in `@widyakarya.ac.id`.
- **RBAC**: Google login does not bypass roles or soft-delete checks.

## 4. Admin/Program Review Surface
- Program Admin/Kaprodi dashboard will include a "Review Pending Registrations" list.
- Admin can Approve (assign program, activate) or Reject (with feedback) pending mahasiswa accounts.

## 5. Test Plan
- Verify Google login for existing active `@widyakarya.ac.id` users.
- Verify Google login is rejected for non-domain or unlinked emails.
- Verify Mahasiswa self-registration with NIM+Email correctly maps to existing records.
- Verify pending students are blocked from app features until approved.
- Verify Dosen/Kaprodi cannot self-register.
- Verify inactive/soft-deleted users remain blocked regardless of login method.

## 6. Assumptions
- Master mahasiswa dataset (imports) is the source of truth for identity verification.
- Account status (pending/active) is the primary gate for system access.
- Role management remains an internal TACLOUD admin function.
