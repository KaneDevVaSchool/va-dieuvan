<?php

namespace App\Services\Resources;

use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use App\Services\Auditing\AuditLogger;
use App\Support\Roles;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Permission\PermissionRegistrar;

/**
 * Import xe + tài xế từ file "NV Điều vận_File bàn giao.xlsx" (sheet "TT xe nội bộ", "TT Tài xế").
 *
 * Upsert: tài xế theo CCCD (fallback họ tên), xe theo biển số — chạy lại nhiều lần an toàn.
 * Cột không có trong bảng drivers/vehicles được lưu vào driver/vehicle_compliance_documents.
 */
class FleetHandoverImportService
{
    public const DRIVER_SHEET = 'TT Tài xế';

    public const VEHICLE_SHEET = 'TT xe nội bộ';

    /**
     * field => nhãn cột (đã chuẩn hoá). Nhãn kết thúc bằng '*' = so khớp tiền tố.
     *
     * @var array<string, list<string>>
     */
    private const DRIVER_COLUMNS = [
        'full_name' => ['họ và tên', 'họ tên'],
        'employee_code' => ['mã nv'],
        'phone' => ['sđt', 'số điện thoại'],
        'national_id' => ['số cccd', 'cccd*'],
        'license_number' => ['số gplx'],
        'license_expires_at' => ['ngày hết hạn gplx'],
        'license_class' => ['hạng gplx'],
        'date_of_birth' => ['ngày sinh'],
        'docs_link' => ['giấy tờ tùy thân', 'giấy tờ tuỳ thân'],
    ];

    /** @var array<string, list<string>> */
    private const VEHICLE_COLUMNS = [
        'license_plate' => ['bks', 'biển số*'],
        'model' => ['loại xe'],
        'owner_name' => ['tên chủ xe', 'chủ xe'],
        'frame_engine_number' => ['số khung*'],
        'year_manufactured' => ['năm sản xuất'],
        'purchased' => ['năm mua xe', 'năm mua*'],
        'usage_expires' => ['niên hạn*'],
        'seat_count' => ['số chỗ*'],
        'insurance_provider' => ['bảo hiểm'],
        'insurance_expires' => ['thời hạn bảo hiểm'],
        'inspection_expires' => ['hạn đăng kiểm'],
        'registration_cycle' => ['thời gian đk'],
        'last_maintenance' => ['lần btbd gần nhất', 'lần bảo dưỡng*'],
        'maintenance_note' => ['bảo dưỡng'],
        'registration_doc' => ['cà vẹt*'],
        'insurance_doc' => ['bảo hiểm xe*'],
        'inspection_doc' => ['chứng nhận kiểm định*'],
        'lease_doc' => ['hợp đồng thuê*'],
        'notes' => ['ghi chú'],
        'driver_name' => ['tài xế phụ trách'],
        'driver_phone' => ['sđt tx'],
    ];

    /** @var array<string, string> Mã NV => email, chỉ định tay khi không tự khớp được */
    private array $manualLinks = [];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{
     *     drivers: list<array<string, mixed>>,
     *     vehicles: list<array<string, mixed>>,
     *     warnings: list<string>,
     * }
     */
    public function import(
        string $path,
        bool $dryRun,
        string $driverSheet = self::DRIVER_SHEET,
        string $vehicleSheet = self::VEHICLE_SHEET,
        array $manualLinks = [],
    ): array {
        $this->manualLinks = [];
        foreach ($manualLinks as $code => $email) {
            $this->manualLinks[mb_strtoupper(trim((string) $code))] = mb_strtolower(trim((string) $email));
        }

        $reader = IOFactory::createReaderForFile($path);
        $spreadsheet = $reader->load($path);

        $driverRows = $this->readSheet($this->sheet($spreadsheet, $driverSheet), self::DRIVER_COLUMNS, 'full_name', false);
        $vehicleRows = $this->readSheet($this->sheet($spreadsheet, $vehicleSheet), self::VEHICLE_COLUMNS, 'license_plate', true);

        $report = ['drivers' => [], 'vehicles' => [], 'warnings' => []];
        $source = basename($path);

        DB::beginTransaction();
        try {
            $driverIdsByName = $this->importDrivers($driverRows, $source, $report);
            $this->importVehicles($vehicleRows, $driverRows, $driverIdsByName, $source, $report);

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $report;
    }

    /**
     * @param  list<array{row: int, data: array<string, mixed>}>  $rows
     * @param  array{drivers: list<array<string, mixed>>, vehicles: list<array<string, mixed>>, warnings: list<string>}  $report
     * @return array<string, int> tên (chuẩn hoá) => driver id
     */
    private function importDrivers(array $rows, string $source, array &$report): array
    {
        $phoneCounts = array_count_values(array_filter(array_map(
            fn (array $r) => $this->phone($r['data']['phone'] ?? ''),
            $rows,
        )));
        foreach ($phoneCounts as $phone => $n) {
            if ($n > 1) {
                $report['warnings'][] = "SĐT {$phone} bị trùng giữa {$n} tài xế trong sheet — không dùng SĐT này để khớp tài khoản.";
            }
        }

        $driverRoleExists = Role::query()->where('name', Roles::DRIVER)->exists();
        $idsByName = [];
        foreach ($rows as ['row' => $rowNo, 'data' => $d]) {
            $name = $this->text($d['full_name'] ?? '');
            if ($name === '') {
                $report['warnings'][] = "Tài xế dòng {$rowNo}: thiếu họ tên — bỏ qua.";

                continue;
            }

            $nationalId = $this->text($d['national_id'] ?? '');
            $phone = $this->phone($d['phone'] ?? '');
            $code = mb_strtoupper($this->text($d['employee_code'] ?? ''));

            $driver = $nationalId !== ''
                ? Driver::withTrashed()->where('national_id', $nationalId)->first()
                : null;
            $driver ??= Driver::withTrashed()->where('full_name', $name)->first();
            $action = $driver ? 'updated' : 'created';
            $driver ??= new Driver;
            if ($driver->trashed()) {
                $driver->restore();
            }
            $before = $driver->exists ? $driver->toArray() : null;

            [$user, $matchedBy] = $this->matchUser($code, $name, ($phoneCounts[$phone] ?? 0) === 1 ? $phone : '');
            $userNote = 'không khớp — cần gán tay';
            if (! $user && isset($this->manualLinks[$code])) {
                $report['warnings'][] = "Tài xế {$name}: --link {$code}={$this->manualLinks[$code]} nhưng không có user với email này.";
                $userNote = 'email --link không tồn tại';
            }
            if ($user) {
                $linkedElsewhere = Driver::query()
                    ->where('user_id', $user->id)
                    ->when($driver->exists, fn ($q) => $q->whereKeyNot($driver->getKey()))
                    ->exists();
                if ($linkedElsewhere) {
                    $report['warnings'][] = "Tài xế {$name}: user #{$user->id} ({$user->email}) đã gắn với tài xế khác — không liên kết.";
                    $user = null;
                    $userNote = 'user đã gắn tài xế khác';
                } else {
                    $userNote = "#{$user->id} {$user->email} (khớp theo {$matchedBy})";
                    if (! $driverRoleExists) {
                        $userNote .= ' (chưa có role driver trong hệ thống — gán tay)';
                    } elseif (! $user->hasRole(Roles::DRIVER)) {
                        $user->assignRole(Roles::DRIVER);
                        $userNote .= ' +role driver';
                    }
                }
            }

            $driver->fill([
                'full_name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'national_id' => $nationalId !== '' ? $nationalId : null,
                'license_class' => $this->text($d['license_class'] ?? '') ?: null,
                'license_expires_at' => $this->date($d['license_expires_at'] ?? null)?->toDateString(),
                'employment_status' => 'active',
                'availability_status' => 'available',
            ]);
            if ($user) {
                $driver->user_id = $user->id;
            }
            $driver->save();

            $link = $this->text($d['docs_link'] ?? '');
            $dob = $this->date($d['date_of_birth'] ?? null);

            $this->upsertDriverDoc($driver, 'id_card', [
                'title' => $nationalId !== '' ? "CCCD {$nationalId}" : 'CCCD',
                'notes' => $this->lines([
                    $code !== '' ? "Mã NV: {$code}" : null,
                    $dob ? 'Ngày sinh: '.$dob->format('d/m/Y') : null,
                    $link !== '' ? "Giấy tờ tùy thân: {$link}" : null,
                ]),
            ]);

            $licenseNumber = $this->text($d['license_number'] ?? '');
            if ($licenseNumber !== '' || $driver->license_expires_at) {
                $this->upsertDriverDoc($driver, 'license', [
                    'title' => trim('GPLX '.$licenseNumber.($driver->license_class ? " — hạng {$driver->license_class}" : '')),
                    'expires_at' => $driver->license_expires_at?->toDateString(),
                    'notes' => $link !== '' ? "Giấy tờ: {$link}" : null,
                ]);
            }

            $this->audit->log(
                actorId: null,
                event: "fleet.driver.{$action}",
                auditable: $driver,
                before: $before,
                after: $driver->fresh()?->toArray(),
                metadata: ['source' => $source, 'row' => $rowNo],
            );

            $idsByName[$this->key($name)] = (int) $driver->id;
            $report['drivers'][] = [
                'row' => $rowNo,
                'name' => $name,
                'code' => $code,
                'action' => $action,
                'user' => $userNote,
            ];
        }

        return $idsByName;
    }

    /**
     * @param  list<array{row: int, data: array<string, mixed>}>  $rows
     * @param  list<array{row: int, data: array<string, mixed>}>  $driverRows
     * @param  array<string, int>  $driverIdsByName
     * @param  array{drivers: list<array<string, mixed>>, vehicles: list<array<string, mixed>>, warnings: list<string>}  $report
     */
    private function importVehicles(array $rows, array $driverRows, array $driverIdsByName, string $source, array &$report): void
    {
        $driverPhones = [];
        foreach ($driverRows as ['data' => $d]) {
            $driverPhones[$this->key($this->text($d['full_name'] ?? ''))] = $this->phone($d['phone'] ?? '');
        }

        foreach ($rows as ['row' => $rowNo, 'data' => $d]) {
            $plate = $this->plate($d['license_plate'] ?? '');
            if ($plate === '') {
                $report['warnings'][] = "Xe dòng {$rowNo}: thiếu biển số — bỏ qua.";

                continue;
            }

            $vehicle = Vehicle::withTrashed()->where('license_plate', $plate)->first();
            $action = $vehicle ? 'updated' : 'created';
            $vehicle ??= new Vehicle;
            if ($vehicle->trashed()) {
                $vehicle->restore();
            }
            $before = $vehicle->exists ? $vehicle->toArray() : null;

            $seats = $this->int($d['seat_count'] ?? '');
            $model = $this->text($d['model'] ?? '');
            $insuranceRaw = $this->text($d['insurance_expires'] ?? '');
            $insuranceExpires = $this->date($d['insurance_expires'] ?? null);
            $inspectionExpires = $this->date($d['inspection_expires'] ?? null);

            $driverName = $this->text($d['driver_name'] ?? '');
            $driverId = null;
            if ($driverName !== '') {
                $driverId = $driverIdsByName[$this->key($driverName)]
                    ?? Driver::query()->where('full_name', $driverName)->value('id');
                if (! $driverId) {
                    $report['warnings'][] = "Xe {$plate}: không tìm thấy tài xế phụ trách \"{$driverName}\" trong sheet tài xế.";
                }
                $sheetPhone = $this->phone($d['driver_phone'] ?? '');
                $driverSheetPhone = $driverPhones[$this->key($driverName)] ?? '';
                if ($sheetPhone !== '' && $driverSheetPhone !== '' && $sheetPhone !== $driverSheetPhone) {
                    $report['warnings'][] = "Xe {$plate}: SĐT tài xế {$driverName} ({$sheetPhone}) khác sheet tài xế ({$driverSheetPhone}) — đang dùng số ở sheet tài xế.";
                }
            }

            $vehicle->fill([
                'license_plate' => $plate,
                'type' => $this->vehicleType($seats),
                'owner_name' => $this->text($d['owner_name'] ?? '') ?: null,
                'frame_engine_number' => $this->text($d['frame_engine_number'] ?? '') ?: null,
                'year_manufactured' => $this->year($d['year_manufactured'] ?? ''),
                'purchased_at' => $this->monthYear($d['purchased'] ?? null)?->toDateString(),
                'usage_expires_year' => $this->year($d['usage_expires'] ?? ''),
                'seat_count' => $seats,
                'insurance_provider' => mb_substr($this->text($d['insurance_provider'] ?? ''), 0, 64) ?: null,
                'insurance_policy_note' => $insuranceRaw !== '' && ! $this->isPlainDate($insuranceRaw) ? $insuranceRaw : null,
                'insurance_expires_at' => $insuranceExpires?->toDateString(),
                'inspection_expires_at' => $inspectionExpires?->toDateString(),
                'registration_cycle_note' => mb_substr($this->text($d['registration_cycle'] ?? ''), 0, 255) ?: null,
                'last_maintenance_at' => $this->date($d['last_maintenance'] ?? null)?->toDateString(),
                'maintenance_schedule_note' => $this->text($d['maintenance_note'] ?? '') ?: null,
                'default_driver_id' => $driverId,
                'notes' => $this->lines([
                    $model !== '' ? "Dòng xe: {$model}" : null,
                    $this->text($d['notes'] ?? '') ?: null,
                ]),
            ]);
            if (! $vehicle->exists) {
                $vehicle->status = 'ready';
            }
            $vehicle->save();

            $docs = [
                'registration' => ['Cà vẹt xe', $d['registration_doc'] ?? '', null],
                'insurance_certificate' => ['Bảo hiểm xe', $d['insurance_doc'] ?? '', $insuranceExpires],
                'inspection_certificate' => ['Chứng nhận kiểm định (đăng kiểm)', $d['inspection_doc'] ?? '', $inspectionExpires],
                'lease_contract' => ['Hợp đồng thuê xe', $d['lease_doc'] ?? '', null],
            ];
            foreach ($docs as $type => [$title, $links, $expires]) {
                $links = $this->text($links, keepNewlines: true);
                if ($links === '') {
                    continue;
                }
                VehicleComplianceDocument::query()->updateOrCreate(
                    ['vehicle_id' => $vehicle->id, 'doc_type' => $type, 'status' => VehicleComplianceDocument::STATUS_ACTIVE],
                    ['title' => $title, 'notes' => $links, 'expires_at' => $expires?->toDateString()],
                );
            }

            $this->audit->log(
                actorId: null,
                event: "fleet.vehicle.{$action}",
                auditable: $vehicle,
                before: $before,
                after: $vehicle->fresh()?->toArray(),
                metadata: ['source' => $source, 'row' => $rowNo],
            );

            $report['vehicles'][] = [
                'row' => $rowNo,
                'plate' => $plate,
                'model' => $model,
                'seats' => $seats,
                'action' => $action,
                'driver' => $driverId ? $driverName : '',
            ];
        }
    }

    /**
     * Khớp user theo Mã NV → họ tên (duy nhất) → SĐT (duy nhất).
     *
     * @return array{0: ?User, 1: ?string}
     */
    private function matchUser(string $code, string $name, string $phone): array
    {
        if ($code !== '' && isset($this->manualLinks[$code])) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$this->manualLinks[$code]])->first();

            return $user ? [$user, 'chỉ định --link'] : [null, null];
        }

        if ($code !== '' && Schema::hasColumn('users', 'employee_code')) {
            $hits = User::query()->whereRaw('UPPER(employee_code) = ?', [$code])->get();
            if ($hits->count() === 1) {
                return [$hits->first(), 'mã NV'];
            }
        }

        $hits = User::query()->where('name', $name)->get()
            ->filter(fn (User $u) => $this->key((string) $u->name) === $this->key($name));
        if ($hits->count() === 1) {
            return [$hits->first(), 'họ tên'];
        }

        if ($phone !== '' && Schema::hasColumn('users', 'phone')) {
            $hits = User::query()->whereNotNull('phone')->get(['id', 'name', 'email', 'phone'])
                ->filter(fn (User $u) => $this->phone((string) $u->phone) === $phone);
            if ($hits->count() === 1) {
                return [User::query()->find($hits->first()->id), 'SĐT'];
            }
        }

        return [null, null];
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function upsertDriverDoc(Driver $driver, string $type, array $attrs): void
    {
        DriverComplianceDocument::query()->updateOrCreate(
            ['driver_id' => $driver->id, 'doc_type' => $type, 'status' => DriverComplianceDocument::STATUS_ACTIVE],
            $attrs,
        );
    }

    private function sheet(Spreadsheet $spreadsheet, string $wanted): Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $ws) {
            if ($this->key($ws->getTitle()) === $this->key($wanted)) {
                return $ws;
            }
        }

        throw new \RuntimeException("Không tìm thấy sheet \"{$wanted}\". Các sheet hiện có: ".implode(', ', $spreadsheet->getSheetNames()));
    }

    /**
     * Đọc sheet theo nhãn cột. Với sheet có tiêu đề 2 tầng (merge), nhãn con ở dòng dưới được ưu tiên.
     *
     * @param  array<string, list<string>>  $columns
     * @return list<array{row: int, data: array<string, mixed>}>
     */
    private function readSheet(Worksheet $ws, array $columns, string $requiredField, bool $twoRowHeader): array
    {
        $lastRow = (int) $ws->getHighestDataRow();
        $lastCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());

        $labelsAt = function (int $r) use ($ws, $lastCol): array {
            $labels = [];
            for ($c = 1; $c <= $lastCol; $c++) {
                $labels[$c] = $this->key((string) $ws->getCell([$c, $r])->getValue());
            }

            return $labels;
        };

        // Dò dòng tiêu đề chỉ bằng dòng chính (không gộp dòng dưới, tránh nhận nhầm dòng tiêu đề phần).
        $headerRow = null;
        for ($r = 1; $r <= min(10, $lastRow); $r++) {
            if (isset($this->mapColumns($labelsAt($r), $columns)[$requiredField])) {
                $headerRow = $r;
                break;
            }
        }

        if ($headerRow === null) {
            throw new \RuntimeException("Sheet \"{$ws->getTitle()}\": không tìm thấy dòng tiêu đề chứa cột bắt buộc ({$requiredField}).");
        }

        $labels = $labelsAt($headerRow);
        if ($twoRowHeader) {
            foreach ($labelsAt($headerRow + 1) as $c => $sub) {
                if ($sub !== '') {
                    $labels[$c] = $sub;
                }
            }
        }
        $map = $this->mapColumns($labels, $columns);

        $rows = [];
        for ($r = $headerRow + ($twoRowHeader ? 2 : 1); $r <= $lastRow; $r++) {
            $data = [];
            foreach ($map as $field => $col) {
                $cell = $ws->getCell([$col, $r]);
                $raw = $cell->getValue();
                $data[$field] = is_numeric($raw) && ExcelDate::isDateTime($cell)
                    ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))
                    : (string) $cell->getFormattedValue();
            }
            if (trim((string) ($data[$requiredField] ?? '')) === '') {
                continue;
            }
            $rows[] = ['row' => $r, 'data' => $data];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $labels  cột => nhãn đã chuẩn hoá
     * @param  array<string, list<string>>  $columns
     * @return array<string, int>
     */
    private function mapColumns(array $labels, array $columns): array
    {
        $map = [];
        // Khớp chính xác trước, tiền tố sau — tránh "bảo hiểm" ăn nhầm cột "bảo hiểm xe (update …)".
        foreach ([false, true] as $prefixPass) {
            foreach ($columns as $field => $patterns) {
                if (isset($map[$field])) {
                    continue;
                }
                foreach ($labels as $col => $label) {
                    if ($label === '' || in_array($col, $map, true)) {
                        continue;
                    }
                    foreach ($patterns as $p) {
                        $isPrefix = str_ends_with($p, '*');
                        if ($isPrefix !== $prefixPass) {
                            continue;
                        }
                        $p = rtrim($p, '*');
                        if ($isPrefix ? str_starts_with($label, $p) : $label === $p) {
                            $map[$field] = $col;

                            continue 3;
                        }
                    }
                }
            }
        }

        return $map;
    }

    /** Chuẩn hoá để so khớp: NFC, lowercase, gộp khoảng trắng. */
    private function key(string $s): string
    {
        return mb_strtolower($this->text($s));
    }

    private function text(mixed $v, bool $keepNewlines = false): string
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->format('d/m/Y');
        }
        $s = (string) $v;
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        }
        if ($keepNewlines) {
            $lines = array_filter(array_map(
                fn (string $l) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $l) ?? $l),
                preg_split('/\R/u', $s) ?: [],
            ));

            return implode("\n", $lines);
        }

        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    /**
     * @param  list<?string>  $parts
     */
    private function lines(array $parts): ?string
    {
        $s = implode("\n", array_filter($parts, fn ($p) => $p !== null && $p !== ''));

        return $s !== '' ? $s : null;
    }

    private function phone(mixed $v): string
    {
        $digits = preg_replace('/\D+/', '', $this->text($v)) ?? '';
        if (strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function plate(mixed $v): string
    {
        return mb_strtoupper($this->text($v));
    }

    private function int(mixed $v): ?int
    {
        return preg_match('/\d+/', $this->text($v), $m) ? (int) $m[0] : null;
    }

    private function year(mixed $v): ?int
    {
        if ($v instanceof \DateTimeInterface) {
            return (int) $v->format('Y');
        }

        return preg_match('/\b(19|20)\d{2}\b/', $this->text($v), $m) ? (int) $m[0] : null;
    }

    /** Ngày cuối cùng xuất hiện trong ô (vd. "17g27 ngày 17/11/2025 – 17g27 ngày 17/11/2026" → 17/11/2026). */
    private function date(mixed $v): ?Carbon
    {
        if ($v instanceof \DateTimeInterface) {
            return Carbon::instance($v)->startOfDay();
        }
        if (! preg_match_all('#(\d{1,2})/(\d{1,2})/(\d{4})#', $this->text($v), $m, PREG_SET_ORDER)) {
            return null;
        }
        [, $d, $mo, $y] = end($m);

        return checkdate((int) $mo, (int) $d, (int) $y) ? Carbon::create((int) $y, (int) $mo, (int) $d) : null;
    }

    /** "2/2014" → 01/02/2014; "2014" → 01/01/2014. */
    private function monthYear(mixed $v): ?Carbon
    {
        if ($v instanceof \DateTimeInterface) {
            return Carbon::instance($v)->startOfDay();
        }
        $s = $this->text($v);
        if ($date = $this->date($s)) {
            return $date;
        }
        if (preg_match('#^(\d{1,2})/(\d{4})$#', $s, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12) {
            return Carbon::create((int) $m[2], (int) $m[1], 1);
        }
        if (preg_match('#^(\d{4})$#', $s, $m)) {
            return Carbon::create((int) $m[1], 1, 1);
        }

        return null;
    }

    private function isPlainDate(string $s): bool
    {
        return (bool) preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $s);
    }

    private function vehicleType(?int $seats): string
    {
        return match (true) {
            $seats === null, $seats <= 5 => 'sedan',
            $seats <= 7 => 'suv',
            $seats <= 16 => 'minibus',
            default => 'bus',
        };
    }
}
