<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 22px 24px; }
        * {
            box-sizing: border-box;
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #111;
            margin: 0;
        }
        h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }
        .section-header {
            font-size: 11pt;
            font-weight: bold;
            background: #222;
            color: #fff;
            padding: 4px 8px;
            text-align: center;
        }
        .header-meta {
            font-size: 9pt;
            text-align: right;
        }
        .header-meta td {
            border: none;
            padding: 1px 0;
            font-size: 9pt;
        }
        .label { font-size: 10pt; font-weight: bold; }
        .value { font-size: 10pt; }
        table.data { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.data th, table.data td {
            border: 1px solid #333;
            padding: 3px 4px;
            vertical-align: top;
        }
        table.data thead th { background: #f0f0f0; font-size: 10pt; }
        .nb { border: none !important; }
        .tc { text-align: center; }
        .tl { text-align: left; }
        .tr { text-align: right; }
        .dots { border-bottom: 0.5pt dotted #000; min-height: 14px; }
        .logo { width: 90px; height: auto; }
        .hint-small { font-size: 9pt; font-style: italic; margin-top: 4px; color: #333; }
        .p2p-note {
            font-size: 10pt;
            font-style: italic;
            margin: 6px 0;
            padding: 6px 8px;
            border: 1px dashed #333;
            background: #fafafa;
        }
        .cb { font-size: 11pt; }
        .muted { color: #333; }
    </style>
</head>
<body>
@php
    use App\Services\DispatchRequests\DispatchRequestPdfPresenter;
    $pdfCheckbox = static function (bool $checked): string {
        return $checked
            ? '<span style="font-family:DejaVu Sans,sans-serif;font-size:11pt;">&#x2611;</span>'
            : '<span style="font-family:DejaVu Sans,sans-serif;font-size:11pt;">&#x2610;</span>';
    };
    $nTargets = count($targetGrid);
    $nRowsTargets = (int) ceil($nTargets / 4);
@endphp

<table style="width:100%; margin-bottom:10px; border-collapse:collapse;">
    <tr>
        <td class="nb" style="width:24%; vertical-align:middle;">
            @if($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="VAS"/>
            @endif
        </td>
        <td class="nb" style="width:52%; vertical-align:middle;">
            <h1>ĐỀ NGHỊ ĐIỀU VẬN</h1>
            <div class="tc value" style="font-size:11pt; margin-top:4px;">
                @if($isCargo)
                    (Điều chuyển Hàng hóa)
                @elseif($isP2P)
                    (Vận chuyển Điểm — Điểm)
                @elseif($isBusiness)
                    (Công tác)
                @else
                    (Đưa đón tận nơi)
                @endif
            </div>
        </td>
        <td class="nb header-meta" style="width:24%; vertical-align:top;">
            <table style="width:100%; border-collapse:collapse;" class="header-meta">
                <tr><td class="tl label">Kí hiệu:</td><td class="tr">BM.03/MH.QT.04</td></tr>
                <tr><td class="tl label">Ngày ban hành:</td><td class="tr">29/08/2025</td></tr>
                <tr><td class="tl label">Lần ban hành:</td><td class="tr">01</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="data">
    <tr><td colspan="4" class="section-header">A. Người đề nghị</td></tr>
    <tr>
        <td style="width:22%;" class="label">a.1 Họ và tên:</td>
        <td style="width:28%;" class="dots value">{{ $aName }}</td>
        <td style="width:22%;" class="label">a.2 Email VA của nhân viên:</td>
        <td class="dots value">{{ $aEmail }}</td>
    </tr>
    <tr>
        <td class="label">a.3 Số điện thoại:</td>
        <td class="dots value">{{ $aPhone }}</td>
        <td class="label">a.4 Đơn vị:</td>
        <td class="dots value">{{ $aUnit }}</td>
    </tr>
</table>

<table class="data" style="margin-top:6px;">
    <tr><td colspan="2" class="section-header">B. Mục đích sử dụng</td></tr>
    <tr>
        <td style="width:20%;" class="label">b.1 Mục đích sử dụng:</td>
        <td class="dots value">{{ $purpose }}</td>
    </tr>
    <tr>
        <td class="label">b.2 Căn cứ đề xuất:<br/><span class="hint-small">Tờ trình số …/ ngày &amp; nội dung</span></td>
        <td class="dots value">@if($basisLine){{ $basisLine }}@else&nbsp;@endif</td>
    </tr>
</table>

<table class="data" style="margin-top:6px;">
    <tr><td colspan="4" class="section-header">C. Thời gian</td></tr>
    <tr>
        <td style="width:18%;" class="label">c.1 Ngày đề xuất:</td>
        <td style="width:32%;" class="dots value">{{ $proposedDate }}</td>
        <td style="width:22%;" class="label">c.2 Ngày cần sử dụng xe:</td>
        <td class="dots value">{{ $dateNeeded }}</td>
    </tr>
    <tr>
        <td colspan="4" class="hint-small">Ghi chú: Tối thiểu 03 ngày làm việc trước ngày cấp xe; từ 2000kg trở lên cần đăng ký sớm hơn (theo quy trình nội bộ).</td>
    </tr>
    <tr>
        <td colspan="2" class="value">
            <span class="cb">{!! $pdfCheckbox($isUrgent) !!}</span> <span class="label">Gấp</span>
            &nbsp;&nbsp; <span class="label">Lý do:</span> <span class="dots">{{ $isUrgent ? $urgentReason : '' }}</span>
        </td>
        <td colspan="2" class="muted tr value" style="font-size:9pt;">Mã yêu cầu: #{{ $dispatchRequest->id }} — Loại: {{ $tripType }}</td>
    </tr>
</table>

<table class="data" style="margin-top:6px;">
    <tr><td colspan="4" class="section-header">D. Khu vực / đối tượng được phân bổ</td></tr>
    <tr><td colspan="4" class="label">d.1 Đối tượng sử dụng:</td></tr>
    @for($r = 0; $r < $nRowsTargets; $r++)
        <tr>
            @for($c = 0; $c < 4; $c++)
                @php $i = $r * 4 + $c; @endphp
                <td style="width:25%; font-size:10pt;">
                    @if($i < $nTargets)
                        @php $tg = $targetGrid[$i]; @endphp
                        <span class="cb">{!! $pdfCheckbox($tg['checked']) !!}</span> {{ $tg['label'] }}
                    @endif
                </td>
            @endfor
        </tr>
    @endfor
    <tr>
        <td colspan="4" class="value">
            <span class="label">d.2 Nhân sự phụ trách điều phối</span> — Họ tên:
            <span class="dots" style="display:inline-block;min-width:120px;">{{ $coordName }}</span>
            &nbsp; Email: <span class="dots" style="display:inline-block;min-width:150px;">{{ $coordEmail }}</span>
            &nbsp; SĐT: <span class="dots" style="display:inline-block;min-width:90px;">{{ $coordPhone }}</span>
        </td>
    </tr>
</table>

<div class="section-header" style="margin-top:8px;">E. Nội dung đề nghị vận chuyển</div>

@if($isP2P && $p2pNote)
    <div class="p2p-note">{{ $p2pNote }}</div>
@endif

@if($isCargo)
<table class="data" style="margin-top:6px; width:100%; border-collapse:collapse; font-size:10pt;">
    <thead>
        <tr>
            <th rowspan="2" style="width:4%;" class="tc">STT</th>
            <th colspan="5" class="tc">Thông tin hàng hóa</th>
            <th colspan="3" class="tc">Điểm tập kết hàng hóa</th>
            <th colspan="3" class="tc">Điểm giao hàng hóa</th>
            <th rowspan="2" style="width:8%; color:#c00; font-style:italic;" class="tc">Loại hình<br/><span style="font-size:8pt;">(NV điều phối)</span></th>
            <th rowspan="2" style="width:8%;" class="tc">Chi phí<br/><span style="font-size:8pt;">(Gồm VAT)</span></th>
        </tr>
        <tr style="background:#f0f0f0;">
            <th style="width:12%;" class="tc">Tên hàng hóa</th>
            <th style="width:6%;" class="tc">SL</th>
            <th style="width:10%;" class="tc">Kích thước (DxRxC)</th>
            <th style="width:8%;" class="tc">Khối lượng</th>
            <th style="width:8%;" class="tc">Ghi chú</th>
            <th style="width:7%;" class="tc">Thời gian</th>
            <th style="width:8%;" class="tc">Địa điểm</th>
            <th style="width:8%;" class="tc">Người giao</th>
            <th style="width:7%;" class="tc">Thời gian</th>
            <th style="width:8%;" class="tc">Địa điểm</th>
            <th style="width:8%;" class="tc">Người nhận</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cargoSectionRows as $idx => $row)
            <tr style="height:24px;">
                <td class="tc">{{ $idx + 1 }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="tc">{{ $row['qty'] }}</td>
                <td>{{ $row['dim'] }}</td>
                <td>{{ $row['weight'] }}</td>
                <td>{{ $row['inotes'] }}</td>
                <td>{{ $row['puTime'] }}</td>
                <td>{{ $row['puPlace'] }}</td>
                <td>{{ $row['puContact'] }}</td>
                <td>{{ $row['delTime'] }}</td>
                <td>{{ $row['delPlace'] }}</td>
                <td>{{ $row['delContact'] }}</td>
                <td>{{ $row['transport'] }}</td>
                <td class="tr">{{ DispatchRequestPdfPresenter::formatCostCell($row['name'] ?? '', $row['cost'] ?? '') }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="13" class="tr label" style="padding:4px 8px;">Tổng:</td>
            <td class="tr label">{{ $grandTotalFmt }}</td>
        </tr>
    </tbody>
</table>

<table class="data" style="margin-top:6px;">
    <tr>
        <td style="width:72%;">
            <div class="label" style="margin-bottom:4px;">e.1.1 Các ghi chú khác đề xuất</div>
            <div><span class="cb">{!! $pdfCheckbox($needPorters) !!}</span> Yêu cầu bốc xếp / nhân công hỗ trợ
                &nbsp; Số lượng: <span class="dots" style="display:inline-block;min-width:36px;">{{ $needPorters ? $porterQty : '' }}</span>
                &nbsp; Chi phí phát sinh: <span class="dots" style="display:inline-block;min-width:50px;">{{ $needPorters ? $porterCost : '' }}</span>
            </div>
            <div style="margin-top:4px;"><span class="cb">{!! $pdfCheckbox($interprovincial) !!}</span> Gửi chành xe đi tỉnh
                &nbsp; Chi phí phát sinh: <span class="dots" style="display:inline-block;min-width:50px;">{{ $interprovincial ? $interprovincialCost : '' }}</span>
            </div>
            @if($cargoExtraNotes)
                <div class="muted" style="margin-top:6px; font-size:10pt;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
            @endif
        </td>
        <td class="muted" style="width:28%; font-size:9pt; vertical-align:top;"><em>(Vui lòng liên hệ NV Điều vận để điền thông tin chi phí)</em></td>
    </tr>
</table>
@endif

@if(!$isCargo && !$isBusiness)
    <div class="label" style="margin-top:8px; margin-bottom:4px;">
        @if($isP2P)
            E.1 Bảng nội dung hành trình (Điểm — Điểm)
        @else
            E.1 Bảng nội dung hành khách
        @endif
    </div>
    <table class="data" style="width:100%; font-size:10pt;">
        <thead>
            <tr style="background:#f0f0f0;">
                <th style="width:4%;" class="tc">STT</th>
                <th style="width:16%;" class="tc">Diễn giải</th>
                <th style="width:6%;" class="tc">SL khách</th>
                <th style="width:9%;" class="tc">TG đi</th>
                <th style="width:11%;" class="tc">Điểm đón</th>
                <th style="width:9%;" class="tc">TG về</th>
                <th style="width:11%;" class="tc">Điểm trả</th>
                <th style="width:10%;" class="tc">Người phụ trách</th>
                <th style="width:9%;" class="tc">Đơn giá</th>
                <th style="width:8%;" class="tc">Phụ thu</th>
                <th style="width:7%;" class="tc">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @foreach($passengerSectionRows as $idx => $row)
                <tr style="height:22px;">
                    <td class="tc">{{ $idx + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="tc">{{ $row['qty'] }}</td>
                    <td>{{ $row['puTime'] }}</td>
                    <td>{{ $row['puPlace'] }}</td>
                    <td>{{ $row['delTime'] }}</td>
                    <td>{{ $row['delPlace'] }}</td>
                    <td>{{ $row['puContact'] }}</td>
                    <td class="tr">{{ $row['unitPrice'] ?? '' }}</td>
                    <td class="tr">{{ $row['extraFee'] ?? '' }}</td>
                    <td>{{ $row['inotes'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="10" class="tr label" style="padding:4px 8px;">
                    Tổng ước tính:
                </td>
                <td class="tr label">{{ $grandTotalFmt }}</td>
            </tr>
        </tbody>
    </table>
@endif

@if($isBusiness)
    <div class="label" style="margin-top:8px; margin-bottom:4px;">E.2 Bảng nội dung công tác</div>
    <table class="data" style="width:100%; font-size:10pt;">
        <thead>
            <tr style="background:#f0f0f0;">
                <th style="width:4%;" class="tc">STT</th>
                <th style="width:14%;" class="tc">Diễn giải</th>
                <th style="width:5%;" class="tc">SL</th>
                <th style="width:10%;" class="tc">Điểm dừng / cung đường</th>
                <th style="width:8%;" class="tc">Ghi chú</th>
                <th style="width:9%;" class="tc">TG đi</th>
                <th style="width:10%;" class="tc">Điểm đi</th>
                <th style="width:9%;" class="tc">TG về</th>
                <th style="width:10%;" class="tc">Điểm đến</th>
                <th style="width:21%;" class="tc">Khác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($businessSectionRows as $idx => $row)
                <tr style="min-height:22px;">
                    <td class="tc">{{ $idx + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="tc">{{ $row['qty'] }}</td>
                    <td>{{ $row['dim'] }}</td>
                    <td>{{ $row['inotes'] }}</td>
                    <td>{{ $row['puTime'] }}</td>
                    <td>{{ $row['puPlace'] }}</td>
                    <td>{{ $row['delTime'] }}</td>
                    <td>{{ $row['delPlace'] }}</td>
                    <td class="tc">{{ $row['delContact'] ?: '—' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="10" class="tr label">Tổng (ước tính): {{ $grandTotalFmt }}</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- F: chữ ký --}}
<table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:10pt;">
    <tr>
        <td colspan="3" class="section-header">F. Phần xác nhận của các bên liên quan</td>
    </tr>
    <tr>
        <th style="width:33%; border:1px solid #333; padding:4px; text-align:center; font-size:10pt;">Trưởng phòng mua hàng</th>
        <th style="width:33%; border:1px solid #333; padding:4px; text-align:center; font-size:10pt;">Người đề xuất</th>
        <th style="width:34%; border:1px solid #333; padding:4px; text-align:center; font-size:10pt;">Trưởng đơn vị đề xuất</th>
    </tr>
    <tr>
        <td style="height:90px; border:1px solid #333; vertical-align:bottom; text-align:center; padding:4px; font-size:10pt;">
            <strong>Phạm Thanh Hùng</strong><br/>
            <span style="font-size:9pt; font-style:italic;">Giám đốc … (bổ sung tùy lĩnh vực)</span>
        </td>
        <td style="height:90px; border:1px solid #333; vertical-align:bottom; text-align:center; padding:4px;"></td>
        <td style="height:90px; border:1px solid #333; vertical-align:bottom; text-align:center; padding:4px;"></td>
    </tr>
    <tr>
        <td style="border:1px solid #333; text-align:center; padding:4px;">Giám đốc Vận hành</td>
        <td style="border:1px solid #333;"></td>
        <td style="border:1px solid #333; text-align:center; padding:4px;">Tổng Giám đốc</td>
    </tr>
    <tr>
        <td style="height:90px; border:1px solid #333; vertical-align:bottom; text-align:center; padding:4px;"><strong>Bùi Quang Minh</strong></td>
        <td style="height:90px; border:1px solid #333;"></td>
        <td style="height:90px; border:1px solid #333;"></td>
    </tr>
</table>

<table class="data" style="margin-top:8px;">
    <tr><td class="section-header tl" style="text-align:left; padding-left:8px;">G. Phần xác nhận của phòng Mua Hàng</td></tr>
    <tr>
        <td class="value" style="padding:8px;">
            <div style="margin-bottom:6px;"><span class="label">g.1</span> Mã vận đơn (PO): <span class="dots">{{ $poCode }}</span></div>
            <div><span class="label">g.2</span> Ngày nhận đề nghị (đã được phê duyệt): <span class="dots">{{ $g2Date }}</span></div>
        </td>
    </tr>
</table>

</body>
</html>
