# Database Schema — VA Điều Vận

## Nhóm bảng chính

### 1. Users & Authentication

```sql
users
├── id (bigint PK)
├── name (varchar)
├── email (varchar UNIQUE)
├── google_id (varchar UNIQUE nullable)
├── avatar_url (varchar nullable)
├── password (varchar nullable)
├── phone (varchar nullable)
├── employee_code (varchar nullable)
├── department_id (FK → departments)
├── is_active (boolean default true)
├── primary_role_name (varchar nullable)
├── primary_role_id (bigint nullable)
├── email_verified_at (timestamp nullable)
├── remember_token
├── created_at, updated_at

departments
├── id (bigint PK)
├── name (varchar)
├── code (varchar nullable)
├── created_at, updated_at
```

### 2. Dispatch Core

```sql
dispatch_requests
├── id (bigint PK)
├── requester_id (FK → users)
├── department_id (FK → departments nullable)
├── status (enum: pending|approved|rejected|cancelled)
├── trip_type (enum: door_to_door|point_to_point|business|cargo nullable)
├── source_channel (varchar: portal|wizard|recurring)
├── origin (text)
├── destination (text)
├── depart_at (datetime nullable)
├── arrive_by (datetime nullable)
├── passenger_count (int default 1)
├── is_urgent (boolean default false)
├── notes (text nullable)
├── approved_by (FK → users nullable)
├── rejection_reason (text nullable)
├── paper_status (enum: pending|received|na)
├── paper_received_at (timestamp nullable)
├── paper_reference (varchar nullable)
├── wizard_snapshot (json nullable)  ← passenger/cargo rows
├── dept_head_id (FK → users nullable)
├── dept_head_decision (enum nullable)
├── recurring_parent_id (FK → dispatch_requests nullable)
├── deleted_at (soft delete)
├── created_at, updated_at

trips
├── id (bigint PK)
├── dispatch_request_id (FK → dispatch_requests)
├── dispatcher_id (FK → users nullable)
├── vehicle_id (FK → vehicles nullable)
├── driver_id (FK → drivers nullable)
├── transport_provider_id (FK → transport_providers nullable)
├── external_vehicle_ref (varchar nullable)
├── external_driver_ref (varchar nullable)
├── status (enum: pending|approved|assigned|driver_confirmed|in_progress|completed|incident|cancelled)
├── depart_at (datetime nullable)
├── arrive_by (datetime nullable)
├── started_at (datetime nullable)
├── completed_at (datetime nullable)
├── lock_version (int default 0)  ← optimistic locking
├── passenger_check_ins (json nullable)
├── supplement_transports (json nullable)
├── schedule_assignments (json nullable)
├── payment_status (enum: unpaid|paid default unpaid)
├── paid_at (timestamp nullable)
├── recurring_package_session_consumed_at (timestamp nullable)
├── created_at, updated_at

trip_records
├── id (bigint PK)
├── trip_id (FK → trips UNIQUE)
├── distance_km (decimal nullable)
├── odometer_start (int nullable)
├── odometer_end (int nullable)
├── notes (text nullable)
├── created_at, updated_at

trip_costs
├── id (bigint PK)
├── trip_id (FK → trips)
├── cost_type (varchar)
├── amount (decimal)
├── notes (text nullable)
├── created_by (FK → users nullable)
├── created_at, updated_at

trip_events
├── id (bigint PK)
├── trip_id (FK → trips)
├── event_type (varchar)
├── notes (text nullable)
├── creator_id (FK → users nullable)
├── created_at, updated_at

trip_passengers
├── id (bigint PK)
├── trip_id (FK → trips)
├── name (varchar)
├── phone (varchar nullable)
├── note (text nullable)
├── created_at, updated_at
```

### 3. Resources (Xe, Tài xế)

```sql
vehicles
├── id (bigint PK)
├── license_plate (varchar UNIQUE)
├── status (enum: available|unavailable|maintenance)
├── type (varchar: bus|van|car|...)
├── seat_count (int)
├── odometer_km (int default 0)
├── transport_provider_id (FK nullable)
├── created_at, updated_at

drivers
├── id (bigint PK)
├── user_id (FK → users nullable)
├── full_name (varchar)
├── phone (varchar)
├── email (varchar nullable)
├── odometer_km (int default 0)
├── transport_provider_id (FK nullable)
├── created_at, updated_at

transport_providers
├── id (bigint PK)
├── name (varchar)
├── type (enum: vendor|taxi nullable)
├── created_at, updated_at
```

### 4. Transport Program (TP)

```sql
tp_programs
├── id (bigint PK)
├── name (varchar)
├── status (enum: draft|active|paused|completed)
├── start_date (date)
├── end_date (date)
├── departure_time (time)
├── return_time (time nullable)
├── default_driver_id (FK → drivers nullable)
├── default_vehicle_id (FK → vehicles nullable)
├── settings (json nullable)  ← per-shift config
├── created_by (FK → users)
├── created_at, updated_at

tp_program_days
├── id (bigint PK)
├── program_id (FK → tp_programs)
├── scheduled_date (date)
├── driver_id (FK → drivers nullable)  ← override
├── vehicle_id (FK → vehicles nullable)
├── expected_count (int default 0)
├── attendance_status (varchar default 'not_started')
├── attendance_lock_version (int default 0)
├── attendance_confirmed_at (timestamp nullable)
├── attendance_confirmed_by (FK → users nullable)
├── [morning|afternoon]_attendance_* (shift-specific columns)
├── created_at, updated_at

tp_students
├── id (bigint PK)
├── code (varchar UNIQUE)
├── full_name (varchar)
├── grade (varchar nullable)
├── class_name (varchar nullable)
├── parent_phone (varchar nullable)
├── created_at, updated_at

tp_enrollments
├── id (bigint PK)
├── program_id (FK → tp_programs)
├── student_id (FK → tp_students)
├── pickup_point (varchar nullable)
├── enrolled_at (date)
├── unenrolled_at (date nullable)
├── created_at, updated_at

tp_day_absences
├── id (bigint PK)
├── program_day_id (FK → tp_program_days)
├── student_id (FK → tp_students)
├── shift (varchar default 'all')
├── absence_type (varchar: parent_notified|no_notice|late_cancel|...)
├── category (varchar: excused|unexcused)
├── reason_code (varchar nullable)
├── absence_reason (text nullable)
├── recorded_by (FK → users nullable)
├── recorded_at (timestamp)
├── source (varchar: dispatcher|driver|parent)
├── created_at, updated_at

tp_trip_executions
├── id (bigint PK)
├── program_day_id (FK → tp_program_days UNIQUE per shift)
├── shift (varchar)
├── status (enum: pending|in_progress|completed)
├── driver_id (FK → drivers nullable)
├── vehicle_id (FK → vehicles nullable)
├── started_at (timestamp nullable)
├── completed_at (timestamp nullable)
├── total_absent (int default 0)
├── created_at, updated_at

tp_trip_student_logs
├── id (bigint PK)
├── execution_id (FK → tp_trip_executions)
├── student_id (FK → tp_students)
├── initial_status (varchar)
├── final_status (varchar: pending|boarded|absent|...)
├── boarded_at (timestamp nullable)
├── absent_at (timestamp nullable)
├── absence_type (varchar nullable)
├── created_at, updated_at
```

### 5. Permission & Roles

```sql
roles (Spatie)
├── id, name, guard_name, created_at, updated_at

permissions (Spatie)
├── id, name, guard_name, created_at, updated_at

model_has_roles (Spatie)
├── role_id, model_type, model_id

role_has_permissions (Spatie)
├── permission_id, role_id

role_assignments (custom)
├── id, user_id, role_id, assigned_by, created_at, updated_at
```

### 6. Notifications & Audit

```sql
notifications (Laravel default)
├── id (uuid PK)
├── type (varchar)
├── notifiable_type, notifiable_id
├── data (json)
├── read_at (timestamp nullable)
├── created_at, updated_at

audit_logs
├── id (bigint PK)
├── actor_id (FK → users nullable)
├── event (varchar)
├── auditable_type, auditable_id
├── before (json nullable)
├── after (json nullable)
├── metadata (json nullable)
├── ip_address (varchar nullable)
├── created_at, updated_at

push_subscriptions
├── id (bigint PK)
├── user_id (FK → users)
├── endpoint (text)
├── public_key (text)
├── auth_token (text)
├── created_at, updated_at
```

### 7. Financial

```sql
trip_costs (đã liệt kê trên)

reconciliation_periods
├── id, name, start_date, end_date, status, created_at, updated_at

payments
├── id, trip_id, amount, paid_at, reference, created_at, updated_at

cargo_fare_rates, passenger_fare_rates
reference_pricing_revisions, pricing_notes
dispatch_packages (recurring budget)
```

### 8. Misc

```sql
feature_toggles
├── id, name (UNIQUE), enabled (boolean), created_at, updated_at

dispatch_settings
├── id, key (UNIQUE), value (json), created_at, updated_at

idempotent_requests
├── id (uuid PK), key (varchar UNIQUE), response (json), created_at

menu_items
attachments
signed_document_versions, signed_document_verifications
compliance_documents (driver & vehicle)
```
