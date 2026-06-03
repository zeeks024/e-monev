# Database Migrations — database/migrations

## OVERVIEW
33 migrations tracking schema evolution from initial Laravel skeleton through versioning refactor (2026-02-20).

## MIGRATION TIMELINE

| Date | Migration | Purpose |
|------|-----------|---------|
| 0001_01_01 | `create_users_table` | Default Laravel users |
| 0001_01_01 | `create_cache_table` | Laravel cache |
| 0001_01_01 | `create_jobs_table` | Laravel queue jobs |
| 2025-08-06 | `create_admins_table` | Separate admin auth model |
| 2025-08-07 | `add_role_to_users_table` | Role column on users |
| 2025-08-07 | `create_penilaians_table` | Initial scoring table |
| 2025-08-08 | `create_badan_publiks_table` | Public information bodies |
| 2025-08-11 | `add_user_id_to_badan_publiks_table` | Link BP to user |
| 2025-08-11 | `add_details_to_badan_publiks_table` | Additional BP fields |
| 2025-08-12 | `create_pengaturans_table` | App settings |
| 2025-08-12 | `create_kategoris_table` | Question categories |
| 2025-08-12 | `create_pertanyaans_table` | Questions (later renamed) |
| 2025-08-14 | `create_pesans_table` | Admin messages |
| 2025-08-14 | `add_status_verifikasi_to_penilaians_table` | Verification status |
| 2025-08-14 | `create_laporans_table` | Report records |
| 2025-08-15 | `create_jadwals_table` | Evaluation schedules |
| 2025-08-19 | `add_jawaban_fields_to_pertanyaans_table` | Answer fields on questions |
| 2025-08-21 | `create_submissions_table` | User answer submissions |
| 2025-08-21 | `create_jawabans_table` | Individual answers |
| 2025-08-21 | `update_penilaians_table` | Scoring table refactor |
| 2025-08-22 | `create_pesan_user_table` | Message-user pivot |
| 2025-08-25 | `add_profile_photo_path_to_users_table` | User avatars |
| **2026-02-20** | `update_jadwals_table_for_versioning` | Schedule versioning |
| **2026-02-20** | `rename_pertanyaans_to_templates_and_add_is_active` | Questions → templates |
| **2026-02-20** | `create_jadwal_pertanyaans_table` | Schedule-question pivot |
| **2026-02-20** | `add_jadwal_id_to_submissions_table` | Link submissions to schedule |
| **2026-02-20** | `update_jawabans_table_for_versioning` | Answer versioning |
| **2026-02-20** | `migrate_existing_data_to_versioning` | Data migration script |
| **2026-02-20** | `drop_ppid_columns_from_badan_publiks_table` | Cleanup |
| **2026-02-20** | `create_klasifikasi_penilaians_table` | Score classification tiers |
| **2026-02-20** | `create_hasil_penilaians_table` | Aggregated results |
| **2026-02-20** | `backfill_hasil_penilaians_table` | Data backfill |
| 2026-04-21 | `add_definisi_operasional_to_pertanyaan_templates_and_jadwal_pertanyaans_table` | Operational definitions |

## KEY SCHEMA PATTERNS
- **Versioning system** (2026-02-20): Questions decoupled from schedules via `jadwal_pertanyaans` pivot, enabling different question sets per evaluation period
- **Scoring chain**: `Submission` → `Penilaian` (per-category score) → `HasilPenilaian` (aggregated) → `KlasifikasiPenilaian` (tier classification)
- **Dual auth**: `users` table (role-based) + `admins` table (separate guard)
- **Database-backed** session, cache, queue — ensure `sessions`, `cache`, `jobs` tables exist

## CONVENTIONS
- Timestamp-prefixed filenames (Laravel standard)
- Data migration included (`migrate_existing_data_to_versioning`, `backfill_hasil_penilaians_table`)
- SQLite in-memory for testing — all migrations must be SQLite-compatible

## ANTI-PATTERNS
- `migrate_existing_data_to_versioning` and `backfill_hasil_penilaians_table` are data migrations — verify they're idempotent if re-run
- No foreign key constraints visible in migration names — check if relations are enforced at DB level or only in Eloquent
