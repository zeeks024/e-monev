# Admin Pages — resources/views/livewire/pages/admin

## OVERVIEW
16 Volt page templates for the admin panel — questionnaire management, scoring verification, reports, and settings.

## STRUCTURE
```
admin/
├── dashboard.blade.php              # Admin dashboard overview
├── kuesioner.blade.php              # Questionnaire management index
├── kuesioner/
│   ├── create-kategori.blade.php    # Create/edit question categories
│   ├── create-pertanyaan.blade.php  # Create/edit questions (versioned via JadwalPertanyaan)
│   ├── detail-pertanyaan.blade.php  # View questions for a category
│   └── jadwal.blade.php             # Manage evaluation schedules
├── penilaian.blade.php              # View all submissions/scores
├── verifikasi-nilai.blade.php       # Verify individual submissions
├── badan-publik.blade.php           # List all public information bodies
├── detail-badan-publik.blade.php    # View single badan publik details
├── edit-badan-publik.blade.php      # Edit badan publik data
├── laporan.blade.php                # Report listing + PDF download
├── pesan.blade.php                  # Admin messaging/announcements
├── klasifikasi-penilaian.blade.php  # Manage score classification tiers
├── pengaturan.blade.php             # App settings
└── keluar.blade.php                 # Admin logout confirmation
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| Create question categories | `kuesioner/create-kategori.blade.php` | Route: `admin.kuesioner.kategori.create` |
| Manage question versions | `kuesioner/create-pertanyaan.blade.php` | Uses `JadwalPertanyaan` pivot for versioning |
| Verify user scores | `verifikasi-nilai.blade.php` | Route params: `{user}/jadwal/{jadwal}/verifikasi` |
| Download PDF report | `laporan.blade.php` | Links to `LaporanController::unduhPdf` |
| Manage classification tiers | `klasifikasi-penilaian.blade.php` | Controls score-to-tier mapping |

## CONVENTIONS
- All routes prefixed with `/admin`, guarded by `admin` middleware
- Route names prefixed with `admin.` (e.g., `admin.kuesioner`, `admin.penilaian.verifikasi`)
- Uses `PenilaianService` for scoring calculations
- PDF generation via `barryvdh/laravel-dompdf` in `LaporanController`

## ANTI-PATTERNS
- Admin auth routes loaded TWICE (see root AGENTS.md) — may cause route registration issues
- `kuesioner/create-pertanyaan.blade.php` handles both create AND edit (same Volt component, different route params) — ensure form logic distinguishes properly
- No pagination visible in list views — verify performance with large datasets
