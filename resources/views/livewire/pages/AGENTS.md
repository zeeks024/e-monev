# Volt Page Templates — resources/views/livewire/pages

## OVERVIEW
31 Volt functional page templates (Blade) — the primary UI layer. User pages + admin pages.

## STRUCTURE
```
livewire/pages/
├── dashboard.blade.php           # User dashboard
├── edit-biodata.blade.php        # User biodata edit
├── kuesioner.blade.php           # Questionnaire info page
├── jawab-kuesioner.blade.php     # Questionnaire answer form
├── notifikasi.blade.php          # User notifications
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   ├── reset-password.blade.php
│   ├── verify-email.blade.php
│   ├── confirm-password.blade.php
│   ├── verify-code.blade.php
│   ├── logout-confirm.blade.php
│   ├── password-reset-success.blade.php
│   └── register-success.blade.php
└── admin/
    ├── dashboard.blade.php
    ├── kuesioner.blade.php
    ├── penilaian.blade.php
    ├── verifikasi-nilai.blade.php
    ├── badan-publik.blade.php
    ├── detail-badan-publik.blade.php
    ├── edit-badan-publik.blade.php
    ├── laporan.blade.php
    ├── pesan.blade.php
    ├── klasifikasi-penilaian.blade.php
    ├── pengaturan.blade.php
    ├── keluar.blade.php
    └── kuesioner/
        ├── create-kategori.blade.php
        ├── create-pertanyaan.blade.php
        ├── detail-pertanyaan.blade.php
        └── jadwal.blade.php
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| User answers questionnaire | `jawab-kuesioner.blade.php` | Core user flow |
| Admin manages questions | `admin/kuesioner/` | CRUD for kategori, pertanyaan, jadwal |
| Admin verifies scores | `admin/verifikasi-nilai.blade.php` | Per-user, per-schedule verification |
| Admin PDF download | `admin/laporan.blade.php` | Links to `LaporanController::unduhPdf` |
| Admin settings | `admin/pengaturan.blade.php` | App configuration UI |

## CONVENTIONS
- Volt syntax: `<?php use Livewire\Volt\Component; ?>` + inline PHP at top of Blade files
- Layout: `components.layouts.app` (user), `components.layouts.admin` (admin), `components.layouts.guest` (auth)
- Tailwind CSS with custom theme (Poppins, brand-blue)
- SPA navigation enabled in Livewire config

## ANTI-PATTERNS
- **`notifikasi.blade.php`** — route defined twice in `web.php`, second definition lacks auth middleware (see root AGENTS.md)
- Large Volt components may mix business logic with presentation — consider extracting to `app/Livewire/` classes if they grow
- No TypeScript — JS is minimal Axios setup only
