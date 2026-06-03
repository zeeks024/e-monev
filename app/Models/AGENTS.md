# Eloquent Models — app/Models

## OVERVIEW
15 Eloquent models defining the domain layer for the E-Monev KIP evaluation system.

## MODELS

| Model | Purpose | Key Relations |
|-------|---------|---------------|
| `User` | Dinas/official users (auth via `web` guard) | `hasOne(BadanPublik)`, `hasMany(Submission)`, `hasMany(HasilPenilaian)`, `belongsToMany(Pesan)` |
| `Admin` | Admin users (auth via `admin` guard) | None defined |
| `BadanPublik` | Public information body (one per User) | `belongsTo(User)` |
| `Jadwal` | Evaluation schedule with date ranges | `hasMany(JadwalPertanyaan)`, `hasMany(Submission)`, `hasMany(HasilPenilaian)`, `scopeActive()` |
| `Kategori` | Question categories | Referred by `PertanyaanTemplate`, `Submission` |
| `PertanyaanTemplate` | Question templates (versioned via JadwalPertanyaan) | `belongsTo(Kategori)`, `hasMany(JadwalPertanyaan)` |
| `JadwalPertanyaan` | Pivot: schedule ↔ question with ordering | `belongsTo(Jadwal)`, `belongsTo(PertanyaanTemplate)` |
| `Submission` | User's answer submission for a category+schedule | `belongsTo(User)`, `belongsTo(Kategori)`, `belongsTo(Jadwal)`, `hasMany(Jawaban)`, `hasOne(Penilaian)` |
| `Jawaban` | Individual answers within a submission | `belongsTo(Submission)` |
| `Penilaian` | Score for a submission | `belongsTo(Submission)` |
| `HasilPenilaian` | Final aggregated result per user+schedule | `belongsTo(User)`, `belongsTo(Jadwal)`, `belongsTo(KlasifikasiPenilaian)` |
| `KlasifikasiPenilaian` | Score-to-tier classification (active, ordered) | `hasMany(HasilPenilaian)`, `scopeActive()` |
| `Pengaturan` | App settings (key-value) | None |
| `Pesan` | Admin messages/announcements | `belongsToMany(User)` via `pesan_user` pivot |
| `Laporan` | Report records | None |

## CONVENTIONS
- All models use `HasFactory` trait
- Fillable arrays defined (no guarded)
- Hidden attributes on auth models: `password`, `remember_token`
- Date casts: `email_verified_at → datetime`, `password → hashed`
- `Jadwal` and `KlasifikasiPenilaian` have `scopeActive()` query scopes
- `PenilaianService` orchestrates scoring across Submission → Penilaian → HasilPenilaian → KlasifikasiPenilaian

## ANTI-PATTERNS
- `Admin` model has no relationships defined — may need them as app grows
- `User` missing `hasMany(Jawaban)` through submissions (indirect only)
- No `$casts` for boolean fields on models that use them (e.g., `Jadwal.is_active` is cast, but check others)
- `Laporan` and `Pengaturan` models not inspected — verify fillable/relations
