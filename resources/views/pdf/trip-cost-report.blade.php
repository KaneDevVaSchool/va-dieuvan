<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 18px 22px 22px 22px;
            size: A4 landscape;
        }

        * { box-sizing: border-box; font-family: 'DejaVu Sans', sans-serif; }

        body {
            font-size: 7pt;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.4;
            background: #fff;
        }

        strong, b { font-weight: bold; }

        /* ── Brand header ── */
        .rpt-header {
            width: 100%;
            border-bottom: 2pt solid #9a0036;
            padding-bottom: 7pt;
            margin-bottom: 6pt;
        }

        .rpt-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1pt;
            color: #9a0036;
            text-align: center;
        }

        .rpt-subtitle {
            font-size: 8pt;
            text-align: center;
            color: #666;
            margin-top: 2pt;
            font-style: italic;
        }

        /* ── Export meta bar ── */
        .meta-bar {
            width: 100%;
            border-collapse: collapse;
            margin: 5pt 0 7pt;
            font-size: 7pt;
        }

        .meta-bar td { padding: 2pt 6pt; }
        .meta-bar .meta-label { color: #888; white-space: nowrap; }
        .meta-bar .meta-value { font-weight: bold; color: #1a1a1a; }

        .meta-line {
            background: #fdf2f5;
            border: 0.5pt solid #f9c9d8;
            border-radius: 2pt;
            padding: 3pt 8pt;
            font-size: 7pt;
            color: #7d0029;
            margin-bottom: 6pt;
        }

        /* ── Data table ── */
        table.cost-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
        }

        table.cost-table th {
            padding: 4pt 3pt;
            text-align: center;
            font-size: 6pt;
            font-weight: bold;
            border: 0.5pt solid #888;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            background: #3a3a5c;
            color: #fff;
        }

        table.cost-table th.money-col { text-align: right; }

        table.cost-table td {
            padding: 4pt 3pt;
            border: 0.5pt solid #ddd;
            color: #1a1a1a;
            vertical-align: top;
            background: #fff;
        }

        table.cost-table tbody tr:nth-child(even) td { background: #fafafa; }
        table.cost-table td.tc { text-align: center; }
        table.cost-table td.tr { text-align: right; white-space: nowrap; tabular-nums: 1; }

        table.cost-table tr.row-total td {
            background: #f5f5f5;
            font-weight: bold;
            border: 0.5pt solid #aaa;
            border-top: 1.5pt solid #777;
        }

        /* Status badge colors */
        .st-confirmed { color: #065f46; }
        .st-rejected  { color: #991b1b; }
        .st-submitted { color: #1e40af; }
        .st-estimate  { color: #6b21a8; }
        .st-draft     { color: #374151; }

        /* ── Signature table ── */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
        }

        table.sig-table td {
            border: 0.5pt solid #aaa;
            text-align: center;
            vertical-align: bottom;
            padding: 4pt 6pt;
            width: 33.33%;
        }

        .sig-header {
            background: #ededed;
            font-size: 7.5pt;
            font-weight: bold;
            color: #333;
            padding: 5pt 6pt;
            vertical-align: middle;
            height: 20pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
        }

        .sig-body {
            height: 72pt;
            vertical-align: bottom;
            background: #fff;
            padding-bottom: 6pt;
        }

        .sig-hint {
            font-size: 6pt;
            font-style: italic;
            color: #999;
        }

        /* ── Footer ── */
        .page-footer {
            text-align: center;
            font-size: 6pt;
            color: #bbb;
            margin-top: 8pt;
            padding-top: 4pt;
            border-top: 0.5pt solid #ddd;
        }

        /* ── Filters summary ── */
        .filter-bar {
            font-size: 6.5pt;
            color: #666;
            margin-bottom: 4pt;
        }

        .filter-chip {
            display: inline-block;
            background: #fdf2f5;
            border: 0.5pt solid #f9c9d8;
            color: #7d0029;
            padding: 1pt 5pt;
            border-radius: 3pt;
            margin-right: 3pt;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ── --}}
    <div class="rpt-header">
        <div class="rpt-title">{{ $sheetTitle }}</div>
        <div class="rpt-subtitle">Hệ Thống Điều Vận Nội Bộ &nbsp;·&nbsp; Vietnam America Schools</div>
    </div>

    {{-- ── EXPORT META ── --}}
    <table class="meta-bar">
        <tr>
            <td class="meta-label">Ngày xuất:</td>
            <td class="meta-value">{{ now()->format('d/m/Y H:i') }}</td>
            <td style="width:30pt;"></td>
            <td class="meta-label">Người xuất:</td>
            <td class="meta-value" style="min-width:100pt;">{{ $exportedBy ?? '_______________________________' }}</td>
            <td style="width:30pt;"></td>
            <td class="meta-label">Đơn vị:</td>
            <td class="meta-value" style="min-width:100pt;">{{ $unitName ?? '_______________________________' }}</td>
        </tr>
    </table>

    {{-- ── FILTER SUMMARY ── --}}
    @if($filterSummary)
    <div class="filter-bar">
        Bộ lọc:
        @foreach($filterSummary as $label)
            <span class="filter-chip">{{ $label }}</span>
        @endforeach
    </div>
    @endif

    {{-- ── DATA TABLE ── --}}
    <table class="cost-table">
        <thead>
            <tr>
                <th style="width:3%;">STT</th>
                <th style="width:8%;">ĐƠN VỊ</th>
                <th style="width:8%;">PHÂN LOẠI</th>
                <th style="width:9%;">NGƯỜI ĐỀ XUẤT</th>
                <th style="width:20%;">NỘI DUNG</th>
                <th style="width:11%;">NGUỒN LỰC CHUYẾN</th>
                <th style="width:9%;">NHÀ CUNG CẤP</th>
                <th class="money-col" style="width:8%;">ĐƠN GIÁ</th>
                <th class="money-col" style="width:8%;">PHỤ THU</th>
                <th class="money-col" style="width:8%;">THÀNH TIỀN</th>
                <th style="width:8%;">TRẠNG THÁI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                @php
                    $statusCls = match($row['status'] ?? '') {
                        'confirmed' => 'st-confirmed',
                        'rejected'  => 'st-rejected',
                        'submitted' => 'st-submitted',
                        'estimate'  => 'st-estimate',
                        default     => 'st-draft',
                    };
                @endphp
                <tr>
                    <td class="tc">{{ $i + 1 }}</td>
                    <td>{{ $row['unit'] ?? '—' }}</td>
                    <td>{{ \App\Services\Reports\TripCostReportService::TRIP_TYPE_LABELS[$row['category'] ?? ''] ?? ($row['category'] ?? '—') }}</td>
                    <td>{{ $row['submitter'] ?? '—' }}</td>
                    <td>{{ $row['description'] ?? '—' }}</td>
                    <td>{{ $row['fleet_source'] ?? '—' }}</td>
                    <td>{{ $row['provider'] ?: '—' }}</td>
                    <td class="tr">
                        @if($row['unit_price'] !== null && $row['unit_price'] > 0)
                            {{ number_format((float)$row['unit_price'], 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="tr">
                        @if($row['extra_fee'] !== null && $row['extra_fee'] > 0)
                            {{ number_format((float)$row['extra_fee'], 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="tr"><strong>{{ number_format((float)($row['amount'] ?? 0), 0, ',', '.') }}</strong></td>
                    <td class="{{ $statusCls }}">{{ $row['status_label'] ?? ($row['status'] ?? '—') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; color:#bbb; padding:12pt;">Không có dữ liệu</td>
                </tr>
            @endforelse
            <tr class="row-total">
                <td colspan="7" class="tr" style="padding-right:6pt;">TỔNG CỘNG</td>
                <td class="tr">{{ $sumUnitPrice > 0 ? number_format($sumUnitPrice, 0, ',', '.') : '—' }}</td>
                <td class="tr">{{ $sumExtraFee > 0 ? number_format($sumExtraFee, 0, ',', '.') : '—' }}</td>
                <td class="tr">{{ number_format($sumAmount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- ── SIGNATURE BLOCK ── --}}
    <table class="sig-table">
        <tr>
            <td class="sig-header">Người lập</td>
            <td class="sig-header">Kế toán xác nhận</td>
            <td class="sig-header">Phê duyệt</td>
        </tr>
        <tr>
            <td class="sig-body"><span class="sig-hint">(Ký và ghi rõ họ tên)</span></td>
            <td class="sig-body"><span class="sig-hint">(Ký và ghi rõ họ tên)</span></td>
            <td class="sig-body"><span class="sig-hint">(Ký và ghi rõ họ tên)</span></td>
        </tr>
    </table>

    <div class="page-footer">
        Báo cáo Chi phí Chuyến &nbsp;·&nbsp; VA Điều Vận &nbsp;·&nbsp; Vietnam America Schools
    </div>

</body>
</html>
