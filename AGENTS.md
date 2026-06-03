# PROJECT KNOWLEDGE BASE — E-Monev KIP

**Generated:** 2026-05-29
**Commit:** 5218a36
**Branch:** main

## OVERVIEW
Electronic Monitoring & Evaluation for KIP (Komisi Informasi Publik) — a public information disclosure compliance evaluation system for Banjarnegara Regency, Indonesia. Laravel 12 + Livewire 3 + Volt + Tailwind CSS + MySQL.

## STRUCTURE
```
emonev/
├── app/
│   ├── Http/           # Controllers, middleware, form requests
│   ├── Livewire/       # Volt actions + form components (Logout, LoginForm)
│   ├── Models/         # 15 Eloquent models (User, Admin, Submission, Penilaian, etc.)
│   ├── Providers/      # AppServiceProvider, VoltServiceProvider
│   ├── Services/       # PenilaianService (scoring business logic)
│   └── View/           # Blade layout components (AppLayout, GuestLayout)
├── bootstrap/          # app.php (routing + middleware registration), providers.php
├── config/             # 11 Laravel config files (auth has custom admin guard)
├── database/
│   ├── migrations/     # 33 migrations (schema evolution with versioning)
│   ├── factories/      # UserFactory
│   └── seeders/        # DatabaseSeeder, AdminSeeder
├── public/             # Front controller (index.php), Vite build output
├── resources/
│   ├── css/            # app.css (Tailwind)
│   ├── js/             # app.js → bootstrap.js (Axios setup)
│   └── views/
│       ├── components/ # Reusable Blade components (14 files)
│       ├── livewire/   # Volt page templates (31 pages: user + admin)
│       └── welcome.blade.php  # Landing page
├── routes/
│   ├── web.php         # User-facing routes (Volt + controller hybrid)
│   ├── admin.php       # Admin panel routes (prefix: /admin, guard: admin)
│   ├── auth.php        # Auth routes (loaded from web.php)
│   └── console.php     # Artisan commands
├── storage/            # Logs, sessions, compiled views, uploads
├── tests/              # Pest PHP tests (Feature/Auth/, Unit/)
├── artisan             # CLI entry point
├── vite.config.js      # Vite + laravel-vite-plugin
├── tailwind.config.js  # Custom theme (Poppins font, brand-blue)
└── reset.php           # ⚠ Ad-hoc password reset script (non-standard location)
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| User authentication | `routes/auth.php` + `app/Http/Controllers/Auth/` | Breeze + Volt |
| Admin authentication | `routes/admin.php` + `app/Http/Controllers/Admin/Auth/` | Custom admin guard |
| Questionnaire (kuesioner) | `resources/views/livewire/pages/kuesioner.blade.php` + `jawab-kuesioner.blade.php` | Volt components |
| Admin questionnaire management | `resources/views/livewire/pages/admin/kuesioner/` | CRUD for kategori, jadwal, pertanyaan |
| Scoring/penilaian logic | `app/Services/PenilaianService.php` | Core business logic |
| Admin verification | `resources/views/livewire/pages/admin/verifikasi-nilai.blade.php` | Admin verifies submissions |
| PDF reports | `app/Http/Controllers/Admin/LaporanController.php` | Uses barryvdh/laravel-dompdf |
| Landing page stats | `routes/web.php` (root `/`) | Inline closure, queries HasilPenilaian |
| Database schema | `database/migrations/` | 33 migrations, versioning added 2026-02-20 |
| Custom middleware | `app/Http/Middleware/AdminMiddleware.php` | Checks `Auth::guard('admin')` |

## CONVENTIONS
- **Volt functional components** for most pages (`Volt::route()` → Blade view), traditional controllers for CRUD/PDF
- **Custom admin guard** in `config/auth.php` — separate `Admin` model, separate `/admin` prefix
- **Database-backed** session, cache, queue (not file/sync defaults)
- **Tailwind theme**: Poppins font, `brand-blue: #438AFF`, `brand-secondary: #3B82F6`
- **Pest PHP** for testing, SQLite in-memory, `RefreshDatabase` scoped to Feature tests only
- **4-space indent** (`.editorconfig`), PSR-4 autoloading

## ANTI-PATTERNS (THIS PROJECT)
- **Admin routes registered TWICE** — `bootstrap/app.php` AND `AppServiceProvider::boot()` both load `routes/admin.php`. Pick one.
- **Duplicate `/notifikasi` route** in `routes/web.php` — second definition (line 101) lacks `auth` middleware, overwrites the guarded version (line 84). Notifications page is publicly accessible.
- **`reset.php` at project root** — manual Laravel bootstrap for password reset. Should be an Artisan command.
- **SQL dump files at repo root** — `db_emonev_kip_*.sql` should be in `database/dumps/` or `.gitignore`d.
- **Tailwind version mismatch** — `tailwindcss@^3.1.0` + `@tailwindcss/vite@^4.0.0` installed together. Conflicting v3/v4 patterns.
- **No CI/CD** — zero GitHub Actions, Dockerfile, or deployment automation.
- **No JS linting** — no ESLint, Prettier, or TypeScript config.

## UNIQUE STYLES
- **Question versioning system** — migrations from 2026-02-20 renamed `pertanyaans` → `pertanyaan_templates`, added `jadwal_pertanyaans` pivot for schedule-based question versioning
- **KlasifikasiPenilaian** — classification system maps final scores to rating tiers (active, ordered by `urutan`)
- **HasilPenilaian** — sync'd result table updated via `PenilaianService::syncHasilPenilaian()` after each category scoring
- **Pesan (messages)** — many-to-many with pivot `pesan_user` table, tracks `dibaca_para` read timestamp
- **Profile photo** — `profile_photo_path` on users, uploaded to storage

## COMMANDS
```bash
# Dev server (4 processes concurrently)
composer dev

# Run tests
composer test          # or: php artisan test

# Frontend build
npm run dev            # Vite dev server (HMR)
npm run build          # Production bundle

# Laravel CLI
php artisan migrate
php artisan route:list
php artisan tinker
```

## NOTES
- Production URL: `https://emonev-kip.banjarnegarakab.go.id`
- Two auth systems: `web` guard (User model) and `admin` guard (Admin model)
- `livewire.php` config: SPA mode enabled, `legacy_model_binding: false`, default layout `components.layouts.app`
- `phpunit.xml`: SQLite `:memory:`, BCRYPT rounds=4 for speed, array cache/session
- No PHP LSP available — no Intelephense installed. Manual code review required for PHP files.
- `laravel/sail` in dev deps but no Docker config exists.
