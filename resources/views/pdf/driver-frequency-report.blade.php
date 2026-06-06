<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page { margin: 18px 22px; size: A4 landscape; }
        * { box-sizing: border-box; font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 7pt; color: #1a1a1a; margin: 0; line-height: 1.35; }
        .rpt-title { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #9a0036; text-align: center; border-bottom: 2pt solid #9a0036; padding-bottom: 6pt; margin-bottom: 6pt; }
        .rpt-sub { font-size: 8pt; text-align: center; color: #666; font-style: italic; margin-bottom: 8pt; }
        .meta { background: #fdf2f5; border: 0.5pt solid #f9c9d8; padding: 4pt 8pt; margin-bottom: 8pt; font-size: 7pt; color: #7d0029; }
        .kpi-row { margin-bottom: 8pt; font-size: 7.5pt; }
        table.data { width: 100%; border-collapse: collapse; font-size: 6.5pt; }
        table.data th { background: #3a3a5c; color: #fff; padding: 4pt 3pt; text-align: center; font-size: 6pt; }
        table.data td { border: 0.5pt solid #e2e8f0; padding: 3pt 4pt; }
        table.data tr:nth-child(even) td { background: #fafafa; }
        .foot { margin-top: 8pt; font-size: 6.5pt; color: #888; font-style: italic; }
    </style>
</head>
<body>
@php
    $kpi = $payload['kpi'] ?? [];
    $drivers = $payload['drivers'] ?? [];
    $year = $payload['year'] ?? now()->year;
@endphp

<div class="rpt-title">Báo cáo tần suất tài xế &amp; xe</div>
<div class="rpt-sub">Hệ Thống Điều Vận Nội Bộ · Vietnam America Schools</div>

<div class="meta">
    Năm {{ $year }}
    @if(!empty($filterLabels))
        · {{ implode(' · ', $filterLabels) }}
    @endif
    · Xuất lúc {{ now()->format('d/m/Y H:i') }}
    @if($exportedBy)
        · Người xuất: {{ $exportedBy }}
    @endif
</div>

<div class="kpi-row">
    <strong>Tổng quan:</strong>
    Tổng chuyến {{ number_format((int)($kpi['totalTrips'] ?? 0), 0, ',', '.') }}
    · Tài xế {{ (int)($kpi['activeDrivers'] ?? 0) }}
    · Xe {{ (int)($kpi['activeVehicles'] ?? 0) }}
    · {{ number_format((float)($kpi['totalHours'] ?? 0), 1, ',', '.') }}h
    · Đúng giờ {{ number_format((float)($kpi['overallOnTime'] ?? 0), 1, ',', '.') }}%
</div>

<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>Tài xế</th>
            <th>Mã NV</th>
            <th>Chuyến</th>
            <th>Giờ lái</th>
            <th>TB/tháng</th>
            <th>CT</th>
            <th>D2D</th>
            <th>P2P</th>
            <th>HH</th>
            <th>Đúng giờ</th>
            <th>KPI</th>
            <th>Xếp loại</th>
        </tr>
    </thead>
    <tbody>
        @forelse($drivers as $idx => $d)
            @php
                $types = $d['typesExport'] ?? [0, 0, 0, 0];
            @endphp
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td>{{ $d['name'] ?? '' }}</td>
                <td style="text-align:center;">{{ $d['employeeCode'] ?? ($d['code'] ?? '') }}</td>
                <td style="text-align:right;">{{ (int)($d['trips'] ?? 0) }}</td>
                <td style="text-align:right;">{{ number_format((float)($d['hours'] ?? 0), 1, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format((float)($d['avgTripsPerMonth'] ?? 0), 1, ',', '.') }}</td>
                <td style="text-align:right;">{{ (int)($types[0] ?? 0) }}</td>
                <td style="text-align:right;">{{ (int)($types[1] ?? 0) }}</td>
                <td style="text-align:right;">{{ (int)($types[2] ?? 0) }}</td>
                <td style="text-align:right;">{{ (int)($types[3] ?? 0) }}</td>
                <td style="text-align:right;">{{ number_format((float)($d['onTime'] ?? 0), 1, ',', '.') }}%</td>
                <td style="text-align:center;font-weight:bold;">{{ (int)($d['kpi'] ?? 0) }}</td>
                <td style="text-align:center;">{{ $d['bonusLabel'] ?? 'Thưởng C' }}</td>
            </tr>
        @empty
            <tr><td colspan="13" style="text-align:center;color:#888;">Không có dữ liệu trong kỳ đã chọn.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="foot">* Điểm KPI = 50% tần suất + 30% đúng giờ + 20% đa dạng loại chuyến</p>
</body>
</html>
