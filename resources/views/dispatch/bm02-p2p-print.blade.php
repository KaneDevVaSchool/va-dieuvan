{{-- BM.02 / MH.QT.04 — in HTML cho preview + xuất PDF (mPDF). Không phụ thuộc PhpSpreadsheet HTML. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .sheet { width: 100%; }
        h1 {
            font-size: 12pt;
            text-align: center;
            margin: 0 0 6px;
            font-weight: bold;
        }
        .sub { text-align: center; font-size: 8.5pt; margin-bottom: 10px; color: #333; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.meta td, table.meta th {
            border: 0.4pt solid #333;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.meta th { background: #f3f4f6; font-weight: 600; width: 22%; }
        .label { font-weight: 600; font-size: 8.5pt; }
        .purpose { white-space: pre-wrap; min-height: 3em; }
        table.targets { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 8px; }
        table.targets td {
            border: 0.35pt solid #555;
            padding: 3px 4px;
            vertical-align: middle;
            width: 25%;
        }
        .tick { display: inline-block; width: 11px; text-align: center; font-size: 11pt; line-height: 1; }
        .tick.on { color: #166534; font-weight: bold; }
        .tick.off { color: #9ca3af; }
        table.trips { width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 6px; }
        table.trips th, table.trips td {
            border: 0.35pt solid #333;
            padding: 3px 4px;
        }
        table.trips th { background: #e5e7eb; font-weight: 600; }
        .right { text-align: right; }
        .muted { color: #6b7280; font-size: 8pt; }
        .foot { margin-top: 12px; font-size: 8pt; }
    </style>
</head>
<body>
<div class="sheet">
    <h1>ĐỀ NGHỊ ĐIỀU VẬN — BM.02 / MH.QT.04 (Điểm — Điểm)</h1>
    <div class="sub">Phiếu điện tử từ portal — In ngày {{ $vm['printed_at'] }}</div>

    <table class="meta">
        <tr>
            <th>Ngày lập</th>
            <td colspan="3">{{ $vm['printed_at'] }}</td>
        </tr>
        <tr>
            <th>Người đề nghị</th>
            <td colspan="3">{{ $vm['requester_name'] }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $vm['requester_email'] }}</td>
            <th>Điện thoại</th>
            <td>{{ $vm['requester_phone'] }}</td>
        </tr>
        <tr>
            <th>Đơn vị</th>
            <td colspan="3">{{ $vm['requester_unit'] }}</td>
        </tr>
        <tr>
            <th>Mục đích sử dụng</th>
            <td colspan="3" class="purpose">{{ $vm['purpose'] }}</td>
        </tr>
        @if($vm['basis_note'] !== '')
        <tr>
            <th>Căn cứ / ghi chú</th>
            <td colspan="3" class="purpose">{{ $vm['basis_note'] }}</td>
        </tr>
        @endif
        <tr>
            <th>Ngày đề xuất</th>
            <td>{{ $vm['proposed_date'] }}</td>
            <th>Ngày cần xe</th>
            <td>{{ $vm['date_needed'] }}</td>
        </tr>
        <tr>
            <th>Gấp</th>
            <td colspan="3">
                <span class="tick {{ $vm['is_urgent'] ? 'on' : 'off' }}">{{ $vm['is_urgent'] ? '☑' : '☐' }}</span>
                @if($vm['is_urgent'])
                    — {{ $vm['urgent_reason'] }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Điều phối</th>
            <td colspan="3">{{ $vm['coordinator_line'] }}</td>
        </tr>
    </table>

    <div class="label">Đối tượng áp dụng</div>
    <table class="targets">
        @foreach($vm['target_table_rows'] as $row)
            <tr>
                @foreach($row as $slot)
                    <td>
                        @if($slot === null)
                            &nbsp;
                        @else
                            <span class="tick {{ !empty($slot['checked']) ? 'on' : 'off' }}">{{ !empty($slot['checked']) ? '☑' : '☐' }}</span>
                            {{ $slot['label'] }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <div class="label">Chi tiết chuyến (tối đa {{ $vm['max_trip_lines'] }} dòng trên mẫu)</div>
    <table class="trips">
        <thead>
        <tr>
            <th>Đi — thời gian</th>
            <th>Điểm đón</th>
            <th>Về — thời gian</th>
            <th>Điểm trả</th>
            <th class="right">SL</th>
            <th>NV phụ trách</th>
            <th class="right">Đơn giá</th>
            <th class="right">Phát sinh</th>
            <th>Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @foreach($vm['trip_lines'] as $line)
            <tr>
                <td>{{ $line['depart_at'] }}</td>
                <td>{{ $line['pickup'] }}</td>
                <td>{{ $line['return_at'] }}</td>
                <td>{{ $line['dropoff'] }}</td>
                <td class="right">{{ $line['guests'] }}</td>
                <td>{{ $line['person_in_charge'] }}</td>
                <td class="right">{{ $line['unit_price'] }}</td>
                <td class="right">{{ $line['extra_fee'] }}</td>
                <td>{{ $line['notes'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($vm['passenger_total'] !== '' && $vm['passenger_total'] !== null)
        <p class="foot"><strong>Tổng (ước tính):</strong> {{ $vm['passenger_total'] }}</p>
    @endif

    @if(!empty($vm['overflow_note']))
        <p class="muted">{{ $vm['overflow_note'] }}</p>
    @endif

    <div class="foot">
        <strong>Người đề nghị:</strong> {{ $vm['requester_name'] }}
    </div>
</div>
</body>
</html>
