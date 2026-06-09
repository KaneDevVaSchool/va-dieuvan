# VA Điều Vận — CLAUDE.md

Đây là hướng dẫn cho Claude Code khi làm việc với dự án này.

## Đọc trước khi code

1. `.claude/project-context.md` — Tech stack, domain vocabulary, constraints
2. `.claude/architecture-rules.md` — Quy tắc kiến trúc BẮT BUỘC
3. `.claude/coding-standards.md` — Coding conventions
4. `.claude/laravel-rules.md` — Laravel-specific patterns
5. `.claude/database-rules.md` — Database rules
6. `.claude/ui-rules.md` — Vue 3 + Tailwind rules

## Quy tắc tuyệt đối

- Controller CHỈ: validate → authorize → service call → response
- Mọi mutation PHẢI có `DB::transaction()`
- Mọi mutation PHẢI có `AuditLogger::log()`
- Không lazy load trong loops → luôn eager load
- Financial lock check trước khi sửa dữ liệu đã paid

## Commands hữu ích

```bash
# PHP
./vendor/bin/pint              # Format PHP
php artisan test               # Chạy tests
php artisan test --parallel    # Tests song song
php artisan migrate            # Migrate DB

# Node
npm run dev                    # Dev server
npm run build                  # Build production
npm run test:e2e               # E2E tests
npm run lint                   # ESLint

# Laravel
php artisan route:list         # Xem routes
php artisan queue:work         # Queue worker
```

## Thư mục quan trọng

```
app/Services/        — Business logic (đây là nơi chính)
app/Http/Controllers/Api/  — Thin controllers
app/Models/          — Eloquent models
app/Support/         — Utility classes (locks, visibility, etc.)
resources/js/composables/  — Vue composables (business logic FE)
resources/js/views/        — Vue pages
tests/Feature/       — PHPUnit feature tests
tests/e2e/           — Playwright E2E tests
docs/                — Documentation đầy đủ
```

## Tài liệu

- Architecture: `docs/architecture/system-overview.md`
- Database: `docs/database/schema.md`
- API: `docs/api/endpoints.md`
- Business rules: `docs/business/rules.md`
- Known issues: `docs/audit/technical-debt.md`
