<!DOCTYPE html>
<html lang="vi">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 20px 24px 24px 24px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: 'DejaVu Sans', sans-serif;
        }

        body {
            font-size: 7.5pt;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.45;
            background: #fff;
        }

        strong, b { font-weight: bold; }

        /* ── Document Header ── */
        .doc-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-header-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .doc-title-main {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1.5pt;
            color: #1a1a1a;
        }

        .doc-title-sub {
            font-size: 8pt;
            text-align: center;
            color: #555;
            margin-top: 3px;
            font-style: italic;
        }

        .doc-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            border: 1px solid #bbb;
        }

        .doc-meta-table tr:first-child td { border-top: none; }
        .doc-meta-table tr:last-child  td { border-bottom: none; }

        .doc-meta-table td {
            padding: 2.5px 5px;
            border-bottom: 0.5pt solid #ddd;
        }

        .doc-meta-label {
            color: #888;
            width: 78px;
        }

        .doc-meta-value {
            font-weight: bold;
            text-align: right;
            color: #1a1a1a;
        }

        .doc-meta-id {
            color: #1755a0;
            font-weight: bold;
        }

        .doc-header-divider {
            border: none;
            border-bottom: 2pt solid #1a1a1a;
            margin: 10px 0 8px;
        }

        /* ── Section Chrome ── */
        .sec-header {
            width: 100%;
            display: table;
            background: #f0f0f0;
            border: 1px solid #bbb;
            border-bottom: none;
            padding: 4pt 9pt;
            margin-top: 7pt;
        }

        .sec-badge {
            display: inline-block;
            background: #1a1a1a;
            color: #fff;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1pt 6pt;
            border-radius: 2pt;
            letter-spacing: 0.5pt;
            margin-right: 7pt;
            text-transform: uppercase;
        }

        .sec-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #1a1a1a;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
        }

        .sec-subtitle {
            font-size: 6.5pt;
            color: #666;
            font-style: italic;
            margin-left: 5pt;
            text-transform: none;
            font-weight: normal;
        }

        .sec-body {
            border: 1px solid #bbb;
            border-top: none;
        }

        .sec-body--flush { padding: 0; }

        /* ── Field Table ── */
        table.field-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.field-table td {
            border: 0.5pt solid #ddd;
            padding: 7pt 9pt 8pt;
            vertical-align: top;
        }

        table.field-table tr:first-child td { border-top: none; }

        .fl {
            font-size: 6pt;
            color: #888;
            font-weight: normal;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-bottom: 4pt;
        }

        .fl-hint-inline {
            font-style: italic;
            color: #aaa;
            font-weight: normal;
            text-transform: none;
            letter-spacing: 0;
        }

        .fv {
            font-size: 7.5pt;
            color: #1a1a1a;
            min-height: 14pt;
        }

        .fv-empty { color: #bbb; }

        /* ── Hint Bar ── */
        .hint-bar {
            background: #fafafa;
            border-top: 0.5pt solid #e0e0e0;
            border-bottom: 0.5pt solid #e0e0e0;
            padding: 4pt 9pt;
            font-size: 6.5pt;
            color: #888;
            font-style: italic;
        }

        /* ── Target Chips (only checked items shown) ── */
        .target-wrap {
            padding: 7pt 9pt 6pt;
            border-bottom: 0.5pt solid #ddd;
        }

        .target-chip {
            display: inline-block;
            background: #f0f6ff;
            border: 0.5pt solid #9ab8e0;
            color: #1a4a7a;
            font-size: 7pt;
            font-weight: bold;
            padding: 2pt 8pt;
            border-radius: 10pt;
            margin-right: 5pt;
            margin-bottom: 3pt;
        }

        /* ── Checkbox symbol ── */
        .cb-sym {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            margin-right: 3pt;
        }

        /* ── Data Tables ── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
        }

        table.data th {
            padding: 4pt 4pt;
            text-align: center;
            font-size: 6pt;
            font-weight: bold;
            border: 0.5pt solid #aaa;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
        }

        table.data th.col-info { background: #f0f0f0; color: #333; }
        table.data th.col-go   { background: #deeaf6; color: #1a4a7a; }
        table.data th.col-back { background: #dff0e0; color: #1a4d2a; }

        table.data td {
            padding: 5pt 4pt;
            border: 0.5pt solid #ddd;
            color: #1a1a1a;
            vertical-align: top;
            background: #fff;
            font-size: 7pt;
        }

        table.data tbody tr:nth-child(even) td { background: #fafafa; }

        table.data td.tl { text-align: left; }
        table.data td.tr { text-align: right; white-space: nowrap; }
        table.data td.tc { text-align: center; }

        table.data tr.row-total td {
            background: #f0f0f0;
            font-weight: bold;
            border: 0.5pt solid #aaa;
            border-top: 1.5pt solid #777;
            color: #1a1a1a;
        }

        table.data th .th-sub {
            font-size: 6pt;
            font-weight: normal;
            text-transform: none;
            display: block;
            margin-top: 1pt;
            letter-spacing: 0;
        }

        /* ── Signature Table ── */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.sig-table td {
            border: 0.5pt solid #aaa;
            text-align: center;
            vertical-align: bottom;
            padding: 4pt 6pt;
        }

        .sig-col-header {
            background: #f0f0f0;
            font-size: 7pt;
            font-weight: bold;
            color: #333;
            padding: 5pt 6pt;
            vertical-align: middle;
            height: 22pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
        }

        .sig-col-body {
            height: 90pt;
            vertical-align: bottom;
            background: #fff;
            padding-bottom: 8pt;
        }

        .sig-name {
            font-size: 7pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        .sig-role {
            font-size: 6pt;
            color: #888;
            font-style: italic;
        }

        /* ── Section G ── */
        .g-row {
            padding: 9pt 10pt;
            display: block;
        }

        .g-label {
            font-size: 7pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        .g-dots {
            border-bottom: 0.7pt dotted #aaa;
            display: inline-block;
            min-width: 190pt;
            margin-left: 5pt;
            vertical-align: bottom;
        }

        /* ── Chips ── */
        .chip-type {
            display: inline-block;
            background: #fff;
            border: 0.5pt solid #888;
            color: #333;
            font-size: 6.5pt;
            padding: 1pt 6pt;
            border-radius: 2pt;
            vertical-align: middle;
        }

        /* ── Misc ── */
        .muted-note {
            font-size: 6.5pt;
            color: #999;
            font-style: italic;
        }

        .d1-lbl {
            padding: 4pt 9pt 3pt;
            font-size: 6pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            border-bottom: 0.5pt solid #ddd;
        }

        /* ── Page Footer ── */
        .page-footer {
            text-align: center;
            font-size: 6pt;
            color: #aaa;
            margin-top: 10pt;
            padding-top: 5pt;
            border-top: 0.5pt solid #ddd;
            letter-spacing: 0.3pt;
        }
    </style>
</head>

<body>
    @php
        use App\Services\DispatchRequests\DispatchRequestPdfPresenter;

        $cb = static function (bool $checked): string {
            return $checked
                ? '<span style="font-family:DejaVu Sans,sans-serif;font-size:8pt;color:#1a1a1a;">&#x2611;</span>'
                : '<span style="font-family:DejaVu Sans,sans-serif;font-size:8pt;color:#ccc;">&#x2610;</span>';
        };

        $checkedTargets = array_filter($targetGrid, fn($t) => $t['checked']);
    @endphp

    {{-- ────────── DOCUMENT HEADER ────────── --}}
    <table class="doc-header-table">
        <tr>
            {{-- Spacer (no logo) --}}
            <td style="width:13%;"></td>

            {{-- Title --}}
            <td style="width:65%; vertical-align:middle;">
                <div class="doc-title-main">Đề Nghị Điều Vận</div>
                <div class="doc-title-sub">
                    @if($isCargo) (Điều chuyển Hàng hóa)
                    @elseif($isP2P) (Vận chuyển Điểm — Điểm)
                    @elseif($isBusiness) (Công tác)
                    @else (Đưa đón tận nơi)
                    @endif
                </div>
            </td>

            {{-- Meta --}}
            <td style="width:22%; vertical-align:top;">
                <table class="doc-meta-table">
                    <tr>
                        <td class="doc-meta-label">Ký hiệu</td>
                        <td class="doc-meta-value">BM.03/MH.QT.04</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Ngày ban hành</td>
                        <td class="doc-meta-value">29/08/2025</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Lần ban hành</td>
                        <td class="doc-meta-value">01</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Mã yêu cầu</td>
                        <td class="doc-meta-value doc-meta-id">#{{ $dispatchRequest->id }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="doc-header-divider" />

    {{-- ────────── A · NGƯỜI ĐỀ NGHỊ ────────── --}}
    <div class="sec-header">
        <span class="sec-badge">A</span>
        <span class="sec-title">Người đề nghị</span>
    </div>
    <div class="sec-body">
        <table class="field-table">
            <tr>
                <td style="width:50%;">
                    <div class="fl">a.1 &nbsp;Họ và tên</div>
                    <div class="fv">{{ $aName ?: '—' }}</div>
                </td>
                <td style="width:50%;">
                    <div class="fl">a.2 &nbsp;Email VA của nhân viên</div>
                    <div class="fv">{{ $aEmail ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="fl">a.3 &nbsp;Số điện thoại</div>
                    <div class="fv">{{ $aPhone ?: '—' }}</div>
                </td>
                <td>
                    <div class="fl">a.4 &nbsp;Đơn vị</div>
                    <div class="fv {{ !$aUnit ? 'fv-empty' : '' }}">{{ $aUnit ?: '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────── B · MỤC ĐÍCH ────────── --}}
    <div class="sec-header">
        <span class="sec-badge">B</span>
        <span class="sec-title">Mục đích sử dụng</span>
    </div>
    <div class="sec-body">
        <table class="field-table">
            <tr>
                <td colspan="2">
                    <div class="fl">b.1 &nbsp;Mục đích sử dụng</div>
                    <div class="fv {{ !$purpose ? 'fv-empty' : '' }}">{{ $purpose ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="fl">b.2 &nbsp;Căn cứ đề xuất
                        <span class="fl-hint-inline">— Tờ trình số …/ ngày &amp; nội dung</span>
                    </div>
                    <div class="fv {{ !$basisLine ? 'fv-empty' : '' }}">{{ $basisLine ?: '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────── C · THỜI GIAN ────────── --}}
    <div class="sec-header">
        <span class="sec-badge">C</span>
        <span class="sec-title">Thời gian</span>
    </div>
    <div class="sec-body">
        <table class="field-table">
            <tr>
                <td style="width:50%;">
                    <div class="fl">c.1 &nbsp;Ngày đề xuất</div>
                    <div class="fv">{{ $proposedDate ?: '—' }}</div>
                </td>
                <td style="width:50%;">
                    <div class="fl">c.2 &nbsp;Ngày cần sử dụng xe</div>
                    <div class="fv">{{ $dateNeeded ?: '—' }}</div>
                </td>
            </tr>
        </table>

        <div class="hint-bar">
            Lưu ý: Tối thiểu 03 ngày làm việc trước ngày cấp xe;
            từ 2.000 kg trở lên cần báo sớm ít nhất 05 ngày làm việc.
        </div>

        <table class="field-table" style="border-top:none;">
            <tr>
                <td style="width:60%; vertical-align:middle; border-top:none; padding:6pt 9pt;">
                    <span class="cb-sym">{!! $cb($isUrgent) !!}</span>
                    <strong style="font-size:7.5pt; color:#1a1a1a;">Gấp</strong>
                    @if($isUrgent)
                        &nbsp;<span style="font-size:7pt; color:#c0392b;">Lý do: {{ $urgentReason }}</span>
                    @else
                        &nbsp;<span style="font-size:7pt; color:#bbb;">Lý do: —</span>
                    @endif
                </td>
                <td style="width:40%; text-align:right; vertical-align:middle; border-top:none; padding:6pt 9pt;">
                    <span style="font-size:6.5pt; color:#888;">Loại yêu cầu:</span>
                    &nbsp;<span class="chip-type">{{ $tripType }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────── D · ĐỐI TƯỢNG ────────── --}}
    <div class="sec-header">
        <span class="sec-badge">D</span>
        <span class="sec-title">Khu vực / Đối tượng được phân bổ</span>
    </div>
    <div class="sec-body">
        <div class="d1-lbl">d.1 &nbsp;Đối tượng sử dụng</div>
        <div class="target-wrap">
            @forelse($checkedTargets as $tg)
                <span class="target-chip">&#x2714;&nbsp;{{ $tg['label'] }}</span>
            @empty
                <span style="font-size:7pt; color:#bbb;">—</span>
            @endforelse
        </div>

        <table class="field-table" style="border-top:0.5pt solid #ddd;">
            <tr>
                <td style="width:35%;">
                    <div class="fl">d.2 &nbsp;Nhân sự phụ trách — Họ tên</div>
                    <div class="fv">{{ $coordName ?: '—' }}</div>
                </td>
                <td style="width:42%;">
                    <div class="fl">Email</div>
                    <div class="fv">{{ $coordEmail ?: '—' }}</div>
                </td>
                <td style="width:23%;">
                    <div class="fl">SĐT</div>
                    <div class="fv {{ !$coordPhone ? 'fv-empty' : '' }}">{{ $coordPhone ?: '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────── E · NỘI DUNG VẬN CHUYỂN ────────── --}}
    <div class="sec-header" style="margin-top:9pt;">
        <span class="sec-badge">E</span>
        <span class="sec-title">Nội dung đề nghị vận chuyển</span>
        @if(!$isCargo && !$isBusiness)
            <span class="sec-subtitle">
                @if($isP2P) · E.1 Bảng hành trình (Điểm — Điểm)
                @else · E.1 Bảng nội dung hành khách
                @endif
            </span>
        @elseif($isBusiness)
            <span class="sec-subtitle">· E.2 Bảng nội dung công tác</span>
        @endif
    </div>

    <div class="sec-body sec-body--flush">

        {{-- ── CARGO TABLE ── --}}
        @if($isCargo)
            <table class="data">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-info tc" style="width:4%;">STT</th>
                        <th colspan="5" class="col-info tc">Thông tin hàng hóa</th>
                        <th colspan="3" class="col-go tc">Điểm tập kết</th>
                        <th colspan="3" class="col-back tc">Điểm giao</th>
                        <th rowspan="2" class="col-info tc" style="width:8%;">
                            Loại hình
                            <span class="th-sub">(NV điều phối)</span>
                        </th>
                        <th rowspan="2" class="col-info tc" style="width:10%;">
                            Chi phí
                            <span class="th-sub">(Gồm VAT)</span>
                        </th>
                    </tr>
                    <tr>
                        <th class="col-info tl" style="width:10%;">Tên hàng hóa</th>
                        <th class="col-info tc" style="width:6%;">SL</th>
                        <th class="col-info tc" style="width:10%;">Kích thước (DxRxC)</th>
                        <th class="col-info tc" style="width:8%;">Khối lượng</th>
                        <th class="col-info tl" style="width:8%;">Ghi chú</th>
                        <th class="col-go tc" style="width:7%;">Thời gian</th>
                        <th class="col-go tl" style="width:8%;">Địa điểm</th>
                        <th class="col-go tl" style="width:8%;">Người giao</th>
                        <th class="col-back tc" style="width:7%;">Thời gian</th>
                        <th class="col-back tl" style="width:8%;">Địa điểm</th>
                        <th class="col-back tl" style="width:8%;">Người nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cargoSectionRows as $idx => $row)
                        <tr>
                            <td class="tc">{{ $idx + 1 }}</td>
                            <td class="tl">{{ $row['name'] }}</td>
                            <td class="tc">{{ $row['qty'] }}</td>
                            <td class="tl">{{ $row['dim'] }}</td>
                            <td class="tl">{{ $row['weight'] }}</td>
                            <td class="tl">{{ $row['inotes'] }}</td>
                            <td>{{ $row['puTime'] }}</td>
                            <td class="tl">{{ $row['puPlace'] }}</td>
                            <td class="tl">{{ $row['puContact'] }}</td>
                            <td>{{ $row['delTime'] }}</td>
                            <td class="tl">{{ $row['delPlace'] }}</td>
                            <td class="tl">{{ $row['delContact'] }}</td>
                            <td>{{ $row['transport'] }}</td>
                            <td class="tr">
                                {{ DispatchRequestPdfPresenter::formatCostCell($row['name'] ?? '', $row['cost'] ?? '') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="13" class="tr" style="padding-right:8pt;">Tổng:</td>
                        <td class="tr">{{ $grandTotalFmt }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="field-table" style="border-top:0.5pt solid #ddd;">
                <tr>
                    <td style="width:72%;">
                        <div class="fl">e.1.1 &nbsp;Các ghi chú khác</div>
                        <div class="fv">
                            <span class="cb-sym">{!! $cb($needPorters) !!}</span>
                            Yêu cầu bốc xếp / nhân công hỗ trợ
                            &nbsp; Số lượng: <strong>{{ $needPorters ? $porterQty : '—' }}</strong>
                            &nbsp; Chi phí phát sinh: <strong>{{ $needPorters ? $porterCost : '—' }}</strong>
                        </div>
                        <div class="fv" style="margin-top:4pt;">
                            <span class="cb-sym">{!! $cb($interprovincial) !!}</span>
                            Gửi chành xe đi tỉnh
                            &nbsp; Chi phí phát sinh: <strong>{{ $interprovincial ? $interprovincialCost : '—' }}</strong>
                        </div>
                        @if($cargoExtraNotes)
                            <div class="muted-note" style="margin-top:5pt;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
                        @endif
                    </td>
                    <td class="muted-note" style="width:28%; vertical-align:top;">
                        (Vui lòng liên hệ NV Điều vận để điền thông tin chi phí)
                    </td>
                </tr>
            </table>
        @endif

        {{-- ── PASSENGER / P2P TABLE ── --}}
        @if(!$isCargo && !$isBusiness)
            <table class="data">
                <thead>
                    <tr>
                        <th class="col-info tc" style="width:4%;">STT</th>
                        <th class="col-info tl" style="width:12%;">Diễn giải</th>
                        <th class="col-info tc" style="width:5%;">SL</th>
                        <th class="col-go   tc" style="width:9%;">TG đi</th>
                        <th class="col-go   tl" style="width:11%;">Điểm đón</th>
                        <th class="col-back tc" style="width:9%;">TG về</th>
                        <th class="col-back tl" style="width:11%;">Điểm trả</th>
                        <th class="col-info tl" style="width:9%;">Phụ trách</th>
                        <th class="col-info tc" style="width:11%;">Đơn giá</th>
                        <th class="col-info tc" style="width:10%;">Phụ thu</th>
                        <th class="col-info tl" style="width:9%;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($passengerSectionRows as $idx => $row)
                        <tr>
                            <td class="tc">{{ $idx + 1 }}</td>
                            <td class="tl">{{ $row['name'] }}</td>
                            <td class="tc">{{ $row['qty'] }}</td>
                            <td>{{ $row['puTime'] }}</td>
                            <td class="tl">{{ $row['puPlace'] }}</td>
                            <td>{{ $row['delTime'] }}</td>
                            <td class="tl">{{ $row['delPlace'] }}</td>
                            <td class="tl">{{ $row['puContact'] }}</td>
                            <td class="tr">{{ $row['unitPrice'] ?? '' }}</td>
                            <td class="tr">{{ $row['extraFee'] ?? '' }}</td>
                            <td class="tl">{{ $row['inotes'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="10" class="tr" style="padding-right:8pt;">Tổng ước tính:</td>
                        <td class="tr">{{ $grandTotalFmt }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- ── BUSINESS TABLE ── --}}
        @if($isBusiness)
            <table class="data">
                <thead>
                    <tr>
                        <th class="col-info tc" style="width:4%;">STT</th>
                        <th class="col-info tl" style="width:14%;">Diễn giải</th>
                        <th class="col-info tc" style="width:5%;">SL</th>
                        <th class="col-info tl" style="width:10%;">Điểm dừng / cung đường</th>
                        <th class="col-info tl" style="width:8%;">Ghi chú</th>
                        <th class="col-go   tc" style="width:9%;">TG đi</th>
                        <th class="col-go   tl" style="width:10%;">Điểm đi</th>
                        <th class="col-back tc" style="width:9%;">TG về</th>
                        <th class="col-back tl" style="width:10%;">Điểm đến</th>
                        <th class="col-info tl" style="width:21%;">Khác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($businessSectionRows as $idx => $row)
                        <tr>
                            <td class="tc">{{ $idx + 1 }}</td>
                            <td class="tl">{{ $row['name'] }}</td>
                            <td class="tc">{{ $row['qty'] }}</td>
                            <td class="tl">{{ $row['dim'] }}</td>
                            <td class="tl">{{ $row['inotes'] }}</td>
                            <td>{{ $row['puTime'] }}</td>
                            <td class="tl">{{ $row['puPlace'] }}</td>
                            <td>{{ $row['delTime'] }}</td>
                            <td class="tl">{{ $row['delPlace'] }}</td>
                            <td class="tc">{{ $row['delContact'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="9" class="tr" style="padding-right:8pt;">Tổng (ước tính):</td>
                        <td class="tr">{{ $grandTotalFmt }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

    </div>

    {{-- ────────── F · XÁC NHẬN CÁC BÊN ────────── --}}
    <div class="sec-header" style="margin-top:9pt;">
        <span class="sec-badge">F</span>
        <span class="sec-title">Phần xác nhận của các bên liên quan</span>
    </div>
    <table class="sig-table" style="border:0.5pt solid #aaa;">
        <tr>
            <td class="sig-col-header">Trưởng phòng Mua hàng</td>
            <td class="sig-col-header">Người đề xuất</td>
            <td class="sig-col-header">Trưởng đơn vị đề xuất</td>
        </tr>
        <tr>
            <td class="sig-col-body">
                <span class="sig-name">Phạm Thanh Hùng</span><br />
                <span class="sig-role">Giám đốc … (tùy lĩnh vực)</span>
            </td>
            <td class="sig-col-body"></td>
            <td class="sig-col-body"></td>
        </tr>
        <tr>
            <td class="sig-col-header">Giám đốc Vận hành</td>
            <td class="sig-col-header">&nbsp;</td>
            <td class="sig-col-header">Phó Tổng Giám đốc</td>
        </tr>
        <tr>
            <td class="sig-col-body">
                <span class="sig-name">Vũ Quốc Vương</span>
            </td>
            <td class="sig-col-body"></td>
            <td class="sig-col-body"></td>
        </tr>
    </table>

    {{-- ────────── G · PHÒNG MUA HÀNG ────────── --}}
    <div class="sec-header" style="margin-top:9pt;">
        <span class="sec-badge">G</span>
        <span class="sec-title">Phần xác nhận của phòng Mua Hàng</span>
    </div>
    <div class="sec-body g-row">
        <div style="margin-bottom:9pt;">
            <span class="g-label">g.1 &nbsp;Mã vận đơn (PO):</span>
            <span class="g-dots">{{ $poCode }}</span>
        </div>
        <div>
            <span class="g-label">g.2 &nbsp;Ngày nhận đề nghị (đã được phê duyệt):</span>
            <span class="g-dots">{{ $g2Date }}</span>
        </div>
    </div>

    <div class="page-footer">
        BM.03/MH.QT.04 &nbsp;·&nbsp; Đề Nghị Điều Vận &nbsp;·&nbsp; Vietnam America Schools
    </div>

</body>

</html>
