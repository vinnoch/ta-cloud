# TACLOUD Test Result Summary

**Prepared:** 27 July 2026  
**Purpose:** One-glance evidence for the launch-readiness and System Flow report.

## Overall result

| Test stream | Result | Status |
|---|---:|---|
| Final campaign | 20 of 20 scenarios closed | ✓ PASS |
| Current automated suite | 135 tests, 457 assertions | ✓ PASS |
| TestSprite accepted browser runs | Public library 5/5; sidang cancellation 1/1; two-advisor sidang flow 1/1 | ✓ PASS |
| Citra manual QA | 38 of 38 rows recorded Passed | ✓ PASS |
| Release migration | Dead `final_document_approvals` table removed; migration Ran | ✓ PASS |
| Independent dependency audit | Composer 0 advisories; npm 0 vulnerabilities | ✓ PASS |
| Production-safe fresh install | 57 migrations; zero users; master data only | ✓ PASS |
| Current launch blocker | None in the tested workflow | ✓ CLEAR |

## Final campaign: 20 scenarios

| # | Scenario | Main evidence | Result |
|---:|---|---|---|
| 1 | Test environment and seed data | Migration, seed, auth checks | ✓ PASS |
| 2 | Baseline application suite and build | Laravel tests, Blade/routes, Vite | ✓ PASS |
| 3 | TestSprite browser infrastructure | Five public-library browser cases | ✓ PASS |
| 4 | Authentication and session security | Login, logout, throttle, Google domain | ✓ PASS |
| 5 | Role-based authorization | Cross-role and middleware matrix | ✓ PASS |
| 6 | Ownership and IDOR protection | Dosen bimbingan ownership checks | ✓ PASS |
| 7 | CSRF protection | Tokenless writes rejected | ✓ PASS |
| 8 | Validation and mass assignment | Forged privileged fields rejected | ✓ PASS |
| 9 | Upload and download security | MIME, owner, phase and path checks | ✓ PASS |
| 10 | XSS and output escaping | Stored, reflected and DOM sinks fixed | ✓ PASS |
| 11 | Query and sort security | Safe sort allow-list and search handling | ✓ PASS |
| 12 | Browser security headers | `nosniff`, frame and referrer headers | ✓ PASS |
| 13 | Mahasiswa workflow | Proposal, grades and final submission guards | ✓ PASS |
| 14 | Dosen workflow | Guidance, grading and sidang request | ✓ PASS |
| 15 | Kaprodi workflow | Review, completion and historical archive | ✓ PASS |
| 16 | Cross-role lifecycle and library | Completion appears in public library | ✓ PASS |
| 17 | Notifications and privacy | Correct recipients and access control | ✓ PASS |
| 18 | Accessibility and modal keyboard use | Dialog semantics, focus, Tab and Escape | ✓ PASS |
| 19 | Production routes and cache | Unique routes, route cache and optimize | ✓ PASS |
| 20 | Dependencies and release gates | Composer/npm audit, build and full suite | ✓ PASS |

## TestSprite browser results

| Browser group | Exact coverage | Result | Evidence note |
|---|---|---|---|
| Public library | Five browse/detail/navigation cases | ✓ 5/5 PASS | Accepted browser assertions |
| Dosen sidang cancellation | Cancel pending request, submit again | ✓ 1/1 PASS | Flash text not asserted; Laravel test covers it |
| Two-advisor sidang request | Pembimbing 1 and 2 submit; Kaprodi approves; phase advances | ✓ 1/1 PASS | DB confirms both approvals and final phase; exact intermediate phases covered by Laravel |
| Final-document approval TC023 | Old Dosen-approval workflow | Not applicable | Removed after business-flow correction |

## Citra manual QA summary

| Role | Passed | Failed |
|---|---:|---:|
| Kaprodi | 19 | 0 |
| Dosen | 7 | 0 |
| Mahasiswa | 7 | 0 |
| General | 5 | 0 |
| **Total** | **38** | **0** |

Eleven Passed rows included remarks about defects or gaps observed during manual QA. They were tracked through later patches and automated verification; identical manual retests were not recorded for every remark.

## Citra manual QA detail

| ID | Role | Area | Short check | Result |
|---|---|---|---|---|
| 1.1 | Kaprodi | Dashboard | Widgets and counts | ✓ PASS |
| 1.2 | Kaprodi | Program Study | CRUD | ✓ PASS |
| 1.3 | Kaprodi | Dosen | CRUD and CSV import | ✓ PASS |
| 1.4 | Kaprodi | Mahasiswa | CRUD and CSV import | ✓ PASS |
| 1.5 | Kaprodi | Academic Year | CRUD | ✓ PASS |
| 1.6 | Kaprodi | Period | CRUD | ✓ PASS |
| 1.7 | Kaprodi | Grading Format | CRUD and duplicate | ✓ PASS |
| 1.8 | Kaprodi | Thesis | List and detail | ✓ PASS |
| 1.9 | Kaprodi | Thesis | Assign advisors/examiners | ✓ PASS |
| 1.10 | Kaprodi | Proposal | Review, approve and reject | ✓ PASS |
| 1.11 | Kaprodi | Sidang Request | Review, approve and reject | ✓ PASS* |
| 1.12 | Kaprodi | Grades | Recap and unlock | ✓ PASS |
| 1.13 | Kaprodi | Document Templates | CRUD and period links | ✓ PASS* |
| 1.14 | Kaprodi | Export | Thesis CSV export | ✓ PASS* |
| 1.15 | Kaprodi | Library | Final-document/library access | ✓ PASS* |
| 1.16 | Kaprodi | Scheduling | Set thesis defense schedule | ✓ PASS* |
| 1.17 | Kaprodi | Monitoring | Open thesis monitoring | ✓ PASS* |
| 1.18 | Kaprodi | Final Document Master | Save draft and publish | ✓ PASS* |
| 1.19 | Kaprodi | Final Document Review | Open final documents | ✓ PASS* |
| 2.1 | Dosen | Dashboard | Assigned work queue | ✓ PASS |
| 2.2 | Dosen | Thesis | Assigned list and detail | ✓ PASS |
| 2.3 | Dosen | Guidance | Add, edit and delete notes | ✓ PASS |
| 2.4 | Dosen | Sidang Request | Submit request | ✓ PASS* |
| 2.5 | Dosen | Grading | Enter and finalize grades | ✓ PASS |
| 2.6 | Dosen | Grade Unlock | Request unlock | ✓ PASS |
| 2.7 | Dosen | Notifications | Assignment/review alerts | ✓ PASS |
| 3.1 | Mahasiswa | Dashboard | Thesis and document summary | ✓ PASS |
| 3.2 | Mahasiswa | Thesis | Own-record CRUD | ✓ PASS |
| 3.3 | Mahasiswa | Documents | Upload, version and delete | ✓ PASS |
| 3.4 | Mahasiswa | Guidance | Revision and logbook export | ✓ PASS |
| 3.5 | Mahasiswa | Final Submission | Eligibility and submission | ✓ PASS* |
| 3.6 | Mahasiswa | Grades | View final grades | ✓ PASS |
| 3.7 | Mahasiswa | Non-Thesis | CRUD | ✓ PASS |
| 4.1 | General | Authentication | Valid/invalid login and roles | ✓ PASS |
| 4.2 | General | Notifications | Read and mark-as-read | ✓ PASS |
| 4.3 | General | Reusable UI | Shared components | ✓ PASS |
| 4.4 | General | Library | Public index and detail | ✓ PASS* |
| 4.5 | General | FAQ | FAQ and limitations pages | ✓ PASS |

`*` Citra recorded a remark. Later technical verification covers the corrected paths, but an identical Citra retest is not documented for every row.

## Final release checks

| Check | Result |
|---|---|
| Full Laravel suite | ✓ 135 tests / 457 assertions |
| Focused final workflow suite | ✓ 22 tests / 90 assertions |
| Authentication and import suite | ✓ 39 tests / 175 assertions |
| Critical release suite | ✓ 65 tests / 290 assertions |
| Dependency audit | ✓ Independent live-registry retest on 27 July 2026: Composer 0 advisories; npm 0 vulnerabilities |
| Critical release suites | ✓ 64 tests / 287 assertions |
| Frontend build | ✓ Vite 8.1.5, 58 modules |
| Route list and cache | ✓ PASS |
| Laravel optimize | ✓ PASS |
| Migration | ✓ Ran; obsolete table removed |
| Working-tree whitespace check | ✓ PASS |

## Conclusion

**✓ Tested workflow is clear for launch.** No open blocker remains in the verified authentication, role, sidang-request, grading, final-document, completion, migration, or library flows.

The Akun Test shortcut and all known production credentials were removed. Production seeding creates master data only and zero users. A disposable SQLite installation passed all 57 migrations; shared MySQL was untouched. Dependency results are time-sensitive. The current conclusion uses the independent live-registry retest completed on 27 July 2026. The non-blocking `/images/login-wave.jpg` runtime-resolution warning remains.

Sources: Citra public Google Sheet; local Laravel/Pest results; TestSprite dashboards and generated scripts; `TestSprite_Final_Campaign_Evidence.md`.
