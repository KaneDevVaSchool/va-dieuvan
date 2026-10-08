<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page { margin: 22px 26px; size: A4 portrait; }
        * { box-sizing: border-box; font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 8pt; color: #111; margin: 0; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .head td { border: 1px solid #333; padding: 3pt 5pt; vertical-align: middle; }
        .org { font-weight: bold; text-align: center; font-size: 8.5pt; }
        .title { font-weight: bold; text-align: center; font-size: 11.5pt; }
        .meta { font-size: 7.5pt; }
        .info td { padding: 3pt 2pt; }
        .grid th, .grid td { border: 1px solid #333; padding: 2.5pt 4pt; vertical-align: middle; }
        .grid th { background: #e5ebf5; font-weight: bold; text-align: center; }
        .c { text-align: center; }
        .crit { color: #b00020; font-weight: bold; }
        .ng { color: #b00020; font-weight: bold; }
        .result { margin-top: 8pt; padding: 5pt 7pt; border: 1.5pt solid #333; font-weight: bold; }
        .result.fail { border-color: #b00020; color: #b00020; }
        .result.pass { border-color: #0a7a3d; color: #0a7a3d; }
        .sign td { text-align: center; padding-top: 6pt; }
        .muted { color: #555; font-style: italic; }
    </style>
</head>
<body>
<table class="head">
    <tr>
        <td rowspan="3" style="width: 34%" class="org">{{ $d['meta']['org_line_1'] ?? '' }}<br>{{ $d['meta']['org_line_2'] ?? '' }}</td>
        <td rowspan="3" class="title">{{ $d['meta']['title'] ?? 'PHIẾU KIỂM TRA' }}</td>
        <td class="meta" style="width: 24%">Kí hiệu: {{ $d['form_code']['code'] ?? '' }}</td>
    </tr>
    <tr><td class="meta">Mã phiếu: <b>{{ $d['code'] }}</b></td></tr>
    <tr><td class="meta">Lần ban hành: {{ $d['form_code']['revision'] ?? '01' }}</td></tr>
</table>

<table class="info" style="margin-top: 6pt">
    <tr>
        <td style="width: 50%">Ngày kiểm tra: <b>{{ $d['check_date'] }}</b></td>
        <td>Địa điểm kiểm tra: <b>{{ $d['location'] }}</b></td>
    </tr>
    <tr>
        <td>Họ và tên Tài xế: <b>{{ $d['driver_name'] }}</b> {{ $d['driver_phone'] ? '('.$d['driver_phone'].')' : '' }}</td>
        <td>Biển số xe: <b>{{ $d['license_plate'] }}</b></td>
    </tr>
    <tr>
        <td>Cơ sở/Tuyến: {{ $d['campus_route'] }}</td>
        <td>Giờ xuất bến: {{ $d['departure_time'] }} @if($d['attendant']) · Bảo mẫu/Giám sát: {{ $d['attendant'] }} @endif</td>
    </tr>
    <tr><td colspan="2">Mục đích kiểm tra: {{ $d['meta']['purpose'] ?? '' }}</td></tr>
</table>

<table class="grid" style="margin-top: 5pt">
    <thead>
    <tr>
        <th style="width: 5%">STT</th>
        <th style="width: 17%">Hạng mục</th>
        <th style="width: 22%">Nội dung kiểm tra</th>
        <th>Yêu cầu/Quy định</th>
        <th style="width: 7%">Đạt (✓)</th>
        <th style="width: 8%">Không đạt (✗)</th>
        <th style="width: 17%">Ghi chú</th>
    </tr>
    </thead>
    <tbody>
    @foreach($d['rows'] as $i => $row)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            @if(($row['group_span'] ?? 1) > 0)
                <td rowspan="{{ $row['group_span'] }}">{{ $row['group'] }}</td>
            @endif
            <td>{{ $row['title'] }}@if($row['severity'] === 'critical')<span class="crit"> *</span>@endif</td>
            <td>{{ $row['requirement'] }}</td>
            <td class="c">{{ $row['result'] === 'ok' ? '✓' : ($row['result'] === 'na' ? 'N/A' : '') }}</td>
            <td class="c ng">{{ $row['result'] === 'ng' ? '✗' : '' }}</td>
            <td>{{ $row['note'] }}@if($row['checked_at'])<span class="muted"> ({{ $row['checked_at'] }})</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
<p class="muted" style="margin: 3pt 0 0">(*) Mục Critical — nếu Không đạt thì không khởi hành.</p>

<div class="result {{ $d['status'] === 'draft' ? '' : ($d['result'] === 'fail' ? 'fail' : 'pass') }}">
    Kết luận: {{ app(\App\Services\Checklist\ChecklistPrintService::class)->conclusion($d) }}
</div>

<p style="margin-top: 6pt">Ghi chú (nếu có): {{ $d['note'] }}</p>
<p>{{ $d['statement'] }}</p>

<table class="sign" style="margin-top: 6pt">
    <tr>
        <td style="width: 50%"><b>Người Kiểm tra (nếu có)</b><br><span class="muted">(ký, ghi rõ họ tên)</span></td>
        <td><b>Tài xế Xác nhận</b><br><span class="muted">(ký, ghi rõ họ tên)</span></td>
    </tr>
    <tr>
        <td style="padding-top: 48pt">{{ $d['inspector_name'] }}</td>
        <td style="padding-top: 48pt">{{ $d['driver_name'] }}</td>
    </tr>
</table>
</body>
</html>
