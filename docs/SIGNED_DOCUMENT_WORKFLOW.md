# Luồng trình ký BM.03 (signed document)

## Tóm tắt

Sau khi phiếu `approved`, người đề xuất tải PDF BM.03, ký ngoài hệ thống, upload bản scan (`signed_paper`). Mỗi lần upload tạo **phiên bản mới** (`signed_document_versions`), không ghi đè file cũ.

Hàng đợi `document-processing` chạy OCR (stub) và heuristic chữ ký (GD trên ảnh; PDF thường `manual_review`).

## Env

| Biến | Mặc định | Ý nghĩa |
|------|----------|---------|
| `DISPATCH_DOCUMENT_QUEUE` | `document-processing` | Queue pipeline |
| `DISPATCH_SIGNED_DOCUMENT_USE_QUEUE` | `true` | OCR async sau upload |
| `DISPATCH_SIGNATURE_AUTO_PASS_MIN_SCORE` | `0.75` | Ngưỡng auto_pass |
| `DISPATCH_SIGNATURE_MANUAL_REVIEW_MIN_SCORE` | `0.35` | Ngưỡng manual_review |
| `DISPATCH_PAPER_RECEIVED_REQUIRES_VERIFIED` | `false` | Bắt buộc verify trước paper-received |

Worker: `php artisan queue:work --queue=document-processing,default`

## API Portal

- `POST /api/portal/dispatch-requests/{id}/signed-paper` (+ idempotency)
- `GET /api/portal/dispatch-requests/{id}/signed-documents`
- `PATCH /api/portal/dispatch-requests/{id}/signing-workflow`

## API Staff

- `GET /api/dispatch-requests/{id}/signed-documents`
- `POST /api/dispatch-requests/{id}/signed-documents`
- `POST /api/signed-document-versions/{id}/ocr`
- `POST /api/signed-document-versions/{id}/verify` (`request.paper.manage`)

## Bảng

- `signed_document_versions` — phiên bản + OCR/chữ ký
- `signed_document_verifications` — lịch sử quyết định
- Cache trên `dispatch_requests`: `current_signed_version_id`, `verification_status`, …

Chi tiết cột: `docs/DATABASE_SPECIFICATION.md` (mục signed document).
