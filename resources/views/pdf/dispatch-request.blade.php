<!DOCTYPE html>
<html lang="vi">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @php
        // header.png 1999×393 px → ~41.3mm khi scale theo chiều ngang A4 (210mm)
        $hdrBandMm = 41.3;
        $pageOrient = $isCargo ? 'landscape' : 'portrait';
        $pageMarginTop = $isCargo ? '16mm' : ($hdrBandMm + 5.7) . 'mm';
        $pageMarginX   = $isCargo ? '12mm' : '18mm';
        $pageMarginBot = $isCargo ? '16mm' : '24mm';
        $bgW = $isCargo ? '297mm' : '210mm';
        $bgH = $isCargo ? '210mm' : '297mm';
        $useBranding = ! $isCargo;
    @endphp
    <style>
        @page {
            margin: {{ $pageMarginTop }} {{ $pageMarginX }} {{ $pageMarginBot }} {{ $pageMarginX }};
            size: A4 {{ $pageOrient }};
        }

        * {
            box-sizing: border-box;
            font-family: 'DejaVu Sans', sans-serif;
        }

        body {
            font-size: 7.5pt;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.5;
        }

        strong, b { font-weight: bold; }

        /* ── Fixed branding layers (repeat on every page, portrait only) ──
           DomPDF positions fixed elements relative to the content area, so
           we pull back by the page margins to reach the physical page edges.  ── */
        .bg-shell {
            position: fixed;
            top: -{{ $pageMarginTop }};
            left: -{{ $pageMarginX }};
            width: {{ $bgW }};
            height: {{ $bgH }};
            z-index: -1;
            overflow: hidden;
        }

        .bg-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $bgW }};
            height: {{ $bgH }};
            margin: 0;
            padding: 0;
            border: none;
            display: block;
        }

        .bg-hdr-mask {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $bgW }};
            height: {{ $hdrBandMm }}mm;
            background: #fff;
        }

        .hdr-layer {
            position: fixed;
            top: -{{ $pageMarginTop }};
            left: -{{ $pageMarginX }};
            width: {{ $bgW }};
            height: {{ $hdrBandMm }}mm;
            z-index: 2;
            margin: 0;
            padding: 0;
            border: none;
            display: block;
        }

        .page-content {
            position: relative;
        }

        /* ── Document title ── */
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5pt;
            text-align: center;
            color: #1a1a1a;
        }

        .doc-subtitle {
            font-size: 7.5pt;
            text-align: center;
            color: #555;
            font-style: italic;
            margin-top: 2pt;
        }

        .top-layout {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3pt;
        }

        .top-layout td {
            vertical-align: middle;
            padding: 0;
            border: none;
        }

        /* ── Meta box (top-right) ── */
        .meta-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            border: 0.7pt solid #bbb;
        }

        .meta-tbl td {
            padding: 2.5pt 5pt;
            border-bottom: 0.5pt solid #e0e0e0;
        }

        .meta-tbl tr:last-child td { border-bottom: none; }

        .meta-lbl { color: #888; width: 90px; }
        .meta-val { font-weight: bold; text-align: right; color: #1a1a1a; }
        .meta-code { color: #7B1E3B; font-weight: bold; font-size: 7.5pt; }

        /* ── Divider ── */
        .doc-divider {
            border: none;
            border-bottom: 1.5pt solid #7B1E3B;
            margin: 4pt 0 5pt;
        }

        /* ── Section chrome ── */
        .sec       { margin-top: 5pt; }
        .sec-avoid { page-break-inside: avoid; }

        .sec-hd {
            background: #7B1E3B;
            color: #fff;
            padding: 3.5pt 8pt;
            width: 100%;
            display: table;
            page-break-after: avoid;
        }

        .sec-badge {
            display: inline-block;
            background: rgba(0,0,0,0.25);
            color: #fff;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1pt 5.5pt;
            border-radius: 2pt;
            letter-spacing: 0.5pt;
            margin-right: 6pt;
            text-transform: uppercase;
        }

        .sec-ttl {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .sec-sub {
            font-size: 6.5pt;
            font-weight: normal;
            font-style: italic;
            text-transform: none;
            letter-spacing: 0;
            color: rgba(255,255,255,0.8);
            margin-left: 4pt;
        }

        .sec-bd {
            border: 0.5pt solid #ccc;
            border-top: none;
            page-break-before: avoid;
        }

        .sec-bd--flush { padding: 0; }

        /* ── Field table ── */
        table.ft {
            width: 100%;
            border-collapse: collapse;
        }

        table.ft td {
            border: 0.5pt solid #e0e0e0;
            padding: 4pt 8pt 5pt;
            vertical-align: top;
        }

        table.ft tr:first-child td { border-top: none; }
        table.ft tr:last-child  td { border-bottom: none; }
        table.ft td:first-child    { border-left: none; }
        table.ft td:last-child     { border-right: none; }

        .fl {
            font-size: 5.5pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-bottom: 3pt;
        }

        .fl-hint {
            font-style: italic;
            color: #bbb;
            text-transform: none;
            letter-spacing: 0;
            font-weight: normal;
        }

        .fv {
            font-size: 7.5pt;
            color: #1a1a1a;
            min-height: 10pt;
        }

        .fv-empty { color: #bbb; }

        /* ── Hint bar ── */
        .hint-bar {
            background: #fdf6f0;
            border-top: 0.5pt solid #e8d0b5;
            border-bottom: 0.5pt solid #e8d0b5;
            padding: 3pt 8pt;
            font-size: 6.5pt;
            color: #8B5A2B;
            font-style: italic;
        }

        /* ── Target chips ── */
        .target-wrap {
            padding: 4pt 8pt 4pt;
            border-bottom: 0.5pt solid #e0e0e0;
        }

        .target-chip {
            display: inline-block;
            background: #fdf0f4;
            border: 0.5pt solid #c08090;
            color: #7B1E3B;
            font-size: 7pt;
            font-weight: bold;
            padding: 2pt 8pt;
            border-radius: 10pt;
            margin-right: 4pt;
            margin-bottom: 2pt;
        }

        /* ── Checkbox symbol ── */
        .cb { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; margin-right: 3pt; }

        /* ── Data tables ── */
        table.dt {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
        }

        table.dt thead tr { page-break-inside: avoid; page-break-after: avoid; }

        table.dt th {
            padding: 4pt 3pt;
            text-align: center;
            font-size: 5.5pt;
            font-weight: bold;
            border: 0.5pt solid #aaa;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            background: #f0f0f0;
            color: #333;
        }

        table.dt th.go   { background: #deeaf6; color: #1a4a7a; }
        table.dt th.back { background: #dff0e0; color: #1a4d2a; }

        table.dt td {
            padding: 3.5pt 3.5pt;
            border: 0.5pt solid #ddd;
            color: #1a1a1a;
            vertical-align: top;
            background: #fff;
            font-size: 7pt;
        }

        table.dt tbody tr:nth-child(even) td { background: #fafafa; }

        table.dt td.tl { text-align: left; }
        table.dt td.tr { text-align: right; white-space: nowrap; }
        table.dt td.tc { text-align: center; }

        table.dt tr.row-total td {
            background: #f5eded;
            font-weight: bold;
            border: 0.5pt solid #aaa;
            border-top: 1.5pt solid #7B1E3B;
            color: #1a1a1a;
        }

        table.dt .th-sub {
            font-size: 5pt;
            font-weight: normal;
            text-transform: none;
            display: block;
            margin-top: 1pt;
            letter-spacing: 0;
        }

        /* ── Signature table ── */
        table.sig {
            width: 100%;
            border-collapse: collapse;
            border: 0.5pt solid #aaa;
        }

        table.sig td {
            border: 0.5pt solid #aaa;
            text-align: center;
            vertical-align: bottom;
            padding: 4pt 6pt;
        }

        .sig-hd {
            background: #f5eded;
            color: #7B1E3B;
            font-size: 6.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 20pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
            border-bottom: 0.5pt solid #c08090 !important;
        }

        .sig-bd {
            height: 65pt;
            vertical-align: bottom;
            background: #fff;
            padding-bottom: 6pt;
        }

        .sig-name { font-size: 7pt; font-weight: bold; color: #1a1a1a; }
        .sig-role { font-size: 6pt; color: #888; font-style: italic; }

        /* ── Section G ── */
        .g-row { padding: 6pt 9pt; display: block; }
        .g-lbl { font-size: 7pt; font-weight: bold; color: #1a1a1a; }

        .g-dots {
            border-bottom: 0.7pt dotted #aaa;
            display: inline-block;
            min-width: 185pt;
            margin-left: 4pt;
            vertical-align: bottom;
        }

        /* ── Misc ── */
        .chip-type {
            display: inline-block;
            background: #fdf0f4;
            border: 0.5pt solid #c08090;
            color: #7B1E3B;
            font-size: 6.5pt;
            padding: 1.5pt 7pt;
            border-radius: 2pt;
        }

        .muted { font-size: 6.5pt; color: #999; font-style: italic; }

        .d1-lbl {
            padding: 3pt 8pt 2pt;
            font-size: 5.5pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            border-bottom: 0.5pt solid #e0e0e0;
        }

        /* ── Page footer ── */
        .pg-footer {
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

        $bgPath  = public_path('docs/background.png');
        $hdrPath = public_path('docs/header.png');
        $bgUri   = ($useBranding && file_exists($bgPath))
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($bgPath))
            : '';
        $hdrUri  = ($useBranding && file_exists($hdrPath))
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($hdrPath))
            : '';

        $reqCode = 'ĐNDV/' . $dispatchRequest->created_at->format('Y') . '/' . str_pad($dispatchRequest->id, 4, '0', STR_PAD_LEFT);
    @endphp

    {{-- ────────── BACKGROUND (watermark + footer) & HEADER (logo + tên trường) ────────── --}}
    @if($bgUri)
        <div class="bg-shell">
            <img class="bg-layer" src="{{ $bgUri }}" alt="" />
            <div class="bg-hdr-mask"></div>
        </div>
    @endif
    @if($hdrUri)
        <img class="hdr-layer" src="{{ $hdrUri }}" alt="" />
    @endif

    <div class="page-content">

    {{-- ────────── DOCUMENT TITLE ────────── --}}
    <table class="top-layout">
        <tr>
            <td style="width:65%; vertical-align:middle; padding-right:10pt;">
                <div class="doc-title">Phiếu Đề Nghị Điều Vận</div>
                <div class="doc-subtitle">
                    @if($isCargo) Điều chuyển Hàng hóa
                    @elseif($isP2P) Vận chuyển Điểm — Điểm
                    @elseif($isBusiness) Công tác
                    @else Đưa đón tận nơi
                    @endif
                </div>
            </td>
            <td style="width:35%; vertical-align:top;">
                <table class="meta-tbl">
                    <tr>
                        <td class="meta-lbl">Ký hiệu</td>
                        <td class="meta-val">BM.03/MH.QT.04</td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">Ngày ban hành</td>
                        <td class="meta-val">29/08/2025</td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">Lần ban hành</td>
                        <td class="meta-val">01</td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">Mã phiếu</td>
                        <td class="meta-val"><span class="meta-code">{{ $reqCode }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="doc-divider" />

    {{-- ────────── A · NGƯỜI ĐỀ NGHỊ ────────── --}}
    <div class="sec sec-avoid">
        <div class="sec-hd">
            <span class="sec-badge">A</span>
            <span class="sec-ttl">Người đề nghị</span>
        </div>
        <div class="sec-bd">
            <table class="ft">
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
                        <div class="fl">a.4 &nbsp;Đơn vị / Bộ phận</div>
                        <div class="fv {{ !$aUnit ? 'fv-empty' : '' }}">{{ $aUnit ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── B · MỤC ĐÍCH ────────── --}}
    <div class="sec sec-avoid">
        <div class="sec-hd">
            <span class="sec-badge">B</span>
            <span class="sec-ttl">Mục đích sử dụng</span>
        </div>
        <div class="sec-bd">
            <table class="ft">
                <tr>
                    <td colspan="2">
                        <div class="fl">b.1 &nbsp;Mục đích sử dụng</div>
                        <div class="fv {{ !$purpose ? 'fv-empty' : '' }}">{{ $purpose ?: '—' }}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="fl">b.2 &nbsp;Căn cứ đề xuất
                            <span class="fl-hint">— Tờ trình số …/ ngày &amp; nội dung</span>
                        </div>
                        <div class="fv {{ !$basisLine ? 'fv-empty' : '' }}">{{ $basisLine ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── C · THỜI GIAN ────────── --}}
    <div class="sec sec-avoid">
        <div class="sec-hd">
            <span class="sec-badge">C</span>
            <span class="sec-ttl">Thời gian</span>
        </div>
        <div class="sec-bd">
            <table class="ft">
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

            <table class="ft" style="border-top: none;">
                <tr>
                    <td style="width:60%; vertical-align:middle; border-top:none; padding:4pt 8pt;">
                        <span class="cb">{!! $cb($isUrgent) !!}</span>
                        <strong style="font-size:7.5pt;">Gấp</strong>
                        @if($isUrgent)
                            &nbsp;<span style="font-size:7pt; color:#c0392b;">Lý do: {{ $urgentReason }}</span>
                        @else
                            &nbsp;<span style="font-size:7pt; color:#bbb;">Lý do: —</span>
                        @endif
                    </td>
                    <td style="width:40%; text-align:right; vertical-align:middle; border-top:none; padding:4pt 8pt;">
                        <span style="font-size:5.5pt; color:#888; text-transform:uppercase; letter-spacing:0.3pt;">Loại yêu cầu:</span>
                        &nbsp;<span class="chip-type">{{ $tripType }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── D · ĐỐI TƯỢNG ────────── --}}
    <div class="sec sec-avoid">
        <div class="sec-hd">
            <span class="sec-badge">D</span>
            <span class="sec-ttl">Khu vực / Đối tượng được phân bổ</span>
        </div>
        <div class="sec-bd">
            <div class="d1-lbl">d.1 &nbsp;Đối tượng sử dụng</div>
            <div class="target-wrap">
                @forelse($checkedTargets as $tg)
                    <span class="target-chip">&#x2714;&nbsp;{{ $tg['label'] }}</span>
                @empty
                    <span style="font-size:7pt; color:#bbb;">—</span>
                @endforelse
            </div>

            <table class="ft" style="border-top: 0.5pt solid #e0e0e0;">
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
                        <div class="fl">Số điện thoại</div>
                        <div class="fv {{ !$coordPhone ? 'fv-empty' : '' }}">{{ $coordPhone ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── E · NỘI DUNG VẬN CHUYỂN ────────── --}}
    <div class="sec">
        <div class="sec-hd">
            <span class="sec-badge">E</span>
            <span class="sec-ttl">Nội dung đề nghị vận chuyển</span>
            @if(!$isCargo && !$isBusiness)
                <span class="sec-sub">
                    @if($isP2P) · E.1 Bảng hành trình (Điểm — Điểm)
                    @else · E.1 Bảng nội dung hành khách
                    @endif
                </span>
            @elseif($isBusiness)
                <span class="sec-sub">· E.2 Bảng nội dung công tác</span>
            @endif
        </div>
        <div class="sec-bd sec-bd--flush">

            {{-- ── CARGO TABLE ── --}}
            @if($isCargo)
                <table class="dt">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:4%;">STT</th>
                            <th colspan="5">Thông tin hàng hóa</th>
                            <th colspan="3" class="go">Điểm tập kết</th>
                            <th colspan="3" class="back">Điểm giao</th>
                            <th rowspan="2" style="width:8%;">
                                Loại hình
                                <span class="th-sub">(NV điều phối)</span>
                            </th>
                            <th rowspan="2" style="width:10%;">
                                Chi phí
                                <span class="th-sub">(Gồm VAT)</span>
                            </th>
                        </tr>
                        <tr>
                            <th style="width:10%;">Tên hàng hóa</th>
                            <th style="width:6%;">SL</th>
                            <th style="width:10%;">Kích thước (DxRxC)</th>
                            <th style="width:8%;">Khối lượng</th>
                            <th style="width:8%;">Ghi chú</th>
                            <th class="go" style="width:7%;">Thời gian</th>
                            <th class="go" style="width:8%;">Địa điểm</th>
                            <th class="go" style="width:8%;">Người giao</th>
                            <th class="back" style="width:7%;">Thời gian</th>
                            <th class="back" style="width:8%;">Địa điểm</th>
                            <th class="back" style="width:8%;">Người nhận</th>
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
                                <td class="tc">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tl">{{ $row['puContact'] }}</td>
                                <td class="tc">{{ $row['delTime'] }}</td>
                                <td class="tl">{{ $row['delPlace'] }}</td>
                                <td class="tl">{{ $row['delContact'] }}</td>
                                <td class="tc">{{ $row['transport'] }}</td>
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

                <table class="ft" style="border-top: 0.5pt solid #ddd;">
                    <tr>
                        <td style="width:72%;">
                            <div class="fl">e.1.1 &nbsp;Các ghi chú khác</div>
                            <div class="fv" style="margin-bottom:3pt;">
                                <span class="cb">{!! $cb($needPorters) !!}</span>
                                Yêu cầu bốc xếp / nhân công hỗ trợ
                                &nbsp;— Số lượng: <strong>{{ $needPorters ? $porterQty : '—' }}</strong>
                                &nbsp;— Chi phí phát sinh: <strong>{{ $needPorters ? $porterCost : '—' }}</strong>
                            </div>
                            <div class="fv">
                                <span class="cb">{!! $cb($interprovincial) !!}</span>
                                Gửi chành xe đi tỉnh
                                &nbsp;— Chi phí phát sinh: <strong>{{ $interprovincial ? $interprovincialCost : '—' }}</strong>
                            </div>
                            @if($cargoExtraNotes)
                                <div class="muted" style="margin-top:4pt;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
                            @endif
                        </td>
                        <td class="muted" style="width:28%; vertical-align:middle; text-align:center;">
                            (Vui lòng liên hệ NV Điều vận<br/>để điền thông tin chi phí)
                        </td>
                    </tr>
                </table>
            @endif

            {{-- ── PASSENGER / P2P TABLE ── --}}
            @if(!$isCargo && !$isBusiness)
                <table class="dt">
                    <thead>
                        <tr>
                            <th style="width:4%;">STT</th>
                            <th style="width:12%;">Diễn giải</th>
                            <th style="width:5%;">SL</th>
                            <th class="go" style="width:9%;">TG đi</th>
                            <th class="go" style="width:12%;">Điểm đón</th>
                            <th class="back" style="width:9%;">TG về</th>
                            <th class="back" style="width:12%;">Điểm trả</th>
                            <th style="width:10%;">Phụ trách</th>
                            <th style="width:10%;">Đơn giá</th>
                            <th style="width:8%;">Phụ thu</th>
                            <th style="width:9%;">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($passengerSectionRows as $idx => $row)
                            <tr>
                                <td class="tc">{{ $idx + 1 }}</td>
                                <td class="tl">{{ $row['name'] }}</td>
                                <td class="tc">{{ $row['qty'] }}</td>
                                <td class="tc">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tc">{{ $row['delTime'] }}</td>
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
                <table class="dt">
                    <thead>
                        <tr>
                            <th style="width:4%;">STT</th>
                            <th style="width:14%;">Diễn giải</th>
                            <th style="width:5%;">SL</th>
                            <th style="width:10%;">Điểm dừng / cung đường</th>
                            <th style="width:8%;">Ghi chú</th>
                            <th class="go" style="width:9%;">TG đi</th>
                            <th class="go" style="width:10%;">Điểm đi</th>
                            <th class="back" style="width:9%;">TG về</th>
                            <th class="back" style="width:10%;">Điểm đến</th>
                            <th style="width:21%;">Khác</th>
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
                                <td class="tc">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tc">{{ $row['delTime'] }}</td>
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
    </div>

    {{-- ────────── F · XÁC NHẬN CÁC BÊN ────────── --}}
    <div class="sec">
        <div class="sec-hd">
            <span class="sec-badge">F</span>
            <span class="sec-ttl">Xác nhận của các bên liên quan</span>
        </div>
        <table class="sig" style="border-top: none;">
            <tr>
                <td class="sig-hd">Trưởng phòng Mua hàng</td>
                <td class="sig-hd">Người đề xuất</td>
                <td class="sig-hd">Trưởng đơn vị đề xuất</td>
            </tr>
            <tr>
                <td class="sig-bd">
                    <span class="sig-name">Phạm Thanh Hùng</span><br />
                </td>
                <td class="sig-bd"></td>
                <td class="sig-bd"></td>
            </tr>
            <tr>
                <td class="sig-hd">Giám đốc Vận hành</td>
                <td class="sig-hd">&nbsp;</td>
                <td class="sig-hd">Phó Tổng Giám đốc</td>
            </tr>
            <tr>
                <td class="sig-bd">
                    <span class="sig-name">Vũ Quốc Vương</span>
                </td>
                <td class="sig-bd"></td>
                <td class="sig-bd"></td>
            </tr>
        </table>
    </div>

    {{-- ────────── G · PHÒNG MUA HÀNG ────────── --}}
    <div class="sec sec-avoid">
        <div class="sec-hd">
            <span class="sec-badge">G</span>
            <span class="sec-ttl">Xác nhận của phòng Mua Hàng</span>
        </div>
        <div class="sec-bd">
            <div class="g-row">
                <div style="margin-bottom:6pt;">
                    <span class="g-lbl">g.1 &nbsp;Mã vận đơn (PO):</span>
                    <span class="g-dots">{{ $poCode }}</span>
                </div>
                <div>
                    <span class="g-lbl">g.2 &nbsp;Ngày nhận đề nghị (đã được phê duyệt):</span>
                    <span class="g-dots">{{ $g2Date }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="pg-footer">
        BM.03/MH.QT.04 &nbsp;·&nbsp; Phiếu Đề Nghị Điều Vận &nbsp;·&nbsp; Hệ Thống Trường Việt Mỹ
    </div>

    </div>{{-- /.page-content --}}

</body>

</html>
