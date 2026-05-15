# TACLOUD Implementation Plan

## 1. Project Planning Summary

This document captures four major implementation tracks discussed for TACLOUD:

1. Realtime infrastructure using Laravel Reverb + Echo
2. Persistent in-app notifications with dropdown UI and workflow triggers
3. Google login integration using Laravel Socialite
4. Super Admin / RBAC strategy for program-studi-specific workflow and grading variation

Goal:

- Serve as later execution guide
- Separate platform governance from academic workflow ownership
- Preserve decisions already chosen during planning
- Give next implementation session clear direction

---

## 2. Realtime Infrastructure

**Status:** Implemented in current system.

### Objective

Enable server-to-browser realtime delivery for app events, starting with notifications.

### Chosen Stack

- Backend: Laravel Reverb
- Frontend: Laravel Echo + `pusher-js`
- Broadcast auth: private user channels
- Primary use: notifications and future live workflow updates

### Current Design Decisions

- Use Laravel Reverb for websocket server
- Use one private channel per user:
  - `users.{userId}`
- Broadcast payload should carry:
  - notification id
  - title
  - message
  - url
  - actor
  - meta
  - unread count
  - created time

### Implemented Units

- Broadcasting config
- Reverb config
- Channel authorization route
- Frontend Echo bootstrap
- JS listener for top-bar notification state
- Notification event payload with unread count support
- Dev/runtime support for app server, reverb server, queue worker, and vite

### Acceptance Criteria

- Logged-in user subscribes only to own private channel
- Server event arrives without page refresh
- Badge count updates instantly
- Same payload can support toast, list, sidebar, or future live UI consumers

---

## 3. Persistent Notification System

**Status:** Implemented in current system. Core trigger matrix now covered.

### Objective

Make notifications persistent, role-aware, and visible in a dropdown list under the top-bar bell icon.

### Chosen Product Behavior

Notifications are both:

- persisted in database
- broadcast in realtime

Database is source of truth. Websocket is delivery accelerator.

### Chosen Storage Model

Use Laravel built-in `notifications` table.

### UI Behavior

Top-bar bell should support:

- unread badge
- click-to-open dropdown
- latest notification list
- mark single notification as read
- mark all notifications as read
- persistent state after refresh/relogin

### Notification Payload Shape

Each notification should support:

- `type`
- `title`
- `message`
- `url`
- `actor`
- `meta`
- `read_at`
- `created_at`

### Implemented Trigger Matrix

#### Mahasiswa Proposal Submit

When mahasiswa submits proposal:

- notify kaprodi
- notify relevant dosen audience
- link target should lead to thesis/detail monitoring page

#### Mahasiswa Final Submit

When mahasiswa submits final skripsi:

- notify kaprodi
- notify assigned dosen/reviewer
- notification should reference final phase or final review target

#### Dosen Adds Bimbingan Note

When dosen creates new bimbingan note:

- notify mahasiswa
- CTA should point mahasiswa to upload/revision action

#### Kaprodi Assigns Dosen Role on Thesis

When kaprodi assigns pembimbing/penguji:

- notify assigned dosen
- include student name, skripsi title, assignment role

### Implemented Dropdown API

Authenticated endpoints:

- list notifications
- mark one as read
- mark all as read

Response includes:

- items
- unread count

### Future Extensions

Notifications can later support:

- approval reminders
- sidang schedule updates
- grade finalization alerts
- document revision requests
- publish-to-library readiness

### Acceptance Criteria

- Refresh does not lose notification history
- Unread count remains accurate across sessions
- Clicking notification marks it read
- Realtime push prepends new item to dropdown
- Same notification visible on multiple devices after reload

---

## 4. Google Login Integration

### Objective

Add “Login with Google” for production-ready OAuth authentication.

### Chosen Stack

- `laravel/socialite`
- Google OAuth client credentials
- `config/services.php` entry for Google
- Controller-based redirect + callback flow

### Current Repo Findings

- `laravel/socialite` is not installed yet
- no Google config in `config/services.php`
- no `GOOGLE_*` vars in `.env.example`

### Environment Strategy

#### Development

- keep app local
- use ngrok or equivalent HTTPS tunnel for OAuth callback testing
- recommended redirect URI during dev:
  - `https://<tunnel>/auth/google/callback`

#### Production

- use real public domain
- update Google console redirect URI to production callback URL

### Important Decision

Do not move code into `public/`.

Google login works from normal Laravel structure. Only callback URL must be public/HTTPS reachable.

### Required Implementation Units

- install Socialite
- add Google config in services
- add env keys:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI`
- add routes:
  - redirect to Google
  - callback from Google
- add controller:
  - fetch Google profile
  - find/create local user by email
  - login user
  - redirect by role dashboard
- add login button to login page

### Product Assumptions

- email is primary identity key
- if user exists by same email, link login to existing account
- password remains nullable/random if account is Google-first
- role assignment still comes from internal system, not Google

### Acceptance Criteria

- user can click “Login with Google”
- Google callback succeeds on tunnel/public URL
- local user is matched or created
- authenticated session lands user on correct dashboard
- no secret committed to repo

---

## 5. Super Admin and RBAC Strategy

### Objective

Handle new reality: different program studi may have different phase flows and different sidang grading structures, without making Super Admin owner of kaprodi academic content.

### Core Governance Decision

Super Admin should not edit kaprodi workflow internals directly.

Super Admin scope:

- system administration
- role creation
- permission design
- page/action access mapping
- user-role assignment
- program scope mapping
- audit/governance controls

Academic config scope:

- owned by Kaprodi for own program studi

Operational support scope:

- Program Admin supports master data and workflow operations
- Program Admin has no publish authority for academic phase/grading config

### Role Model Target

#### Super Admin

- create new user level
- assign pages/modules
- assign actions
- assign scope by program studi
- audit permission/config changes
- freeze/disable access if needed

#### Kaprodi

- own workflow profile per study program
- define phase sequence
- define grading structure per sidang event
- publish/lock active versions
- assign dosen/reviewer
- monitor thesis progression

#### Program Admin

- manage dosen/mahasiswa master data
- manage imports
- access operational support pages
- cannot publish or own academic config

### Recommended Permission Model

Use:

- page permissions
- action permissions
- data scope

Minimum action examples:

- view
- create
- edit
- delete
- assign
- approve
- publish
- archive
- import
- export

Minimum scope examples:

- all programs
- one specific program
- own assignments only

### Full RBAC Implementation Roadmap

#### Phase 1 — Stabilize Role Source of Truth

- keep current route-level middleware working during transition
- standardize role resolution to one canonical source:
  - `users.role` for runtime read
  - `users_level` as normalized reference table
- add explicit support for new roles:
  - `super_admin`
  - `program_admin`
  - keep existing `kaprodi`, `dosen`, `mahasiswa`
- add missing user-to-program relation so scoped access is possible:
  - `users.study_program_id` nullable for global roles
- update factories, seeders, and login shortcuts to include new roles when needed

#### Phase 2 — Permission Matrix Foundation

- create permission entities for:
  - module/page
  - action
  - scope
- recommended minimum tables:
  - `permissions`
  - `role_permissions`
  - `user_program_scopes`
  - `permission_audits`
- permission naming should be stable and explicit, e.g.:
  - `skripsi.view`
  - `skripsi.assign`
  - `format_penilaian.publish`
  - `mahasiswa.import`
- keep action verbs aligned with this plan:
  - `view`, `create`, `edit`, `delete`, `assign`, `approve`, `publish`, `archive`, `import`, `export`

#### Phase 3 — Runtime Authorization Layer

- keep `RoleMiddleware` for coarse route grouping only
- add permission-aware authorization layer for sensitive actions
- enforce checks in this order:
  1. authenticated user
  2. role allowed in area
  3. permission action allowed
  4. data scope allowed
- use policies/services for object-level checks, not route strings alone
- high-risk actions that must move first from pure role checks:
  - publish/lock grading template
  - assign reviewer/penguji/pembimbing
  - import master data
  - archive/delete master data
  - phase override by kaprodi

#### Phase 4 — Program Scope Enforcement

- bind kaprodi and program admin to one or more `study_programs`
- define scope modes:
  - global
  - specific program
  - own assignments only
- all kaprodi academic data queries must filter by scoped `study_program_id`
- all program admin operational pages must filter by scoped `study_program_id`
- dosen remains assignment-scoped, not broad program-scoped by default
- mahasiswa remains self-scoped

#### Phase 5 — Super Admin Workspace

- add workspace to manage:
  - roles
  - permission matrix
  - program scope mapping
  - freeze/disable access
  - audit history
- super admin may manage governance objects only
- super admin must not edit:
  - kaprodi phase sequence
  - kaprodi grading structure
  - published academic workflow content

#### Phase 6 — Program Admin Workspace

- add role for operational support pages only
- allow:
  - dosen CRUD/import
  - mahasiswa CRUD/import
  - periode/tahun visibility if needed for support context
- block:
  - workflow publish
  - grading template publish/lock
  - academic final approval decisions

#### Phase 7 — Kaprodi Academic Ownership Hardening

- ensure kaprodi owns academic config only for own program scope
- enforce scoped ownership for:
  - workflow profile
  - phase definitions
  - grading template versions
  - reviewer assignment
  - skripsi monitoring
- prevent cross-program access even if route manually guessed

#### Phase 8 — Notification and Realtime Scope Reconnect

- reconnect notification recipients to RBAC/program scope rules
- ensure broadcast/private channel payloads never expose cross-program data
- super admin receives governance notifications only
- kaprodi/program admin receive only scoped operational/academic notifications

#### Phase 9 — Migration Strategy

- migrate in compatibility mode first:
  - old `role:*` route checks still function
  - new permission checks layered gradually
- backfill `study_program_id` for users tied to one program
- keep nullable scope for global governance roles
- convert sensitive controller actions first, low-risk list pages later

#### Phase 10 — Test and Rollout Completion

- add tests for:
  - super admin role creation
  - permission assignment
  - scoped kaprodi access
  - scoped program admin access
  - cross-program denial
  - publish denial without permission
  - notifications filtered by scope
- rollout order:
  1. schema + seeds + factories
  2. permission read services
  3. super admin pages
  4. program admin pages
  5. controller/policy conversion
  6. notification scope tightening
  7. remove remaining hardcoded assumptions

### Current Codebase Gap Against Roadmap

- current system already has route-level role gates for `mahasiswa`, `dosen`, `kaprodi`
- current system does not yet have:
  - `super_admin` runtime role
  - `program_admin` runtime role
  - permission matrix
  - scope engine by study program
  - user-to-study-program relation in active user model
- current system already hints at program scope in grading template via `format_penilaians.study_program_id`, so RBAC should align with that as first scoped domain

### RBAC Target Schema Draft

Recommended minimum schema for implementation:

#### Core Role Tables

- `users_level`
  - keep as role catalog / normalized role reference
  - add rows for `super_admin` and `program_admin`
- `users`
  - keep `role` as runtime convenience field during transition
  - add `study_program_id` nullable
  - global roles may keep `study_program_id = null`

#### Permission Tables

- `permissions`
  - `id`
  - `module_key`
  - `action_key`
  - `code` unique, e.g. `format_penilaian.publish`
  - `description` nullable
- `role_permissions`
  - `id`
  - `users_id` -> `users_level.users_id`
  - `permission_id`
  - unique on role + permission

#### Program Scope Tables

- `user_program_scopes`
  - `id`
  - `user_id`
  - `study_program_id`
  - `scope_type`
  - recommended values:
    - `program`
    - `global`
- if many-to-many program access is required for governance/support roles, use this table as source of truth instead of relying only on `users.study_program_id`

#### Audit Tables

- `permission_audits`
  - `id`
  - `actor_user_id`
  - `target_user_id` nullable
  - `target_role_id` nullable
  - `event_type`
  - `before_payload` nullable json
  - `after_payload` nullable json
  - timestamps

### RBAC Relation Rules

- `super_admin`
  - may have global scope
  - may manage roles, permissions, and scope mapping
- `program_admin`
  - must have one or more explicit study program scopes
- `kaprodi`
  - must have one study program scope in v1
- `dosen`
  - access primarily through assignment ownership
- `mahasiswa`
  - access through self ownership

### Runtime Resolution Rules

Authorization should resolve with this priority:

1. authenticated user exists
2. role resolved from `users.role`
3. permission resolved from `role_permissions`
4. scope resolved from `user_program_scopes` or fallback `users.study_program_id`
5. object-level ownership/assignment check applied last

### Migration Defaults

- keep old `role:*` middleware valid during migration
- backfill `users.role` from `users_level` if mismatch found
- backfill `users.study_program_id` only where ownership is unambiguous
- for users with multi-program access, prefer `user_program_scopes` over single-column scope

---

## 6. Program-Specific Workflow and Grading Variability

### Objective

Allow each study program to run distinct thesis phases and distinct grading models while preserving data stability.

### Chosen Principle

Workflow config should be program-scoped, versioned, and publishable.

### Recommended Domain Concept

Introduce Workflow Profile per study program.

Each workflow profile contains:

- ordered thesis phases
- phase type
- whether phase requires approval
- whether phase requires grading
- allowed transitions
- mapping to grading template when relevant

### Example Phase Model

Base example from current discussion:

- Proposal
- Sidang Proposal
- Bimbingan Skripsi
- Sidang Skripsi
- Revisi Sidang
- Submission Final

Another program may differ:

- Proposal
- Seminar Hasil
- Bimbingan
- Sidang Akhir
- Revisi
- Final Archive Submission

This means:

- no single global hardcoded phase list
- no global fixed sidang grading structure

### Grading Strategy

Each graded event should map to program-specific published template, for example:

- `sidang_proposal`
- `sidang_skripsi`

Template should support:

- category structure
- component structure
- weights
- reviewer roles
- version/state

### Configuration Lifecycle

Use:

- `draft`
- `published`
- `archived`

Rules:

- published config cannot be edited destructively
- changes create new draft version
- in-flight student records keep old referenced version
- new students/new phase entries use currently active published version

### Required Model Direction

Need program-scoped config tables for:

- workflow profiles
- workflow phases
- workflow phase transitions
- published grading templates
- template categories/components
- runtime references from thesis/grade records to used version

### Runtime Rule

Student/thesis should resolve workflow by:

- owning `study_program_id`
- active published workflow profile for that program
- active published grading template for relevant event

### Acceptance Criteria

- two programs can have different phase order
- two programs can have different sidang events
- two programs can have different grading weights/components
- publishing new config does not rewrite old student histories
- runtime always knows which profile/template version was used

---

## 7. Suggested Execution Order

1. Stabilize DB/runtime models for workflow/grading variability
2. Build RBAC foundation with Super Admin + Program Admin roles
3. Complete realtime infrastructure
4. Complete persistent notifications
5. Add Google login
6. Reconnect notifications and auth rules to finalized RBAC/program scope

---

## 8. Test and Validation Plan

### Realtime / Notifications

- private channel auth works
- unread badge syncs after push
- dropdown persists after reload
- mark-read APIs update unread count correctly

### Google Login

- redirect succeeds through ngrok/public callback
- callback creates or links user by email
- role-based redirect works
- missing/invalid Google config fails safely

### RBAC

- Super Admin can create role
- Super Admin can assign permission matrix
- Program Admin blocked from publish config
- Kaprodi limited to own study program
- cross-program access denied where required

### Workflow / Grading

- Program A and Program B can use different phase definitions
- Program A and Program B can use different sidang grading templates
- published template/version is preserved for historical grades
- changing draft does not alter active published runtime

---

## 9. Assumptions

- Laravel Reverb remains websocket choice
- Laravel notifications table remains persistence choice
- Google login uses Socialite
- Dev OAuth testing uses ngrok or similar HTTPS tunnel
- Super Admin is governance role, not academic content owner
- Kaprodi owns workflow/grading config for own program
- Program Admin is operational helper only
- permission model is page + action + program scope
- workflow/grading variability is program-based in v1
- period-based workflow variation may be added later, but not first

## 10. Free SMTP Mailing Strategy

### Objective
Enable email delivery for critical transactional events without relying on paid vendor platforms like Mailgun or Postmark. 

### Chosen Strategy
- Use generic Laravel SMTP configuration.
- Connect later to a free or institutional SMTP server (e.g., University Google Workspace SMTP relay or a dedicated university app-password mailbox).
- Mail delivery is queued to prevent web request blocking.
- Only critical workflow events trigger emails.

### Scope of v1 Mails
- **Mahasiswa submits proposal**: email kaprodi & relevant dosen
- **Mahasiswa submits final skripsi**: email kaprodi & assigned reviewers
- **Dosen adds bimbingan note**: email mahasiswa
- **Kaprodi assigns reviewer**: email assigned dosen
- **Auth operations**: reset password (later when auth finalized)

*Note: Not every in-app notification will send an email. Bulk announcements or digest emails are out of scope for v1.*

### Required Implementation Units
- Setup queue worker infrastructure (already covered in base dev setup).
- Update `.env` to point `MAIL_MAILER` to `smtp`.
- Leave generic placeholders for `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` until actual mailbox is chosen.
- Create a base transactional email Blade layout.
- Update `NotificationService` or individual workflow controllers to dispatch mail jobs alongside database notifications.
- Add error handling: if SMTP fails, the in-app notification must still succeed and the request must not crash.

### Acceptance Criteria
- Critical events dispatch an email job to the queue.
- Email contains clear context and a direct CTA link back to TACLOUD.
- Duplicate recipients in a single event receive only one email.
- The web request completes instantly even if the SMTP server is slow.
- The system logs mail failures without interrupting the user experience.

---

## 8. Safe Default Flow Note — Revisi Sidang Skripsi Approval

**Status:** Planned only. Do not implement yet.

### Goal

Keep current system stable while reserving future flexibility for Super Admin or Kaprodi to define their own workflow rules.

### Decision

Do **not** replace current hardcoded phase system yet.
Do **not** introduce workflow-engine tables yet.
Use this as a future roadmap only.

### Safe Default Flow To Preserve For Later Implementation

When `sidang_skripsi` starts:
- Kaprodi assigns `penguji_1` and `penguji_2`
- reviewer assignment should be complete before full sidang flow continues

After `sidang_skripsi`:
- Mahasiswa uploads revisi hasil sidang
- phase remains `revisi_sidang_skripsi`
- assigned dosen (`pembimbing_1`, `pembimbing_2`, `penguji_1`, `penguji_2`) approve or reject revisi
- once **all required dosen approve** latest revisi document, skripsi moves to `review_dokumen_final`
- after that, Kaprodi performs final validation before `skripsi_selesai`

### Important Current Gap Notes

Current codebase has these risks/gaps relative to desired safe default:
- revisi approval flow and final document approval flow still overlap conceptually
- current upload/submission flow may advance phase too early if not guarded carefully
- dosen approval UI/route should not be reintroduced carelessly without aligning phase transition logic
- reviewer assignment completeness should be verified before or at `sidang_skripsi`

### Future Flexibility Roadmap

Later, when system is stable, move to configurable workflow architecture:
- `workflow_phases`
- `workflow_transitions`
- role-based action mapping
- dynamic transition conditions

But until that future refactor:
- keep string-based `current_phase`
- keep controller-driven transitions
- prefer small, surgical changes only
- avoid DB-heavy workflow engine migration that could break existing live flow
