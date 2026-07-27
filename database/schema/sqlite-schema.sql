CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "departments"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "departments_code_unique" on "departments"("code");
CREATE UNIQUE INDEX "departments_name_unique" on "departments"("name");
CREATE INDEX "departments_is_active_index" on "departments"("is_active");
CREATE TABLE IF NOT EXISTS "study_programs"(
  "id" integer primary key autoincrement not null,
  "department_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "degree_level" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("department_id") references "departments"("id") on delete cascade
);
CREATE UNIQUE INDEX "study_programs_department_id_name_unique" on "study_programs"(
  "department_id",
  "name"
);
CREATE UNIQUE INDEX "study_programs_code_unique" on "study_programs"("code");
CREATE INDEX "study_programs_is_active_index" on "study_programs"("is_active");
CREATE TABLE IF NOT EXISTS "tahun_akademiks"(
  "id" integer primary key autoincrement not null,
  "tahun_awal" integer not null,
  "tahun_akhir" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE UNIQUE INDEX "tahun_akademiks_tahun_awal_tahun_akhir_unique" on "tahun_akademiks"(
  "tahun_awal",
  "tahun_akhir"
);
CREATE TABLE IF NOT EXISTS "periodes"(
  "id" integer primary key autoincrement not null,
  "tahun_akademik_id" integer not null,
  "kode_periode" varchar not null,
  "semester" integer not null,
  "sk_nomor" varchar,
  "sk_dokumen_url" varchar,
  "tgl_mulai" date not null,
  "tgl_selesai" date not null,
  "is_aktif" tinyint(1) not null default '0',
  "status" varchar not null default 'draft',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("tahun_akademik_id") references "tahun_akademiks"("id") on delete cascade
);
CREATE UNIQUE INDEX "periodes_tahun_akademik_id_kode_periode_unique" on "periodes"(
  "tahun_akademik_id",
  "kode_periode"
);
CREATE INDEX "periodes_is_aktif_index" on "periodes"("is_aktif");
CREATE INDEX "periodes_status_index" on "periodes"("status");
CREATE TABLE IF NOT EXISTS "skripsis"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "periode_id" integer not null,
  "title" varchar not null,
  "type" varchar check("type" in('skripsi', 'non_skripsi')) not null default 'skripsi',
  "current_phase" varchar not null default 'proposal',
  "created_at" datetime,
  "updated_at" datetime,
  "journal_article_url" varchar,
  "deleted_at" datetime,
  "proposal_review_status" varchar not null default 'pending',
  "proposal_reviewed_at" datetime,
  "proposal_review_note" text,
  "sidang_skripsi_datetime" datetime,
  "sidang_skripsi_grade_notified_at" datetime,
  "proposal_rejected_at" datetime,
  "sidang_proposal_datetime" datetime,
  "sidang_proposal_grade_notified_at" datetime,
  foreign key("student_id") references "users"("id") on delete cascade,
  foreign key("periode_id") references "periodes"("id") on delete cascade
);
CREATE INDEX "skripsis_periode_id_current_phase_index" on "skripsis"(
  "periode_id",
  "current_phase"
);
CREATE UNIQUE INDEX "skripsis_student_id_unique" on "skripsis"("student_id");
CREATE INDEX "skripsis_current_phase_index" on "skripsis"("current_phase");
CREATE TABLE IF NOT EXISTS "reviewer_assignments"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "lecturer_id" integer not null,
  "role_type" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade,
  foreign key("lecturer_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "reviewer_assignments_skripsi_id_role_type_unique" on "reviewer_assignments"(
  "skripsi_id",
  "role_type"
);
CREATE INDEX "reviewer_assignments_skripsi_id_role_type_index" on "reviewer_assignments"(
  "skripsi_id",
  "role_type"
);
CREATE TABLE IF NOT EXISTS "document_versions"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "phase" varchar not null,
  "version_number" integer not null,
  "file_path" varchar not null,
  "mime_type" varchar not null,
  "size" integer not null,
  "uploaded_by" integer not null,
  "created_at" datetime,
  "deleted_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_versions_skripsi_id_phase_version_number_unique" on "document_versions"(
  "skripsi_id",
  "phase",
  "version_number"
);
CREATE INDEX "document_versions_phase_index" on "document_versions"("phase");
CREATE TABLE IF NOT EXISTS "bimbingans"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "reviewer_id" integer not null,
  "phase" varchar not null,
  "meeting_date" date not null,
  "student_notes" text,
  "lecturer_notes" text,
  "revision_file_url" varchar,
  "reviewed_version_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade,
  foreign key("reviewer_id") references "users"("id") on delete cascade,
  foreign key("reviewed_version_id") references "document_versions"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "format_penilaians"(
  "id" integer primary key autoincrement not null,
  "study_program_id" integer,
  "template_type" varchar not null default 'sidang_skripsi',
  "nama" varchar not null,
  "is_published" tinyint(1) not null default '0',
  "is_locked" tinyint(1) not null default '0',
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("study_program_id") references "study_programs"("id") on delete cascade
);
CREATE INDEX "format_penilaians_study_program_id_is_published_index" on "format_penilaians"(
  "study_program_id",
  "is_published"
);
CREATE INDEX "format_penilaians_template_type_index" on "format_penilaians"(
  "template_type"
);
CREATE INDEX "format_penilaians_is_published_index" on "format_penilaians"(
  "is_published"
);
CREATE INDEX "format_penilaians_is_locked_index" on "format_penilaians"(
  "is_locked"
);
CREATE INDEX "format_penilaians_is_default_index" on "format_penilaians"(
  "is_default"
);
CREATE TABLE IF NOT EXISTS "item_penilaians"(
  "id" integer primary key autoincrement not null,
  "format_penilaian_id" integer not null,
  "nama" varchar not null,
  "kode" varchar not null,
  "bobot" integer not null,
  "sort_order" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("format_penilaian_id") references "format_penilaians"("id") on delete cascade
);
CREATE UNIQUE INDEX "item_penilaians_format_penilaian_id_kode_unique" on "item_penilaians"(
  "format_penilaian_id",
  "kode"
);
CREATE UNIQUE INDEX "item_penilaians_format_penilaian_id_sort_order_unique" on "item_penilaians"(
  "format_penilaian_id",
  "sort_order"
);
CREATE INDEX "item_penilaians_format_penilaian_id_sort_order_index" on "item_penilaians"(
  "format_penilaian_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "format_periode"(
  "id" integer primary key autoincrement not null,
  "format_penilaian_id" integer not null,
  "periode_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("format_penilaian_id") references "format_penilaians"("id") on delete cascade,
  foreign key("periode_id") references "periodes"("id") on delete cascade
);
CREATE UNIQUE INDEX "format_periode_format_penilaian_id_periode_id_unique" on "format_periode"(
  "format_penilaian_id",
  "periode_id"
);
CREATE TABLE IF NOT EXISTS "grades"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "format_penilaian_id" integer not null,
  "reviewer_id" integer not null,
  "role_type" varchar not null,
  "grade_event" varchar not null,
  "status" varchar not null default 'draft',
  "score" float,
  "created_at" datetime,
  "updated_at" datetime,
  "notes" text,
  "locked_at" datetime,
  "unlock_requested_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade,
  foreign key("format_penilaian_id") references "format_penilaians"("id") on delete cascade,
  foreign key("reviewer_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "grades_unique_event" on "grades"(
  "skripsi_id",
  "format_penilaian_id",
  "reviewer_id",
  "grade_event"
);
CREATE TABLE IF NOT EXISTS "grade_items"(
  "id" integer primary key autoincrement not null,
  "grade_id" integer not null,
  "item_penilaian_id" integer not null,
  "score" float not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("grade_id") references "grades"("id") on delete cascade,
  foreign key("item_penilaian_id") references "item_penilaians"("id") on delete cascade
);
CREATE UNIQUE INDEX "grade_items_grade_id_item_penilaian_id_unique" on "grade_items"(
  "grade_id",
  "item_penilaian_id"
);
CREATE TABLE IF NOT EXISTS "non_skripsi_records"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "summary" text not null,
  "abstract" text not null,
  "report_path" varchar,
  "publication_url" varchar,
  "final_score" float,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade
);
CREATE UNIQUE INDEX "non_skripsi_records_skripsi_id_unique" on "non_skripsi_records"(
  "skripsi_id"
);
CREATE TABLE IF NOT EXISTS "users_level"(
  "users_id" integer primary key autoincrement not null,
  "users_level" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_level_users_level_unique" on "users_level"(
  "users_level"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "sidang_requests"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "lecturer_id" integer not null,
  "role_type" varchar not null,
  "status" varchar not null default 'submitted',
  "note" text,
  "submitted_at" datetime,
  "approved_at" datetime,
  "approved_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "rejected_at" datetime,
  foreign key("skripsi_id") references "skripsis"("id") on delete cascade,
  foreign key("lecturer_id") references "users"("id") on delete cascade,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "sidang_requests_skripsi_id_lecturer_id_unique" on "sidang_requests"(
  "skripsi_id",
  "lecturer_id"
);
CREATE INDEX "sidang_requests_status_index" on "sidang_requests"("status");
CREATE INDEX "skripsis_proposal_review_status_index" on "skripsis"(
  "proposal_review_status"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "nim" varchar,
  "users_id" integer not null,
  "deleted_at" datetime,
  "nidn_nip" varchar,
  "role" varchar not null default('mahasiswa'),
  "study_program_id" integer,
  "google_id" varchar,
  "google_avatar" varchar,
  foreign key("users_id") references users_level("users_id") on delete restrict on update no action,
  foreign key("study_program_id") references "study_programs"("id") on delete set null
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_nidn_nip_index" on "users"("nidn_nip");
CREATE TABLE IF NOT EXISTS "document_templates"(
  "id" integer primary key autoincrement not null,
  "study_program_id" integer,
  "nama" varchar not null,
  "is_published" tinyint(1) not null default '0',
  "is_locked" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("study_program_id") references "study_programs"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "document_template_periode"(
  "id" integer primary key autoincrement not null,
  "document_template_id" integer not null,
  "periode_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("document_template_id") references "document_templates"("id") on delete cascade,
  foreign key("periode_id") references "periodes"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_template_periode_document_template_id_periode_id_unique" on "document_template_periode"(
  "document_template_id",
  "periode_id"
);
CREATE TABLE IF NOT EXISTS "document_submissions"(
  "id" integer primary key autoincrement not null,
  "skripsi_id" integer not null,
  "document_template_item_id" integer not null,
  "document_version_id" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("skripsi_id") references skripsis("id") on delete cascade on update no action,
  foreign key("document_template_item_id") references "document_template_items"("id") on delete cascade,
  foreign key("document_version_id") references "document_versions"("id") on delete set null
);
CREATE UNIQUE INDEX "document_submissions_skripsi_id_document_template_item_id_unique" on "document_submissions"(
  "skripsi_id",
  "document_template_item_id"
);
CREATE TABLE IF NOT EXISTS "document_template_items"(
  "id" integer primary key autoincrement not null,
  "document_template_id" integer not null,
  "nama" varchar not null,
  "kode" varchar not null,
  "is_required" tinyint(1) not null default('1'),
  "sort_order" integer not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "type" varchar not null default 'file',
  foreign key("document_template_id") references "document_templates"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_template_items_document_template_id_kode_unique" on "document_template_items"(
  "document_template_id",
  "kode"
);
CREATE UNIQUE INDEX "users_google_id_unique" on "users"("google_id");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_04_21_000001_01_create_departments_table',1);
INSERT INTO migrations VALUES(5,'2026_04_21_000001_02_create_study_programs_table',1);
INSERT INTO migrations VALUES(6,'2026_04_21_000001_add_role_and_nim_to_users_table',1);
INSERT INTO migrations VALUES(7,'2026_04_21_000002_01_create_tahun_akademiks_table',1);
INSERT INTO migrations VALUES(8,'2026_04_21_000002_02_create_periodes_table',1);
INSERT INTO migrations VALUES(9,'2026_04_21_000003_create_skripsis_table',1);
INSERT INTO migrations VALUES(10,'2026_04_21_000004_create_reviewer_assignments_table',1);
INSERT INTO migrations VALUES(11,'2026_04_21_000005_create_document_versions_table',1);
INSERT INTO migrations VALUES(12,'2026_04_21_000006_create_bimbingans_table',1);
INSERT INTO migrations VALUES(13,'2026_04_21_000007_create_template_penilaians_table',1);
INSERT INTO migrations VALUES(14,'2026_04_21_000007z_create_item_penilaians_table',1);
INSERT INTO migrations VALUES(15,'2026_04_21_000008_create_grading_phases_table',1);
INSERT INTO migrations VALUES(16,'2026_04_21_000009_create_grading_categories_table',1);
INSERT INTO migrations VALUES(17,'2026_04_21_000010_create_grading_components_table',1);
INSERT INTO migrations VALUES(18,'2026_04_21_000011_create_template_periode_table',1);
INSERT INTO migrations VALUES(19,'2026_04_21_000012_create_grades_table',1);
INSERT INTO migrations VALUES(20,'2026_04_21_000013_create_grade_items_table',1);
INSERT INTO migrations VALUES(21,'2026_04_21_000014_create_non_skripsi_records_table',1);
INSERT INTO migrations VALUES(22,'2026_04_28_000000_rebuild_users_role_schema',1);
INSERT INTO migrations VALUES(23,'2026_04_29_054817_create_notifications_table',1);
INSERT INTO migrations VALUES(24,'2026_04_30_000001_normalize_active_academic_periods',1);
INSERT INTO migrations VALUES(25,'2026_05_01_000001_add_deleted_at_to_users_table',1);
INSERT INTO migrations VALUES(26,'2026_05_01_000004_drop_status_from_skripsis_table',1);
INSERT INTO migrations VALUES(27,'2026_05_01_000005_create_sidang_requests_table',1);
INSERT INTO migrations VALUES(28,'2026_05_01_000006_add_nidn_nip_to_users_table',1);
INSERT INTO migrations VALUES(29,'2026_05_01_000007_add_journal_article_url_to_skripsis_table',1);
INSERT INTO migrations VALUES(30,'2026_05_05_000001_add_soft_deletes_to_non_skripsi_records_table',1);
INSERT INTO migrations VALUES(31,'2026_05_05_000002_add_soft_deletes_to_skripsis_and_document_versions',1);
INSERT INTO migrations VALUES(32,'2026_05_05_000003_restore_role_column_on_users_table',1);
INSERT INTO migrations VALUES(33,'2026_05_06_120000_drop_student_notes_from_bimbingans_table',1);
INSERT INTO migrations VALUES(34,'2026_05_06_220000_restore_bimbingan_student_notes_and_reviewed_version',1);
INSERT INTO migrations VALUES(35,'2026_05_07_193255_drop_student_notes_from_bimbingans_table_again',1);
INSERT INTO migrations VALUES(36,'2026_05_08_000001_create_final_document_approvals_table',1);
INSERT INTO migrations VALUES(37,'2026_05_08_000002_publish_existing_grades',1);
INSERT INTO migrations VALUES(38,'2026_05_08_000003_unify_sidang_requests',1);
INSERT INTO migrations VALUES(39,'2026_05_08_120000_add_proposal_review_fields_to_skripsis_table',1);
INSERT INTO migrations VALUES(40,'2026_05_14_000001_add_study_program_id_to_users_table',1);
INSERT INTO migrations VALUES(41,'2026_05_18_113735_add_sidang_skripsi_datetime_to_skripsis_table',1);
INSERT INTO migrations VALUES(42,'2026_05_18_123133_test_after_squash',2);
INSERT INTO migrations VALUES(43,'2026_05_18_183923_create_document_submissions_table',2);
INSERT INTO migrations VALUES(44,'2026_05_18_183923_create_document_template_items_table',2);
INSERT INTO migrations VALUES(45,'2026_05_18_183923_create_document_templates_table',2);
INSERT INTO migrations VALUES(46,'2026_05_18_190500_add_foreign_keys_to_document_submissions_table',2);
INSERT INTO migrations VALUES(47,'2026_05_18_190600_sync_document_template_schema',2);
INSERT INTO migrations VALUES(48,'2026_05_18_190700_add_foreign_keys_to_document_template_items_table',2);
INSERT INTO migrations VALUES(49,'2026_05_18_235900_add_notes_to_grades_table',2);
INSERT INTO migrations VALUES(50,'2026_05_19_000500_add_type_to_document_template_items_table',2);
INSERT INTO migrations VALUES(51,'2026_05_19_001004_add_unlock_requested_to_grades_table',2);
INSERT INTO migrations VALUES(52,'2026_05_26_211110_add_google_fields_to_users_table',2);
INSERT INTO migrations VALUES(53,'2026_05_27_230326_add_proposal_rejected_at_to_skripsis_table',2);
INSERT INTO migrations VALUES(54,'2026_05_27_231754_add_rejected_at_to_sidang_requests_table',2);
INSERT INTO migrations VALUES(55,'2026_06_30_120000_add_missing_sidang_schedule_columns_to_skripsis_table',2);
INSERT INTO migrations VALUES(56,'2026_07_07_145750_add_sidang_proposal_schedule_to_skripsis_table',2);
INSERT INTO migrations VALUES(57,'2026_07_20_000000_drop_final_document_approvals_table',2);
