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
            font-size: 9.5pt;
            color: #18181b;
            margin: 0;
            line-height: 1.45;
            background: #fff;
        }

        /* ══════════════════════════════════════════════
           HEADER
        ══════════════════════════════════════════════ */
        .doc-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .doc-header-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .doc-title-main {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #18181b;
        }

        .doc-title-sub {
            font-size: 8.5pt;
            text-align: center;
            color: #71717a;
            font-style: italic;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }

        /* Meta block (top-right) */
        .doc-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            border: 0.5pt solid #e4e4e7;
            border-radius: 3pt;
        }

        .doc-meta-table td {
            padding: 2.5px 6px;
            border: none;
            border-bottom: 0.5pt solid #f4f4f5;
        }

        .doc-meta-table tr:last-child td {
            border-bottom: none;
        }

        .doc-meta-label {
            color: #a1a1aa;
            width: 90px;
        }

        .doc-meta-value {
            font-weight: bold;
            text-align: right;
            color: #3f3f46;
        }

        .doc-meta-id {
            color: #991b1b;
            font-size: 8.5pt;
        }

        /* Divider */
        .doc-header-divider {
            border: none;
            border-bottom: 2pt solid #991b1b;
            margin: 8px 0 10px 0;
        }

        .doc-header-accent {
            border: none;
            border-bottom: 0.5pt solid #fca5a5;
            margin: 0 0 10px 0;
        }

        /* ══════════════════════════════════════════════
           SECTION HEADER
        ══════════════════════════════════════════════ */
        .sec-header {
            width: 100%;
            display: table;
            background: linear-gradient(90deg, #fef2f2 0%, #fafafa 100%);
            border-left: 3.5pt solid #991b1b;
            border-top: 0.5pt solid #fca5a5;
            border-right: 0.5pt solid #e4e4e7;
            padding: 5pt 9pt 4pt 9pt;
            margin-top: 9pt;
            margin-bottom: 0;
        }

        .sec-badge {
            display: inline-block;
            background: #991b1b;
            color: #fff;
            font-size: 7pt;
            font-weight: bold;
            padding: 1.5pt 6pt;
            border-radius: 2pt;
            letter-spacing: 0.8px;
            margin-right: 6pt;
            text-transform: uppercase;
        }

        .sec-title {
            font-size: 9pt;
            font-weight: bold;
            color: #18181b;
            letter-spacing: 0.2px;
        }

        .sec-subtitle {
            font-size: 7.5pt;
            color: #991b1b;
            font-style: italic;
            font-weight: normal;
            margin-left: 4pt;
        }

        /* ══════════════════════════════════════════════
           SECTION BODY WRAPPER
        ══════════════════════════════════════════════ */
        .sec-body {
            border-left: 0.5pt solid #e4e4e7;
            border-right: 0.5pt solid #e4e4e7;
            border-bottom: 0.5pt solid #e4e4e7;
            border-top: none;
        }

        .sec-body--flush {
            padding: 0;
        }

        /* ══════════════════════════════════════════════
           FIELD TABLE
        ══════════════════════════════════════════════ */
        table.field-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.field-table td {
            border-right: 0.5pt solid #e4e4e7;
            border-bottom: 0.5pt solid #e4e4e7;
            padding: 5pt 8pt;
            vertical-align: top;
        }

        table.field-table tr:last-child td {
            border-bottom: none;
        }

        table.field-table td:last-child {
            border-right: none;
        }

        .fl {
            font-size: 7pt;
            color: #a1a1aa;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2.5pt;
        }

        .fv {
            font-size: 9.5pt;
            color: #18181b;
            min-height: 12pt;
            font-weight: 500;
        }

        .fv-empty {
            color: #d4d4d8;
            font-style: italic;
            font-weight: normal;
        }

        /* ══════════════════════════════════════════════
           HINT / NOTE BARS
        ══════════════════════════════════════════════ */
        .hint-bar {
            background: #fffbeb;
            border-left: 3pt solid #f59e0b;
            border-bottom: 0.5pt solid #fde68a;
            padding: 4pt 9pt;
            font-size: 7.5pt;
            color: #78350f;
            font-style: italic;
        }

        .p2p-note-bar {
            background: #fffbeb;
            border-left: 3pt solid #f59e0b;
            border-bottom: 0.5pt solid #fde68a;
            padding: 5pt 9pt;
            font-size: 8pt;
            color: #78350f;
            font-style: italic;
        }

        /* ══════════════════════════════════════════════
           CHECKBOX GRID
        ══════════════════════════════════════════════ */
        table.cb-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.cb-table td {
            width: 25%;
            padding: 3pt 8pt;
            border-right: 0.5pt solid #e4e4e7;
            border-bottom: 0.5pt solid #e4e4e7;
            font-size: 8.5pt;
            color: #3f3f46;
            vertical-align: middle;
        }

        table.cb-table tr:last-child td {
            border-bottom: none;
        }

        table.cb-table td:last-child {
            border-right: none;
        }

        .cb-sym {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            margin-right: 3pt;
            color: #991b1b;
        }

        /* ══════════════════════════════════════════════
           DATA TABLE
        ══════════════════════════════════════════════ */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        table.data th {
            padding: 4.5pt 5pt;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.2px;
            border-right: 0.5pt solid #e4e4e7;
            border-bottom: 1pt solid #d4d4d8;
        }

        table.data th:last-child {
            border-right: none;
        }

        table.data th.col-go {
            background: #eff6ff;
            color: #1e40af;
        }

        table.data th.col-back {
            background: #f0fdf4;
            color: #166534;
        }

        table.data th.col-info {
            background: #f9fafb;
            color: #374151;
        }

        table.data td {
            padding: 4.5pt 5pt;
            text-align: center;
            border-right: 0.5pt solid #e4e4e7;
            border-bottom: 0.5pt solid #e4e4e7;
            color: #18181b;
            vertical-align: top;
        }

        table.data td:last-child {
            border-right: none;
        }

        table.data td.tl {
            text-align: left;
        }

        table.data td.tr {
            text-align: right;
        }

        table.data td.tc {
            text-align: center;
        }

        table.data tr:last-child td {
            border-bottom: none;
        }

        table.data tr.row-total td {
            background: #fef2f2;
            font-weight: bold;
            border-top: 1pt solid #fca5a5;
            color: #991b1b;
        }

        table.data tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        table.data tbody tr.row-total td {
            background: #fef2f2 !important;
        }

        /* ══════════════════════════════════════════════
           SIGNATURE TABLE
        ══════════════════════════════════════════════ */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.sig-table td {
            width: 33.3%;
            border: 0.5pt solid #e4e4e7;
            text-align: center;
            vertical-align: bottom;
            padding: 4pt 6pt;
        }

        .sig-col-header {
            background: #f9fafb;
            font-size: 8.5pt;
            font-weight: bold;
            color: #3f3f46;
            padding: 5pt 6pt;
            vertical-align: middle;
            height: 18pt;
            letter-spacing: 0.2px;
            border-bottom: 1pt solid #e4e4e7;
        }

        .sig-col-body {
            height: 54pt;
            vertical-align: bottom;
            background: #fff;
        }

        .sig-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #18181b;
        }

        .sig-role {
            font-size: 7.5pt;
            color: #71717a;
            font-style: italic;
        }

        /* ══════════════════════════════════════════════
           SECTION G
        ══════════════════════════════════════════════ */
        .g-row {
            padding: 6pt 9pt;
            display: block;
        }

        .g-label {
            font-size: 8.5pt;
            font-weight: bold;
            color: #3f3f46;
        }

        .g-dots {
            border-bottom: 0.7pt dotted #a1a1aa;
            display: inline-block;
            min-width: 200pt;
            margin-left: 5pt;
            vertical-align: bottom;
        }

        /* ══════════════════════════════════════════════
           FOOTER
        ══════════════════════════════════════════════ */
        .page-footer {
            text-align: center;
            font-size: 7pt;
            color: #d4d4d8;
            margin-top: 10pt;
            padding-top: 5pt;
            border-top: 0.5pt solid #e4e4e7;
            letter-spacing: 0.4px;
        }

        /* ══════════════════════════════════════════════
           MISC
        ══════════════════════════════════════════════ */
        .muted-note {
            font-size: 7.5pt;
            color: #71717a;
            font-style: italic;
        }

        /* Urgent / trip-type chip inline */
        .chip-urgent {
            display: inline-block;
            background: #fef2f2;
            border: 0.5pt solid #fca5a5;
            color: #991b1b;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1pt 5pt;
            border-radius: 2pt;
            vertical-align: middle;
        }

        .chip-type {
            display: inline-block;
            background: #f0f9ff;
            border: 0.5pt solid #bae6fd;
            color: #0369a1;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1pt 5pt;
            border-radius: 2pt;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    @php
        use App\Services\DispatchRequests\DispatchRequestPdfPresenter;

        $cb = static function (bool $checked): string {
            return $checked
                ? '<span style="font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#991b1b;">&#x2611;</span>'
                : '<span style="font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#d4d4d8;">&#x2610;</span>';
        };

        $nTargets = count($targetGrid);
        $nRowsTargets = (int) ceil($nTargets / 4);
    @endphp

    {{-- ────────── DOCUMENT HEADER ────────── --}}
    <table class="doc-header-table">
        <tr>
            {{-- Logo --}}
            <td style="width:17%; vertical-align:middle; padding-right:10px;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" style="width:82px; height:auto;" alt="VAS" />
                @endif
            </td>

            {{-- Title --}}
            <td style="width:66%; vertical-align:middle;">
                <div class="doc-title-main">Đề Nghị Điều Vận</div>
                <div class="doc-title-sub">
                    @if($isCargo) (Điều chuyển Hàng hóa)
                    @elseif($isP2P) (Vận chuyển Điểm — Điểm)
                    @elseif($isBusiness)(Công tác)
                    @else (Đưa đón tận nơi)
                    @endif
                </div>
            </td>

            {{-- Meta --}}
            <td style="width:17%; vertical-align:top;">
                <table class="doc-meta-table">
                    <tr>
                        <td class="doc-meta-label">Ký hiệu:</td>
                        <td class="doc-meta-value">BM.03/MH.QT.04</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Ngày ban hành:</td>
                        <td class="doc-meta-value">29/08/2025</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Lần ban hành:</td>
                        <td class="doc-meta-value">01</td>
                    </tr>
                    <tr>
                        <td class="doc-meta-label">Mã yêu cầu:</td>
                        <td class="doc-meta-value doc-meta-id">#{{ $dispatchRequest->id }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="doc-header-divider" />
    <hr class="doc-header-accent" />

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
                        <span style="font-weight:normal; text-transform:none; letter-spacing:0; color:#a1a1aa;">
                            — Tờ trình số …/ ngày &amp; nội dung
                        </span>
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
            ⚠&nbsp; Lưu ý: Tối thiểu 03 ngày làm việc trước ngày cấp xe;
            từ 2.000 kg trở lên cần báo sớm ít nhất 05 ngày làm việc.
        </div>

        <table class="field-table">
            <tr>
                <td style="width:60%; vertical-align:middle; padding:5pt 8pt;">
                    <span class="cb-sym">{!! $cb($isUrgent) !!}</span>
                    <span style="font-weight:bold; font-size:9pt; color:#991b1b;">Gấp</span>
                    @if($isUrgent)
                        &nbsp;<span style="font-size:8pt; color:#71717a;">Lý do: {{ $urgentReason }}</span>
                    @else
                        &nbsp;<span style="font-size:8pt; color:#d4d4d8; font-style:italic;">Lý do: —</span>
                    @endif
                </td>
                <td style="width:40%; text-align:right; vertical-align:middle; padding:5pt 8pt;">
                    <span style="font-size:7.5pt; color:#a1a1aa;">Loại yêu cầu:</span>
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
        <div style="padding:3.5pt 8pt 2.5pt; font-size:7pt; font-weight:bold;
                color:#a1a1aa; border-bottom:0.5pt solid #e4e4e7;
                text-transform:uppercase; letter-spacing:0.5px;">
            d.1 &nbsp;Đối tượng sử dụng
        </div>
        <table class="cb-table">
            @for($r = 0; $r < $nRowsTargets; $r++)
                <tr>
                    @for($c = 0; $c < 4; $c++)
                        @php $i = $r * 4 + $c; @endphp
                        <td>
                            @if($i < count($targetGrid))
                                @php $tg = $targetGrid[$i]; @endphp
                                <span class="cb-sym">{!! $cb($tg['checked']) !!}</span>{{ $tg['label'] }}
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>

        <table class="field-table" style="border-top:0.5pt solid #e4e4e7;">
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
    <div class="sec-header">
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

        @if($isP2P && $p2pNote)
            <div class="p2p-note-bar">{{ $p2pNote }}</div>
        @endif

        {{-- ── CARGO TABLE ── --}}
        @if($isCargo)
            <table class="data">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-info tc" style="width:4%;">STT</th>
                        <th colspan="5" class="col-info tc">Thông tin hàng hóa</th>
                        <th colspan="3" class="col-go tc">Điểm tập kết</th>
                        <th colspan="3" class="col-back tc">Điểm giao</th>
                        <th rowspan="2" class="col-info tc" style="width:8%; color:#991b1b; font-style:italic;">
                            Loại hình<br /><span style="font-size:7pt;">(NV điều phối)</span>
                        </th>
                        <th rowspan="2" class="col-info tc" style="width:8%;">
                            Chi phí<br /><span style="font-size:7pt;">(Gồm VAT)</span>
                        </th>
                    </tr>
                    <tr>
                        <th class="col-info tl" style="width:12%;">Tên hàng hóa</th>
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
                                {{ DispatchRequestPdfPresenter::formatCostCell($row['name'] ?? '', $row['cost'] ?? '') }}</td>
                        </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="13" class="tr" style="padding-right:8pt;">Tổng:</td>
                        <td class="tr">{{ $grandTotalFmt }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="field-table" style="border-top:0.5pt solid #e4e4e7;">
                <tr>
                    <td style="width:72%;">
                        <div class="fl">e.1.1 &nbsp;Các ghi chú khác</div>
                        <div class="fv" style="font-size:8.5pt;">
                            <span class="cb-sym">{!! $cb($needPorters) !!}</span>
                            Yêu cầu bốc xếp / nhân công hỗ trợ
                            &nbsp; Số lượng: <strong>{{ $needPorters ? $porterQty : '—' }}</strong>
                            &nbsp; Chi phí phát sinh: <strong>{{ $needPorters ? $porterCost : '—' }}</strong>
                        </div>
                        <div class="fv" style="font-size:8.5pt; margin-top:4pt;">
                            <span class="cb-sym">{!! $cb($interprovincial) !!}</span>
                            Gửi chành xe đi tỉnh
                            &nbsp; Chi phí phát sinh: <strong>{{ $interprovincial ? $interprovincialCost : '—' }}</strong>
                        </div>
                        @if($cargoExtraNotes)
                            <div class="muted-note" style="margin-top:5pt;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
                        @endif
                    </td>
                    <td class="muted-note" style="width:28%; vertical-align:top; font-size:7.5pt;">
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
                        <th class="col-info tl" style="width:14%;">Diễn giải</th>
                        <th class="col-info tc" style="width:5%;">SL</th>
                        <th class="col-go   tc" style="width:9%;">TG đi</th>
                        <th class="col-go   tl" style="width:11%;">Điểm đón</th>
                        <th class="col-back tc" style="width:9%;">TG về</th>
                        <th class="col-back tl" style="width:11%;">Điểm trả</th>
                        <th class="col-info tl" style="width:11%;">Phụ trách</th>
                        <th class="col-info tc" style="width:9%;">Đơn giá</th>
                        <th class="col-info tc" style="width:8%;">Phụ thu</th>
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
    <table class="sig-table" style="border:0.5pt solid #e4e4e7;">
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
            <td class="sig-col-header" style="background:#fff;">&nbsp;</td>
            <td class="sig-col-header">Tổng Giám đốc</td>
        </tr>
        <tr>
            <td class="sig-col-body">
                <span class="sig-name">Bùi Quang Minh</span>
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
        <div style="margin-bottom:6pt;">
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
