# Routes — routes/

## OVERVIEW
Route definitions split into 3 files: `web.php` (user), `admin.php` (admin panel), `auth.php` (authentication).

## STRUCTURE
```
routes/
├── web.php         # User-facing routes (116 lines)
├── admin.php       # Admin panel routes (87 lines)
├── auth.php        # Auth routes (31 lines, loaded from web.php)
└── console.php     # Artisan console commands
```

## WHERE TO LOOK
| Route Group | File | Key Routes |
|-------------|------|------------|
| Landing page | `web.php` | `GET /` → inline closure with stats |
| User dashboard | `web.php` | `GET /dashboard` → `pages.dashboard` (Volt) |
| Questionnaire | `web.php` | `/kuesioner`, `/kuesioner/jawab` (Volt) |
| Profile | `web.php` | `/profile` → `ProfileController` |
| User auth | `auth.php` | `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email` |
| Admin login | `admin.php` | `GET/POST /admin/login` → `AuthenticatedSessionController` |
| Admin panel | `admin.php` | `/admin/dashboard`, `/admin/kuesioner/*`, `/admin/penilaian/*`, `/admin/laporan/*`, etc. |
| Admin logout | `admin.php` | `POST /admin/logout` |

## CONVENTIONS
- **Volt routes**: `Volt::route('/path', 'pages.component-name')` for most pages
- **Controller routes**: `Route::get('/path', [Controller::class, 'method'])` for CRUD/PDF
- **Middleware groups**: `middleware('auth')`, `middleware('admin')`, `middleware('guest')`, `middleware('guest:admin')`
- **Admin prefix**: All admin routes under `prefix('admin')`, named `admin.*`
- Auth routes loaded via `require __DIR__.'/auth.php'` in `web.php`
- Admin routes loaded from `bootstrap/app.php` (AND `AppServiceProvider::boot()` — see anti-patterns)

## ANTI-PATTERNS
- **Admin routes registered TWICE** — `bootstrap/app.php` + `AppServiceProvider::boot()` both load `admin.php`. Remove one.
- **Duplicate `/notifikasi` route** in `web.php` — line 84 (with auth) and line 101 (without auth). Second overwrites first.
- **Unused import** in `web.php` line 7: `AuthenticatedSessionController as AdminAuth` — used in `admin.php`, not here.
- **Dead comment** in `web.php` line 116: `// Panggil semua rute dari file admin.php` — admin routes moved to `bootstrap/app.php`.
