# TA Cloud

> A thesis management system for universities — managing skripsi submissions, bimbingan, sidang requests, document versioning, grading, and real-time notifications.

![Laravel](https://img.shields.io/badge/Laravel-13-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3-blue?style=flat-square)
![Tailwind](https://img.shields.io/badge/Tailwind-v4-teal?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-gray?style=flat-square)

---

## Roles

| Role | Access scope |
|------|-------------|
| Kaprodi | Global operator — full CRUD, assignments, approvals |
| Dosen | Reviewer-scoped — assigned skripsi only |
| Mahasiswa | Owner-scoped — own records only |

---

## Feature status

| Feature | Status |
|---------|--------|
| Realtime notification system | ✅ Done |
| Kaprodi, Dosen & Mahasiswa layers | ✅ Done |
| Document versioning & bimbingan flow | ✅ Done |
| Reusable Blade UI components | ✅ Done |
| Final submission wiring | 🔄 In progress |
| Skripsi export / rekap | 📋 Todo |
| Google OAuth login | 📋 Todo |
| Super Admin & advanced RBAC | 📋 Todo |

---

## Tech stack

**Backend** — Laravel 13, PHP 8.3, Eloquent ORM, Laravel Reverb

**Frontend** — Blade views, Tailwind CSS v4, Alpine.js, Vite

**Infra & QA** — MySQL, Laravel Breeze, Pest, Laravel Herd

---

## Getting started

```bash
# Clone and install
git clone https://github.com/vinnoch/ta-cloud.git
cd ta-cloud
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# Run
npm run dev
php artisan reverb:start
```

---

## Project structure

```
app/
├── Http/Controllers/
│   ├── Kaprodi/       # Kaprodi-scoped controllers
│   ├── Dosen/         # Dosen-scoped controllers
│   └── Mahasiswa/     # Mahasiswa-scoped controllers
├── Models/            # Eloquent models
├── Services/          # Business logic services
└── Notifications/     # Laravel notification classes
resources/views/
├── kaprodi/           # Kaprodi views
├── dosen/             # Dosen views
├── mahasiswa/         # Mahasiswa views
└── partials/          # Reusable Blade components
routes/web/            # Role-scoped route files
```

---

## License

MIT