# Brief: Khó khăn khi import dữ liệu cũ — `dispatch-request:import-legacy`

> File Excel: `database/Test_Phiếu đề xuất ghi nhận.xlsx`  
> Command: `php artisan dispatch-request:import-legacy [--dry-run] [--file=...] [--sheet=...]`  
> Stack: `LegacyDispatchRequestImporter`, `PassengerRowMapper`, `CargoRowMapper`,  
> `LegacyVehicleImporter`, `LegacyTripCostImporter`, `LegacyTripRecordImporter`

---

## Tóm tắt nhanh

| Mức độ | Vấn đề | Sheet |
|--------|--------|-------|
| 🔴 Cao | Ngày nhập thủ công dạng text → skip cả row | 1, 2 |
| 🔴 Cao | Status không khớp → không tạo Trip, sai status | 1, 2 |
| 🔴 Cao | Sheet 4 & 5 không có idempotency key → duplicate khi chạy lại | 4, 5 |
| 🟠 Trung | Lỗi bị nuốt thầm lặng — không biết row nào, lỗi gì | 1–5 |
| 🟠 Trung | Merged cells → các hàng phụ bị bỏ qua hoàn toàn | 1, 2 |
| 🟠 Trung | Biển số không chuẩn hoá đồng đều → mất link vehicle | 1, 5 |
| 🟡 Thấp | Import key theo row number → thay đổi khi thêm/xóa dòng | 1, 2 |
| 🟡 Thấp | `resolveFilePath()` lấy file Excel đầu tiên ngẫu nhiên | all |

---

## Chi tiết từng vấn đề

---

### 1. 🔴 Ngày nhập dạng text → bỏ qua cả hàng

**File:** `PassengerRowMapper.php:135`, `CargoRowMapper.php:95`

```php
$dateDepart = $this->cellDate($cells, self::C_DATE_DEP);
if ($dateDepart === null) {
    return null;  // ← bỏ qua hàng, không báo lý do
}
```

`cellDate()` chỉ xử lý được:
- `DateTimeInterface` (OpenSpout tự parse ô định dạng Date).
- Excel serial number dạng float trong khoảng `40000–100000`.

**Không xử lý được:**
- Nhập tay dạng chuỗi: `"15/06/2023"`, `"15-6-23"`, `"06/2023"`, `"15.06.2023"`.
- Ô để trống nhưng ngày được merge từ dòng trên.
- Ô chứa text lẫn ngày: `"đi ngày 15/6"`.

**Hậu quả:** Toàn bộ hàng bị skip hoàn toàn — không có `DispatchRequest` lẫn `Trip`.

**Gợi ý fix:** Thêm fallback parse string trong `cellDate()`:
```php
if (is_string($val)) {
    foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'm/Y'] as $fmt) {
        $dt = \DateTime::createFromFormat($fmt, trim($val));
        if ($dt) return Carbon::instance($dt)->startOfDay();
    }
}
```

---

### 2. 🔴 Status không khớp → sai trạng thái, không tạo Trip

**File:** `PassengerRowMapper.php:100`, `CargoRowMapper.php:72`

`STATUS_MAP` của Sheet 1 chỉ nhận đúng 8 giá trị:

```
done | hủy | huỷ | báo xe ok | in process | chờ thêm thông tin | taxi | pending
```

Sheet 2 còn ít hơn — chỉ 5 giá trị, thiếu `báo xe ok`, `chờ thêm thông tin`, `taxi`.

**Các giá trị thực tế hay gặp trong Excel tay:**
- Khoảng trắng thừa: `"done "`, `"  Hủy"`
- Viết tắt: `"OK"`, `"Xong"`, `"Đã xong"`, `"✓"`, `"x"`
- Tiếng Anh biến thể: `"Cancel"`, `"Cancelled"`, `"Done ✓"`
- Ghi chú kèm: `"Done – thanh toán rồi"`, `"Hủy (trùng lịch)"`

**Hậu quả nếu không match:** `$statusEntry = null` → `DispatchRequest` vẫn được tạo với `status = 'pending'`, **nhưng không có `Trip`**.

**Gợi ý:** Dùng fuzzy match (`str_contains`) thay vì exact match, hoặc log warning rõ ràng theo row.

---

### 3. 🔴 Sheet 4 và Sheet 5 không có idempotency key → duplicate khi chạy lại

**File:** `LegacyTripCostImporter.php`, `LegacyTripRecordImporter.php`

Sheet 1 & 2 có cơ chế guard bằng `_import_key` (`pax_{rowNum}`, `cargo_{rowNum}`) và query check trước khi insert.

**Sheet 4 và Sheet 5 không có** — mỗi lần chạy lại command sẽ insert thêm toàn bộ `TripCost` rows.

**Hậu quả:** Chạy lại để fix lỗi hoặc thêm dữ liệu → duplicate records trong `trip_costs` không phát hiện được.

**Gợi ý:** Thêm `source_ref` (vd: `"legacy_cost_row_{rowNum}"`) + unique index, hoặc `firstOrCreate` trên cặp `(reported_on, amount, type, description)`.

---

### 4. 🟠 Lỗi bị nuốt thầm lặng

**File:** `LegacyDispatchRequestImporter.php:127`

```php
try {
    $mapped = $mapper->map($cells, $rowNum, $systemUserId, $vehicleMap);
} catch (\Throwable $e) {
    $stats['errors']++;
    continue;  // ← không log gì, không biết row nào, lỗi gì
}
```

**Hậu quả:** Command chạy xong báo `errors: 12` nhưng không biết:
- Row nào trong Excel bị lỗi.
- Lỗi là gì (null constraint, enum invalid, type mismatch...).

**Gợi ý:**
```php
} catch (\Throwable $e) {
    $stats['errors']++;
    logger()->warning("LegacyImport: row {$rowNum} — {$e->getMessage()}");
    continue;
}
```

---

### 5. 🟠 Merged cells → hàng phụ bị bỏ qua

**Context:** Excel hay merge cells theo chiều dọc (một STT cho nhiều chuyến cùng ngày, hoặc gộp ô ngày).

**OpenSpout behaviour:** Chỉ cell đầu tiên của merge có giá trị; các cell còn lại trả về `null` hoặc empty string.

**Hậu quả với `isRowEmpty()`:**
```php
if ($this->isRowEmpty($cells, [self::C_STT, self::C_ORIGIN, self::C_STATUS])) {
    return null; // ← hàng 2-N của merge bị drop
}
```

Nếu STT bị merge theo nhóm tháng/tuần, các hàng phụ sẽ không có STT → bị skip toàn bộ.

**Gợi ý:** Carry-forward merge value — giữ lại giá trị của cell merge từ hàng trước nếu hàng hiện tại empty nhưng STATUS hợp lệ.

---

### 6. 🟠 Biển số xe không chuẩn hoá đồng đều

**File:** `LegacyTripRecordImporter.php:64`, `LegacyVehicleImporter.php:100`, `PassengerRowMapper.php:208`

Normalization hiện tại:
```php
mb_strtoupper(preg_replace('/\s+/', ' ', trim($raw)))
```

Không xử lý được:
- Dấu gạch ngang vs khoảng trắng: `51A-796.68` vs `51A 796.68`.
- Dấu chấm: `51A.796.68` (một số biển số cũ có dấu chấm).
- Ký tự full-width: `５１Ａ` (copy từ PDF).

**Hậu quả nghiêm trọng cho Sheet 5:**
```php
private const SHEET_VEHICLE_PLATE = '51A 796.68'; // hardcoded
$vehicleId = $vehicleMap[self::SHEET_VEHICLE_PLATE] ?? null;
```

Nếu Sheet 3 import plate là `"51A-796.68"` (có dấu gạch ngang) → key trong `vehicleMap` là `"51A-796.68"` → không match `"51A 796.68"` → `vehicleId = null` → toàn bộ TripCost của Sheet 5 không có `vehicle_id`, và odometer không được cập nhật.

**Gợi ý:** Strip tất cả ký tự không phải chữ-số-chấm:
```php
preg_replace('/[^A-Z0-9.]/u', '', mb_strtoupper(trim($raw)))
// "51A-796.68" → "51A796.68"; "51A 796.68" → "51A796.68"
```

---

### 7. 🟡 Import key theo row number — vỡ khi file thay đổi

**File:** `PassengerRowMapper.php:170`, `CargoRowMapper.php:132`

```php
'_import_key' => 'pax_'.$rowNum,
```

Idempotency check dựa vào `$rowNum` (số thứ tự dòng Excel tuyệt đối).

**Kịch bản vỡ:**
1. Import lần đầu: row 5 → `pax_5` ✓
2. Sếp thêm 1 hàng mới vào đầu file → row cũ trở thành row 6
3. Import lần 2: `pax_6` không tồn tại → insert lại toàn bộ → **duplicate**

**Gợi ý:** Dùng hash nội dung làm key:
```php
'_import_key' => 'pax_'.md5($origin.$dateDepartStr.$personName),
```

---

### 8. 🟡 `resolveFilePath()` lấy file Excel ngẫu nhiên

**File:** `ImportLegacyDispatchRequestsCommand.php:138`

```php
$found = glob(base_path('database/*.xlsx'));
return $found ? $found[0] : null;
```

`glob()` trả về mảng theo thứ tự hệ thống file (không phải alphabetical hoàn toàn). Nếu `database/` có nhiều file `.xlsx` (backup, bản nháp, bản test) → có thể chọn nhầm file.

**Gợi ý:** Thêm warning khi có nhiều file, hoặc sort theo mtime để lấy mới nhất.

---

### 9. 🟡 Hai lần `ensureSystemUser()` trong cùng một lần chạy

**File:** `ImportLegacyDispatchRequestsCommand.php:82` và `LegacyDispatchRequestImporter.php:52`

Command gọi `ensureSystemUser()` để lấy `$systemUserId`, sau đó truyền vào các importer. Nhưng `LegacyDispatchRequestImporter` cũng tự gọi `ensureSystemUser()` bên trong — thừa một DB query.

**Hậu quả:** Không ảnh hưởng logic (cùng tìm/tạo cùng email), chỉ lãng phí 1 query + 1 bcrypt hash per run.

---

### 10. 🟡 Ô text bị Excel auto-convert thành Date

**File:** `CellValueParser.php:16`

```php
if ($val instanceof \DateTimeInterface) {
    return null; // ← cellStr() trả null nếu là DateTimeInterface
}
```

Nếu địa điểm được nhập như `"10/6"`, `"12/7"` → Excel có thể auto-parse thành ngày → `cellStr(cells, C_ORIGIN)` trả `null` → `origin = null` trong DispatchRequest.

---

## Luồng chạy lý tưởng để test

```bash
# 1. Dry-run toàn bộ — kiểm tra parsed/skipped/errors
php artisan dispatch-request:import-legacy \
  --file="database/Test_Phiếu đề xuất ghi nhận.xlsx" \
  --dry-run

# 2. Chỉ import xe trước — xác nhận plate map
php artisan dispatch-request:import-legacy \
  --file="database/Test_Phiếu đề xuất ghi nhận.xlsx" \
  --sheet=vehicles \
  --dry-run

# 3. Import thật — thứ tự: vehicles → passenger → cargo → costs → odometer
php artisan dispatch-request:import-legacy \
  --file="database/Test_Phiếu đề xuất ghi nhận.xlsx"
```

---

## Checklist sửa trước khi import thật

- [ ] **Kiểm tra file Excel:** Mở file, xác nhận các cột ngày (`Ngày nhận`, `Ngày đi`, `Ngày về`) có format **Date** (không phải General/Text). Nếu là text → `Format Cells → Date`.
- [ ] **Kiểm tra cột Status:** Lọc cột `Trạng thái xử lý` → xem có giá trị lạ nào ngoài whitelist không. Chuẩn hoá về: `done | hủy | báo xe ok | in process | chờ thêm thông tin | taxi | pending`.
- [ ] **Kiểm tra biển số:** Cột `Thông tin xe` (Sheet 1 col X) — đảm bảo format nhất quán (vd: luôn dùng khoảng trắng hoặc luôn dùng gạch ngang).
- [ ] **Không có file xlsx nào khác trong `database/`** trước khi chạy (hoặc dùng `--file=` rõ ràng).
- [ ] **Chạy `--dry-run` trước**, đọc số `errors` và `skipped` — nếu cao bất thường → điều tra trước khi ghi DB.
- [ ] **Backup DB** trước khi chạy thật lần đầu.
