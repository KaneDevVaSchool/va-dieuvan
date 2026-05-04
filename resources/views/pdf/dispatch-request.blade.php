<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 22px 24px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #111;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 0.4pt solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }
        .nb { border: none !important; }
        .tc { text-align: center; }
        .tl { text-align: left; }
        .tr { text-align: right; }
        .bold { font-weight: bold; }
        .muted { font-size: 7pt; color: #333; }
        .red { color: #a01d33; }
        .header-meta td { font-size: 7.5pt; padding: 2px 4px; }
        .sec-title {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
        }
        .dots { border-bottom: 0.3pt dotted #000; min-height: 14px; }
        .logo { max-height: 52px; width: auto; }
        .hint-small { font-size: 6.5pt; font-style: italic; margin-top: 2px; }
        .cb { font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
        .sig-cell { min-height: 56px; vertical-align: top; }
    </style>
</head>
<body>
@php
    use App\Services\DispatchRequests\DispatchRequestPdfPresenter;
    $nTargets = count($targetGrid);
    $nRowsTargets = (int) ceil($nTargets / 4);
@endphp

{{-- Header --}}
<table style="margin-bottom: 6px;">
    <tr>
        <td class="nb" style="width: 22%; vertical-align: middle;">
            @if($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="VAS"/>
            @endif
        </td>
        <td class="nb tc" style="width: 56%; vertical-align: middle;">
            <div class="bold" style="font-size: 15pt; letter-spacing: 0.5px;">ĐỀ NGHỊ ĐIỀU VẬN</div>
            <div style="font-size: 10pt; margin-top: 2px;">(Điều chuyển Hàng hóa)</div>
        </td>
        <td class="nb" style="width: 22%; vertical-align: top;">
            <table class="header-meta" style="width: 100%;">
                <tr><td class="bold">Kí hiệu:</td><td>BM.03/MH.QT.04</td></tr>
                <tr><td class="bold">Ngày ban hành:</td><td>29/08/2025</td></tr>
                <tr><td class="bold">Lần ban hành:</td><td>01</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- A --}}
<table>
    <tr><td colspan="4" class="sec-title">A. Người đề nghị</td></tr>
    <tr>
        <td style="width: 25%;"><span class="bold">a.1</span> Họ và tên:</td>
        <td style="width: 25%;" class="dots">{{ $aName }}</td>
        <td style="width: 22%;"><span class="bold">a.2</span> Email VA của nhân viên:</td>
        <td style="width: 28%;" class="dots">{{ $aEmail }}</td>
    </tr>
    <tr>
        <td><span class="bold">a.3</span> Số điện thoại:</td>
        <td class="dots">{{ $aPhone }}</td>
        <td><span class="bold">a.4</span> Đơn vị:</td>
        <td class="dots">{{ $aUnit }}</td>
    </tr>
</table>

{{-- B --}}
<table style="margin-top: 4px;">
    <tr><td colspan="2" class="sec-title">B. Mục đích sử dụng</td></tr>
    <tr>
        <td style="width: 18%;"><span class="bold">b.1</span> Mục đích sử dụng:</td>
        <td class="dots">{{ $purpose }}</td>
    </tr>
    <tr>
        <td><span class="bold">b.2</span> Căn cứ đề xuất:<br/><span class="muted">Tờ trình số …/ ngày &amp; nội dung</span></td>
        <td class="dots">
            @if($basisLine)
                {{ $basisLine }}
            @else
                &nbsp;
            @endif
        </td>
    </tr>
</table>

{{-- C --}}
<table style="margin-top: 4px;">
    <tr><td colspan="4" class="sec-title">C. Thời gian</td></tr>
    <tr>
        <td style="width: 20%;"><span class="bold">c.1</span> Ngày đề xuất:</td>
        <td style="width: 30%;" class="dots">{{ $proposedDate }}</td>
        <td style="width: 22%;"><span class="bold">c.2</span> Ngày cần sử dụng xe:</td>
        <td class="dots">{{ $dateNeeded }}</td>
    </tr>
    <tr>
        <td colspan="4">
            <div class="hint-small">Ghi chú: Tối thiểu 03 ngày làm việc trước ngày cấp xe; từ 2000kg trở lên cần đăng ký sớm hơn (theo quy trình nội bộ).</div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <span class="cb">{{ $isUrgent ? '☑' : '☐' }}</span> <span class="bold">Gấp</span>
            &nbsp;&nbsp; <span class="bold">Lý do:</span> <span class="dots">{{ $isUrgent ? $urgentReason : '' }}</span>
        </td>
        <td colspan="2" class="muted">Mã yêu cầu hệ thống: #{{ $dispatchRequest->id }} · Loại: {{ $tripType }}</td>
    </tr>
</table>

{{-- D --}}
<table style="margin-top: 4px;">
    <tr><td colspan="4" class="sec-title">D. Khu vực / đối tượng được phân bổ</td></tr>
    <tr>
        <td colspan="4"><span class="bold">d.1</span> Đối tượng sử dụng:</td>
    </tr>
    @for($r = 0; $r < $nRowsTargets; $r++)
        <tr>
            @for($c = 0; $c < 4; $c++)
                @php $i = $r * 4 + $c; @endphp
                <td style="width: 25%; font-size: 7.5pt;">
                    @if($i < $nTargets)
                        @php $tg = $targetGrid[$i]; @endphp
                        <span class="cb">{{ $tg['checked'] ? '☑' : '☐' }}</span> {{ $tg['label'] }}
                    @endif
                </td>
            @endfor
        </tr>
    @endfor
    <tr>
        <td colspan="4">
            <span class="bold">d.2</span> Nhân sự phụ trách điều phối — Họ tên:
            <span class="dots" style="display: inline-block; min-width: 140px;">{{ $coordName }}</span>
            &nbsp; Email: <span class="dots" style="display: inline-block; min-width: 160px;">{{ $coordEmail }}</span>
            &nbsp; SĐT: <span class="dots" style="display: inline-block; min-width: 100px;">{{ $coordPhone }}</span>
        </td>
    </tr>
</table>

{{-- E --}}
<table style="margin-top: 4px;">
    <tr>
        <td rowspan="2" class="tc bold" style="width: 2.5%;">STT</td>
        <td colspan="5" class="tc bold">Thông tin hàng hóa</td>
        <td colspan="3" class="tc bold">Điểm tập kết hàng hóa</td>
        <td colspan="3" class="tc bold">Điểm giao hàng hóa</td>
        <td rowspan="2" class="tc bold" style="width: 8%;">Loại hình vận chuyển<br/><span class="red muted">(Ghi chú của NV điều phối)</span></td>
        <td rowspan="2" class="tc bold" style="width: 9%;">Chi phí<br/><span class="muted">(Gồm VAT)</span></td>
    </tr>
    <tr>
        <td class="tc bold" style="width: 11%;">Tên hàng hóa</td>
        <td class="tc bold" style="width: 4%;">SL</td>
        <td class="tc bold" style="width: 9%;">Kích thước (d × r × c) (1 kiện)</td>
        <td class="tc bold" style="width: 7%;">Khối lượng (1 kiện)</td>
        <td class="tc bold" style="width: 7%;">Ghi chú</td>
        <td class="tc bold" style="width: 6%;">Thời gian</td>
        <td class="tc bold" style="width: 8%;">Địa điểm</td>
        <td class="tc bold" style="width: 7%;">Thông tin người giao</td>
        <td class="tc bold" style="width: 6%;">Thời gian</td>
        <td class="tc bold" style="width: 8%;">Địa điểm</td>
        <td class="tc bold" style="width: 7%;">Thông tin người nhận</td>
    </tr>
    @foreach($sectionERows as $idx => $row)
        <tr>
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
        <td colspan="13" class="tr bold">Tổng cộng</td>
        <td class="tr bold">{{ $grandTotalFmt }}</td>
    </tr>
</table>

<table style="margin-top: 4px;">
    <tr>
        <td>
            <div class="bold" style="margin-bottom: 3px;">e.1.1 Các ghi chú khác đề xuất</div>
            <div><span class="cb">{{ $needPorters ? '☑' : '☐' }}</span> Yêu cầu bốc xếp / nhân công hỗ trợ
                &nbsp; Số lượng: <span class="dots" style="display:inline-block;min-width:40px;">{{ $needPorters ? $porterQty : '' }}</span>
                &nbsp; Chi phí phát sinh: <span class="dots" style="display:inline-block;min-width:60px;">{{ $needPorters ? $porterCost : '' }}</span>
            </div>
            <div style="margin-top: 2px;"><span class="cb">{{ $interprovincial ? '☑' : '☐' }}</span> Gửi chành xe đi tỉnh
                &nbsp; Chi phí phát sinh: <span class="dots" style="display:inline-block;min-width:60px;">{{ $interprovincial ? $interprovincialCost : '' }}</span>
            </div>
            @if($cargoExtraNotes)
                <div class="muted" style="margin-top: 4px;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
            @endif
        </td>
        <td class="muted" style="width: 28%; font-size: 7pt;"><em>(Vui lòng liên hệ với NV Điều vận để điền thông tin về chi phí)</em></td>
    </tr>
</table>

{{-- F --}}
<table style="margin-top: 8px;">
    <tr><td colspan="3" class="sec-title">F. Phần xác nhận của các bên liên quan</td></tr>
    <tr>
        <td class="tc sig-cell" style="width: 34%;">
            <div class="bold">Trưởng phòng mua hàng</div>
            <div style="margin-top: 28px;" class="bold">Phạm Thanh Hùng</div>
            <div class="muted" style="margin-top: 8px;">Giám đốc … (bổ sung tùy vào lĩnh vực và thẩm quyền)</div>
        </td>
        <td class="tc sig-cell" style="width: 33%;">
            <div class="bold">Người đề xuất</div>
            <div class="muted" style="margin-top: 48px;">Giám đốc Vận hành</div>
        </td>
        <td class="tc sig-cell" style="width: 33%;">
            <div class="bold">Trưởng đơn vị đề xuất</div>
            <div class="muted" style="margin-top: 20px;">Tổng Giám đốc</div>
            <div style="margin-top: 18px;" class="bold">Bùi Quang Minh</div>
        </td>
    </tr>
</table>

{{-- G --}}
<table style="margin-top: 6px;">
    <tr><td class="sec-title tl" style="padding-left: 6px;">G. Phần xác nhận của phòng Mua Hàng</td></tr>
    <tr>
        <td>
            <div style="margin: 4px 0;"><span class="bold">g.1</span> Mã vận đơn (PO): <span class="dots">{{ $poCode }}</span></div>
            <div><span class="bold">g.2</span> Ngày nhận đề nghị (đã được phê duyệt): <span class="dots">{{ $g2Date }}</span></div>
        </td>
    </tr>
</table>

</body>
</html>
