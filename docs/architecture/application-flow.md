# Luồng xử lý ứng dụng

## 1. Luồng yêu cầu điều vận (Dispatch Request Flow)

```
[Người dùng/Portal]
      │
      ▼
POST /api/dispatch-requests
      │
      ▼
DispatchRequestController::store()
      │
      ├── FormRequest validation (StoreDispatchRequestRequest)
      ├── Authorization check
      │
      ▼
DispatchingService::createRequest()
      │
      ├── DB::transaction()
      ├── DispatchRequest::create()
      ├── Handle attachments
      ├── Notify dispatch staff (NewDispatchRequestNotification)
      └── AuditLogger::log()
      │
      ▼
[Status: pending]
      │
      ▼
Dispatcher reviews → POST /api/trips/{id}/assign
      │
DispatchingService::assignResources()
      │
      ├── Validate vehicle/driver availability
      ├── Trip::update(vehicle_id, driver_id, status='assigned')
      ├── TripAssignedNotification → Driver
      └── AuditLogger::log()
      │
      ▼
[Status: assigned → driver_confirmed → in_progress → completed]
```

## 2. Luồng Transport Program (TP)

```
[Admin tạo chương trình]
      │
POST /api/transport-programs
      ▼
TpProgramController::store()
      │
CreateTransportProgramAction::execute()
      │
      ├── TpProgram::create()
      ├── ProgramDayGeneratorService::generate()  ← tạo TpProgramDay records
      └── TpAuditLogger::log()
      │
      ▼
[Import học sinh]
POST /api/tp-import/*
      │
TpImportController → ImportParserService → ImportValidatorService → ImportExecutorService
      │
      ▼
[Đăng ký học sinh: TpEnrollment]
      │
      ▼
[Ngày thực hiện: TpProgramDay]
      │
      ▼
[Tài xế xác nhận: DriverTpDayConfirmController]
      │
      ▼
[Bắt đầu chuyến: StartTripExecutionAction]
      │
      ├── TpTripExecution::create(status='in_progress')
      ├── TpTripStudentLog::insert() ← cho mỗi học sinh enrolled
      └── TpAuditLogger::log()
      │
      ▼
[Điểm danh: AttendanceService]
      │
      ├── markAbsent() / unmarkAbsent()
      ├── confirmAttendance() ← với optimistic locking
      └── syncExecutionLogIfInProgress()
      │
      ▼
[Hoàn thành chuyến: DriverTripCompleteController]
```

## 3. Luồng xác thực

```
[Google OAuth]
      │
GET /auth/google → redirect to Google
GET /auth/google/callback
      │
GoogleAuthController::callback()
      │
      ├── Socialite::driver('google')->user()
      ├── User::updateOrCreate(google_id)
      └── createToken() → Sanctum token
      │
      ▼
Frontend lưu token → localStorage
      │
API requests: Authorization: Bearer {token}
      │
      ▼
Sanctum middleware → $request->user()
```

## 4. Luồng thông báo

```
[Sự kiện xảy ra]
      │
      ├── Database notification (Notifiable)
      │         │
      │         ▼
      │   SendWebPushOnDatabaseNotification (Listener)
      │         │
      │         ▼
      │   WebPushSender::send() → VAPID push
      │
      └── Email notification (Mailable)
                │
                ▼
          Mail::send() → SMTP
```

## 5. Luồng import học sinh TP

```
[Upload Excel file]
      │
POST /api/trips/tp-import
      │
      ▼
ImportParserService::parse()
      │
      ├── Read Excel rows
      ├── Map columns (ImportMappingController)
      └── Store raw data → TpImportBatch + TpImportRow
      │
      ▼
ImportValidatorService::validate()
      │
      ├── Check required fields
      ├── Validate student codes
      └── Flag errors → TpImportRow.errors
      │
      ▼
ImportAutoFixService::fix()  ← optional
      │
      ▼
ImportExecutorService::execute()
      │
      ├── TpStudent::updateOrCreate()
      ├── TpEnrollment::create()
      └── Update TpImportBatch.status = 'completed'
```

## 6. Idempotency Flow

Các mutation quan trọng đều hỗ trợ idempotency key:

```
Client gửi: X-Idempotency-Key: {uuid}
      │
IdempotencyKey middleware
      │
      ├── Check IdempotentRequest::find(key)
      ├── Nếu exists → return cached response
      └── Nếu không → execute → cache response
```
