# Project Context — VA Điều Vận (Cursor)

Xem chi tiết đầy đủ tại `.claude/project-context.md`.

## Quick Reference

**Stack**: Laravel 10 + Vue 3 SPA + MySQL + Tailwind CSS  
**Domain**: Hệ thống quản lý điều vận xe cho trường học VA

## Critical Rules

1. Controller CHỈ nhận request → gọi service → trả response
2. Business logic PHẢI trong app/Services/{Domain}/
3. Mọi mutation PHẢI trong DB::transaction()
4. Mọi mutation PHẢI có AuditLogger::log()
5. Không lazy load trong loops — luôn eager load
6. Optimistic lock: check lock_version trước khi update concurrent entities
7. Financial lock: check FinancialDataLock trước khi sửa paid trips

## File Organization

```
Controllers → app/Http/Controllers/Api/{Domain}/
Services    → app/Services/{Domain}/
Models      → app/Models/
FormReq     → app/Http/Requests/Api/{Domain}/
Views       → resources/js/views/
Components  → resources/js/components/
Composables → resources/js/composables/
Tests       → tests/Feature/ và tests/Unit/
```

## Key Services

| Service | Trách nhiệm |
|---------|------------|
| AttendanceService | Điểm danh TP |
| DispatchingService | Gán xe/tài xế |
| TripExecutionService | Lifecycle chuyến |
| ImportExecutorService | Import học sinh TP |
| AuditLogger | Ghi log nghiệp vụ |
| WebPushSender | Push notifications |
