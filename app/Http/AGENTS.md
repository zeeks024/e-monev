# HTTP Layer — app/Http

## OVERVIEW
Controllers, middleware, and form requests handling HTTP layer for E-Monev KIP.

## STRUCTURE
```
Http/
├── Controllers/
│   ├── Controller.php          # Base controller (empty, extends Laravel's)
│   ├── LandingPageController.php
│   ├── ProfileController.php   # User profile CRUD (edit, update, destroy)
│   ├── Auth/
│   │   └── VerifyEmailController.php  # Email verification handler
│   └── Admin/
│       ├── Auth/
│       │   └── AuthenticatedSessionController.php  # Admin login (create, store, destroy)
│       └── LaporanController.php  # PDF report generation (unduhPdf, unduhPdfPerBadanPublik)
├── Middleware/
│   ├── Authenticate.php        # Custom auth redirect (extends Laravel's)
│   ├── AdminMiddleware.php     # Checks Auth::guard('admin'), redirects to admin.login
│   └── RedirectIfAuthenticated.php  # Custom guest redirect
└── Requests/
    ├── Auth/
    │   └── LoginRequest.php    # User login validation
    └── ProfileUpdateRequest.php  # Profile update validation
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| Admin login flow | `Controllers/Admin/Auth/AuthenticatedSessionController.php` | Uses `admin` guard, `guest:admin` middleware |
| PDF generation | `Controllers/Admin/LaporanController.php` | Uses `barryvdh/laravel-dompdf` |
| User profile | `Controllers/ProfileController.php` | Standard Breeze pattern |
| Auth middleware override | `Middleware/Authenticate.php` | Custom — not Laravel default |
| Admin gatekeeper | `Middleware/AdminMiddleware.php` | Single check: `Auth::guard('admin')->check()` |

## CONVENTIONS
- Most pages use **Volt functional components** (Blade-only), NOT traditional controllers
- Controllers reserved for: admin auth, profile CRUD, email verification, PDF export
- Form requests used for login + profile validation (not inline in controllers)
- Custom middleware aliases registered in `bootstrap/app.php`: `auth`, `admin`, `guest`

## ANTI-PATTERNS
- `AdminMiddleware` redirects to `admin.login` on failure but does NOT return 403 — silent redirect may confuse API consumers
- `Authenticate.php` extends Laravel's middleware — verify custom redirect logic doesn't conflict with Breeze defaults
- No API controllers — this is a server-rendered app (expected, but note for future)
