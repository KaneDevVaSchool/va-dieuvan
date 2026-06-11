<!DOCTYPE html>
<html lang="vi">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @php
        // header.png 1999×393 px → ~41.3mm khi scale theo chiều ngang A4 (210mm)
        $hdrBandMm = 41.3;
        $hdrTrimBottomMm = 2.8;
        $hdrVisibleMm = $hdrBandMm - $hdrTrimBottomMm;
        $hdrContentGapMm = 0;
        $pageOrient = $isCargo ? 'landscape' : 'portrait';
        $pageMarginTop = $isCargo ? '12mm' : ($hdrVisibleMm + $hdrContentGapMm) . 'mm';
        $pageMarginX   = '5mm';
        $pageMarginBot = $isCargo ? '12mm' : '14mm';
        $bgW = $isCargo ? '297mm' : '210mm';
        $bgH = $isCargo ? '210mm' : '297mm';
        $useBranding = ! $isCargo;
    @endphp
    @php
        // Fonts pre-installed under storage/fonts (see installed-fonts.json). Do not use @font-face:
        // Dompdf re-registers fonts on each request and writes installed-fonts.json (needs writable storage).
        $pdfFontFamily = 'garbatatrial, sans-serif';
        $pdfTabularFont = 'dejavu sans, sans-serif';
        $pdfTextColor = '#000';
    @endphp
    <style>
        @page {
            margin: {{ $pageMarginTop }} {{ $pageMarginX }} {{ $pageMarginBot }} {{ $pageMarginX }};
            size: A4 {{ $pageOrient }};
        }

        * { box-sizing: border-box; font-family: {!! $pdfFontFamily !!}; }

        body {
            font-size: 8.5pt;
            color: {{ $pdfTextColor }};
            margin: 0;
            line-height: 1.55;
        }

        strong, b { font-weight: normal; }

        /* Garbata render số/ngày kém trong DomPDF — các vùng tabular dùng DejaVu */
        .fl, .fl-hint, .fv, .fv-phone,
        .d1-lbl, .g-lbl, .g-row, .g-dots,
        table.ft, table.ft td,
        table.dt, table.dt th, table.dt td,
        .hint-bar, .target-chip, .chip-type, .muted,
        .dt-amount-words, .dt-amount-words-lbl, .dt-amount-words-val,
        .cb, .sec-badge, .sec-sub,
        table.sig, .sig-hd, .sig-bd, .sig-name,
        .pg-footer,
        table.ft strong, .hint-bar strong, .fv strong {
            font-family: {!! $pdfTabularFont !!};
            letter-spacing: 0;
        }

        /* ── Fixed branding layers ──
           DomPDF positions fixed elements from the content area origin,
           so we use negative offsets to reach the physical page edges.  ── */
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
            top: 0; left: 0;
            width: {{ $bgW }};
            height: {{ $bgH }};
            margin: 0; padding: 0; border: none; display: block;
        }

        .bg-hdr-mask {
            position: absolute;
            top: 0; left: 0;
            width: {{ $bgW }};
            height: {{ $hdrVisibleMm }}mm;
            background: #fff;
        }

        .hdr-shell {
            position: fixed;
            top: -{{ $pageMarginTop }};
            left: -{{ $pageMarginX }};
            width: {{ $bgW }};
            height: {{ $hdrVisibleMm }}mm;
            overflow: hidden;
            z-index: 2;
        }

        .hdr-layer {
            display: block;
            width: {{ $bgW }};
            height: {{ $hdrBandMm }}mm;
            margin: 0;
            padding: 0;
            border: none;
        }

        .page-content {
            position: relative;
            width: 100%;
            color: {{ $pdfTextColor }};
            margin-top: -3pt;
        }

        /* E + F + G always on the same page */
        .signatures-page {
            page-break-before: always;
            page-break-inside: avoid;
        }

        .signatures-page > .sec:first-child { margin-top: 0; }

        /* ── TITLE BLOCK ── */
        .doc-head {
            width: 100%;
            margin-bottom: 2pt;
        }

        .doc-heading {
            width: 100%;
            text-align: center;
            padding: 0;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5pt;
            text-align: center;
            color: {{ $pdfTextColor }};
            line-height: 1.4;
        }

        .doc-subtitle {
            font-size: 8.5pt;
            text-align: center;
            color: {{ $pdfTextColor }};
            font-style: italic;
            margin-top: 3pt;
            line-height: 1.5;
        }

        /* ── SECTION HEADERS (A–G) ── */
        .sec       { margin-top: 5pt; }
        .sec-avoid { page-break-inside: avoid; }

        .sec-hd {
            text-align: left;
            padding: 5pt 8pt 4.5pt 6pt;
            background: #f2f2f2;
            page-break-after: avoid;
            line-height: 1.55;
        }

        .sec-badge {
            display: inline-block;
            min-width: 13pt;
            text-align: center;
            border: 0.5pt solid #444;
            background: #fff;
            color: {{ $pdfTextColor }};
            font-size: 8pt;
            padding: 1.5pt 4pt;
            margin-right: 7pt;
            vertical-align: middle;
            letter-spacing: 0;
        }

        .sec-ttl {
            display: inline;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.35pt;
            color: {{ $pdfTextColor }};
            vertical-align: middle;
        }

        .sec-sub {
            display: inline;
            font-size: 7.5pt;
            font-style: italic;
            letter-spacing: 0;
            text-transform: none;
            color: {{ $pdfTextColor }};
            margin-left: 6pt;
            vertical-align: middle;
        }

        .sec-bd { page-break-before: avoid; }
        .sec-bd--flush { padding: 0; }

        .sec--bordered .sec-hd {
            border-top: 0.75pt solid #444;
            border-bottom: 0.5pt solid #bbb;
        }

        .sec--bordered .sec-bd {
            border-bottom: 0.5pt solid #ccc;
        }

        .sec--bordered table.ft td {
            border-right: 0.5pt solid #ddd;
            border-bottom: 0.5pt solid #ddd;
        }

        .sec--bordered table.ft tr:first-child td { border-top: 0.5pt solid #ddd; }
        .sec--bordered table.ft td:first-child    { border-left: 0.5pt solid #ddd; }

        .sec--bordered .d1-lbl {
            border-bottom: 0.5pt solid #ddd;
        }

        .sec--bordered .target-wrap {
            border-bottom: 0.5pt solid #ddd;
        }

        /* ── FIELD TABLE ── */
        table.ft { width: 100%; border-collapse: collapse; }

        table.ft td {
            border: none;
            padding: 4pt 6pt 5pt;
            vertical-align: top;
        }

        table.ft tr:first-child td,
        table.ft tr:last-child td,
        table.ft td:first-child,
        table.ft td:last-child { border: none; }

        .fl {
            font-size: 6.5pt;
            color: {{ $pdfTextColor }};
            text-transform: uppercase;
            margin-bottom: 2.5pt;
            line-height: 1.45;
        }

        .fl-hint { font-style: italic; color: {{ $pdfTextColor }}; text-transform: none; letter-spacing: 0; }

        .fv {
            font-size: 9pt;
            color: {{ $pdfTextColor }};
            min-height: 14pt;
            line-height: 1.55;
            border-bottom: 0.6pt dotted #bbb;
            padding-bottom: 2pt;
        }

        .fv-phone {
            white-space: nowrap;
            word-wrap: normal;
        }

        /* ── HINT BAR ── */
        .hint-bar {
            border-top: 0.5pt solid #ddd;
            border-bottom: 0.5pt solid #ddd;
            padding: 4pt 6pt;
            font-size: 7.5pt;
            line-height: 1.5;
            color: {{ $pdfTextColor }};
            font-style: italic;
        }

        /* ── SECTION C · Thời gian ── */
        .sec-c-dates td.sec-c-date {
            width: 50%;
            padding: 7pt 8pt 8pt;
            vertical-align: top;
        }

        .sec-c-dates td.sec-c-date + td.sec-c-date {
            border-left: 0.75pt solid #bbb;
        }

        .fv-date {
            font-size: 10pt;
            font-weight: bold;
            white-space: nowrap;
            border-bottom: 0.75pt solid #888;
            padding-bottom: 3pt;
            min-height: 16pt;
            line-height: 1.5;
        }

        .hint-bar--policy {
            background: #f7f7f7;
            border-top: 0.75pt solid #bbb;
            border-bottom: 0.75pt solid #bbb;
            border-left: 2.5pt solid #444;
            padding: 5pt 8pt 5pt 7pt;
            font-style: normal;
        }

        .hint-bar--policy strong {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.2pt;
        }

        .sec-c-meta td {
            vertical-align: middle;
            padding: 6pt 8pt;
            border-top: none;
        }

        .sec-c-urgent {
            width: 58%;
            border-right: 0.75pt solid #bbb;
        }

        .sec-c-type {
            width: 42%;
            text-align: center;
        }

        .sec-c-urgent-row {
            line-height: 1.55;
        }

        .sec-c-urgent-lbl {
            font-size: 8.5pt;
            font-weight: bold;
        }

        .sec-c-reason {
            display: block;
            margin-top: 3pt;
            padding-left: 16pt;
            font-size: 8pt;
            line-height: 1.5;
        }

        .sec-c-reason--urgent { color: #c0392b; }

        .sec-c-type-lbl {
            display: block;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.35pt;
            margin-bottom: 4pt;
            line-height: 1.45;
        }

        .chip-type--trip {
            display: inline-block;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 2.5pt 12pt;
            border: 0.75pt solid #444;
            background: #fff;
            letter-spacing: 0.15pt;
        }

        /* ── TARGET CHIPS ── */
        .target-wrap { padding: 4pt 6pt 3pt; }

        .target-chip {
            display: inline-block;
            border: 0.5pt solid #999;
            color: {{ $pdfTextColor }};
            font-size: 7.5pt;
            line-height: 1.45;
            padding: 1.5pt 7pt;
            border-radius: 10pt;
            margin-right: 4pt;
            margin-bottom: 2pt;
        }

        /* ── CHECKBOX ── */
        .cb { font-size: 9pt; margin-right: 3pt; }

        /* ── DATA TABLES ── */
        table.dt { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
        table.dt thead tr { page-break-inside: avoid; page-break-after: avoid; }

        table.dt th {
            padding: 4.5pt 3pt;
            text-align: center;
            font-size: 6.5pt;
            font-weight: bold;
            border: 0.5pt solid #bbb;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            background: #f4f4f4;
            color: {{ $pdfTextColor }};
            line-height: 1.5;
        }

        table.dt td {
            padding: 4.5pt 3pt;
            border: 0.5pt solid #e0e0e0;
            color: {{ $pdfTextColor }};
            vertical-align: top;
            font-size: 8pt;
            line-height: 1.55;
        }

        table.dt tbody tr:nth-child(even) td { background: #fafafa; }
        table.dt td.tl { text-align: left; }
        table.dt td.tr { text-align: right; white-space: nowrap; }
        table.dt td.tc { text-align: center; }
        table.dt td.dt-time { white-space: nowrap; font-size: 7.5pt; }

        table.dt tr.row-total td {
            font-weight: bold;
            background: #f5f5f5;
            font-size: 8.5pt;
            padding-top: 5pt;
            padding-bottom: 5pt;
            border: 0.5pt solid #bbb;
            border-top: 1pt solid #555;
            color: {{ $pdfTextColor }};
        }

        table.dt .th-sub {
            font-size: 6pt;
            line-height: 1.4;
            text-transform: none;
            display: block;
            margin-top: 1pt;
            letter-spacing: 0;
            font-weight: normal;
        }

        table.dt thead tr.dt-group-hd th {
            padding: 5pt 3pt 4pt;
            font-size: 7pt;
            border-bottom: 0.75pt solid #888;
        }

        .dt-amount-words {
            border-top: 0.75pt solid #bbb;
            border-bottom: 0.5pt solid #ccc;
            padding: 5pt 8pt 6pt;
            line-height: 1.55;
        }

        .dt-amount-words-lbl {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-right: 8pt;
            vertical-align: top;
        }

        .dt-amount-words-val {
            display: inline;
            font-size: 8.5pt;
            font-style: italic;
        }

        .e-notes-wrap {
            border-top: 0.75pt solid #bbb;
        }

        .e-notes-main { padding: 5pt 6pt 6pt; vertical-align: top; }

        .e-notes-aside {
            width: 28%;
            vertical-align: middle;
            text-align: center;
            padding: 8pt 6pt;
            border-left: 0.75pt solid #bbb;
            font-size: 7.5pt;
            line-height: 1.55;
            font-style: italic;
        }

        /* ── SIGNATURE BLOCK ── */
        table.sig { width: 100%; border-collapse: collapse; border: 0.5pt solid #bbb; }

        table.sig td { border: 0.5pt solid #bbb; text-align: center; padding: 0; }

        .sig-hd {
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.5;
            text-align: center;
            vertical-align: middle;
            padding: 4pt 4pt;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: {{ $pdfTextColor }};
            border-bottom: 0.5pt solid #ccc !important;
        }

        .sig-bd {
            height: 85pt;
            min-height: 85pt;
            vertical-align: bottom;
            padding: 8pt 6pt 8pt;
            text-align: center;
        }

        .sig-name { font-size: 8pt; line-height: 1.5; color: {{ $pdfTextColor }}; margin-top: 4pt; display: block; }

        /* ── SECTION G ── */
        .g-row { padding: 6pt 6pt; display: block; line-height: 1.55; }
        .g-lbl { font-size: 8pt; color: {{ $pdfTextColor }}; }

        .g-dots {
            border-bottom: 0.6pt dotted #bbb;
            display: inline-block;
            min-width: 180pt;
            margin-left: 4pt;
            vertical-align: bottom;
        }

        /* ── MISC ── */
        .chip-type {
            display: inline-block;
            border: 0.5pt solid #999;
            color: {{ $pdfTextColor }};
            font-size: 7.5pt;
            line-height: 1.45;
            padding: 1pt 6pt;
            border-radius: 2pt;
        }

        .muted { font-size: 7.5pt; line-height: 1.5; color: {{ $pdfTextColor }}; font-style: italic; }

        .d1-lbl {
            padding: 3pt 6pt 2.5pt;
            font-size: 6.5pt;
            line-height: 1.45;
            color: {{ $pdfTextColor }};
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        /* ── PAGE FOOTER ── */
        .pg-footer {
            text-align: center;
            font-size: 7pt;
            line-height: 1.5;
            color: {{ $pdfTextColor }};
            margin-top: 8pt;
            padding-top: 3pt;
            letter-spacing: 0.4pt;
        }
    </style>
</head>

<body>
    @php
        use App\Services\DispatchRequests\DispatchRequestPdfPresenter;

        $cb = static function (bool $checked): string {
            return $checked
                ? '<span style="font-family:DejaVu Sans,sans-serif;font-size:9pt;color:{{ $pdfTextColor }};">&#x2611;</span>'
                : '<span style="font-family:DejaVu Sans,sans-serif;font-size:9pt;color:{{ $pdfTextColor }};">&#x2610;</span>';
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
    @endphp

    {{-- ────────── BACKGROUND (watermark + footer) & HEADER (logo + tên trường) ────────── --}}
    @if($bgUri)
        <div class="bg-shell">
            <img class="bg-layer" src="{{ $bgUri }}" alt="" />
            <div class="bg-hdr-mask"></div>
        </div>
    @endif
    @if($hdrUri)
        <div class="hdr-shell">
            <img class="hdr-layer" src="{{ $hdrUri }}" alt="" />
        </div>
    @endif

    <div class="page-content">

    {{-- ────────── DOCUMENT TITLE ────────── --}}
    <div class="doc-head">
        <div class="doc-heading">
            <div class="doc-title">Phiếu Đề Nghị Điều Vận</div>
            <div class="doc-subtitle">
                @if($isCargo) Điều chuyển Hàng hóa
                @elseif($isP2P) Vận chuyển Điểm — Điểm
                @elseif($isBusiness) Công tác
                @else Đưa đón tận nơi
                @endif
            </div>
        </div>
    </div>

    {{-- ────────── A · NGƯỜI ĐỀ NGHỊ ────────── --}}
    <div class="sec sec-avoid sec--bordered">
        <div class="sec-hd">
            <span class="sec-badge">A</span>
            <span class="sec-ttl">Người đề nghị</span>
        </div>
        <div class="sec-bd">
            <table class="ft">
                <tr>
                    <td style="width:50%;">
                        <div class="fl">a.1 &nbsp;Họ và tên</div>
                        <div class="fv">{{ $aName }}</div>
                    </td>
                    <td style="width:50%;">
                        <div class="fl">a.2 &nbsp;Email VA của nhân viên</div>
                        <div class="fv">{{ $aEmail }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fl">a.3 &nbsp;Số điện thoại</div>
                        <div class="fv fv-phone">{{ $aPhone }}</div>
                    </td>
                    <td>
                        <div class="fl">a.4 &nbsp;Đơn vị / Bộ phận</div>
                        <div class="fv">{{ $aUnit }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── B · MỤC ĐÍCH ────────── --}}
    <div class="sec sec-avoid sec--bordered">
        <div class="sec-hd">
            <span class="sec-badge">B</span>
            <span class="sec-ttl">Mục đích sử dụng</span>
        </div>
        <div class="sec-bd">
            <table class="ft">
                <tr>
                    <td colspan="2">
                        <div class="fl">b.1 &nbsp;Mục đích sử dụng</div>
                        <div class="fv">{{ $purpose }}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="fl">b.2 &nbsp;Căn cứ đề xuất
                            <span class="fl-hint">— Tờ trình số …/ ngày &amp; nội dung</span>
                        </div>
                        <div class="fv">{{ $basisLine }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── C · THỜI GIAN ────────── --}}
    <div class="sec sec-avoid sec--bordered">
        <div class="sec-hd">
            <span class="sec-badge">C</span>
            <span class="sec-ttl">Thời gian</span>
        </div>
        <div class="sec-bd">
            <table class="ft sec-c-dates">
                <tr>
                    <td class="sec-c-date">
                        <div class="fl">c.1 &nbsp;Ngày đề xuất</div>
                        <div class="fv fv-date">{{ $proposedDate }}</div>
                    </td>
                    <td class="sec-c-date">
                        <div class="fl">c.2 &nbsp;Ngày cần sử dụng xe</div>
                        <div class="fv fv-date">{{ $dateNeeded }}</div>
                    </td>
                </tr>
            </table>

            <div class="hint-bar hint-bar--policy">
                <strong>Lưu ý</strong> &nbsp;·&nbsp;
                Tối thiểu 03 ngày làm việc trước ngày cấp xe;
                từ 2.000 kg trở lên cần báo sớm ít nhất 05 ngày làm việc.
            </div>

            <table class="ft sec-c-meta">
                <tr>
                    <td class="sec-c-urgent">
                        <div class="sec-c-urgent-row">
                            <span class="cb">{!! $cb($isUrgent) !!}</span>
                            <span class="sec-c-urgent-lbl">Gấp</span>
                        </div>
                        @if($isUrgent)
                            <span class="sec-c-reason sec-c-reason--urgent">Lý do: {{ $urgentReason }}</span>
                        @else
                            <span class="sec-c-reason">Lý do: —</span>
                        @endif
                    </td>
                    <td class="sec-c-type">
                        <span class="sec-c-type-lbl">Loại yêu cầu</span>
                        <span class="chip-type chip-type--trip">{{ $tripType }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ────────── D · ĐỐI TƯỢNG ────────── --}}
    <div class="sec sec-avoid sec--bordered">
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
                    <span style="font-size:8pt; color:{{ $pdfTextColor }};">—</span>
                @endforelse
            </div>

            <table class="ft">
                <tr>
                    <td style="width:35%;">
                        <div class="fl">d.2 &nbsp;Nhân sự phụ trách — Họ tên</div>
                        <div class="fv">{{ $coordName }}</div>
                    </td>
                    <td style="width:42%;">
                        <div class="fl">Email</div>
                        <div class="fv">{{ $coordEmail }}</div>
                    </td>
                    <td style="width:23%;">
                        <div class="fl">Số điện thoại</div>
                        <div class="fv fv-phone">{{ $coordPhone }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="signatures-page">

    {{-- ────────── E · NỘI DUNG VẬN CHUYỂN ────────── --}}
    <div class="sec sec-avoid">
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
                        <tr class="dt-group-hd">
                            <th rowspan="2" style="width:4%;">STT</th>
                            <th colspan="5">Thông tin hàng hóa</th>
                            <th colspan="3">Điểm tập kết</th>
                            <th colspan="3">Điểm giao</th>
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
                            <th style="width:7%;">Thời gian</th>
                            <th style="width:8%;">Địa điểm</th>
                            <th style="width:8%;">Người giao</th>
                            <th style="width:7%;">Thời gian</th>
                            <th style="width:8%;">Địa điểm</th>
                            <th style="width:8%;">Người nhận</th>
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
                                <td class="tc dt-time">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tl">{{ $row['puContact'] }}</td>
                                <td class="tc dt-time">{{ $row['delTime'] }}</td>
                                <td class="tl">{{ $row['delPlace'] }}</td>
                                <td class="tl">{{ $row['delContact'] }}</td>
                                <td class="tc">{{ $row['transport'] }}</td>
                                <td class="tr">{{ $row['cost'] }}</td>
                            </tr>
                        @endforeach
                        <tr class="row-total">
                            <td colspan="13" class="tr" style="padding-right:8pt;">Tổng:</td>
                            <td class="tr">{{ $grandTotalFmt }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="dt-amount-words">
                    <span class="dt-amount-words-lbl">Bằng chữ</span>
                    <span class="dt-amount-words-val">{{ $grandTotalWords }}</span>
                </div>

                <table class="ft e-notes-wrap">
                    <tr>
                        <td class="e-notes-main" style="width:72%;">
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
                        <td class="e-notes-aside">
                            (Vui lòng liên hệ NV Điều vận<br/>để điền thông tin chi phí)
                        </td>
                    </tr>
                </table>
            @endif

            {{-- ── PASSENGER / P2P TABLE ── --}}
            @if(!$isCargo && !$isBusiness)
                <table class="dt">
                    <thead>
                        <tr class="dt-group-hd">
                            <th rowspan="2" style="width:4%;">STT</th>
                            <th colspan="2">Nội dung</th>
                            <th colspan="2">Chiều đi</th>
                            <th colspan="2">Chiều về</th>
                            <th colspan="4">Chi phí &amp; ghi chú</th>
                        </tr>
                        <tr>
                            <th style="width:12%;">Diễn giải</th>
                            <th style="width:5%;">SL</th>
                            <th style="width:9%;">TG đi</th>
                            <th style="width:12%;">Điểm đón</th>
                            <th style="width:9%;">TG về</th>
                            <th style="width:12%;">Điểm trả</th>
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
                                <td class="tc dt-time">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tc dt-time">{{ $row['delTime'] }}</td>
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

                <div class="dt-amount-words">
                    <span class="dt-amount-words-lbl">Bằng chữ</span>
                    <span class="dt-amount-words-val">{{ $grandTotalWords }}</span>
                </div>
            @endif

            {{-- ── BUSINESS TABLE ── --}}
            @if($isBusiness)
                <table class="dt">
                    <thead>
                        <tr class="dt-group-hd">
                            <th rowspan="2" style="width:4%;">STT</th>
                            <th colspan="4">Nội dung công tác</th>
                            <th colspan="2">Chiều đi</th>
                            <th colspan="2">Chiều về</th>
                            <th rowspan="2" style="width:21%;">Khác</th>
                        </tr>
                        <tr>
                            <th style="width:14%;">Diễn giải</th>
                            <th style="width:5%;">SL</th>
                            <th style="width:10%;">Điểm dừng / cung đường</th>
                            <th style="width:8%;">Ghi chú</th>
                            <th style="width:9%;">TG đi</th>
                            <th style="width:10%;">Điểm đi</th>
                            <th style="width:9%;">TG về</th>
                            <th style="width:10%;">Điểm đến</th>
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
                                <td class="tc dt-time">{{ $row['puTime'] }}</td>
                                <td class="tl">{{ $row['puPlace'] }}</td>
                                <td class="tc dt-time">{{ $row['delTime'] }}</td>
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

                <div class="dt-amount-words">
                    <span class="dt-amount-words-lbl">Bằng chữ</span>
                    <span class="dt-amount-words-val">{{ $grandTotalWords }}</span>
                </div>
            @endif

        </div>
    </div>

    {{-- ────────── F · XÁC NHẬN CÁC BÊN ────────── --}}
    <div class="sec sec-avoid">
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
                    <span style="font-size:7pt; color:{{ $pdfTextColor }}; font-style:italic; display:block; margin-bottom:2pt;">(Ký, ghi rõ họ tên)</span>
                    <span class="sig-name">Phạm Thanh Hùng</span>
                </td>
                <td class="sig-bd"><span style="font-size:7pt; color:{{ $pdfTextColor }}; font-style:italic;">(Ký, ghi rõ họ tên)</span></td>
                <td class="sig-bd"><span style="font-size:7pt; color:{{ $pdfTextColor }}; font-style:italic;">(Ký, ghi rõ họ tên)</span></td>
            </tr>
            <tr>
                <td class="sig-hd">Giám đốc Vận hành</td>
                <td class="sig-hd">&nbsp;</td>
                <td class="sig-hd">Phó Tổng Giám đốc</td>
            </tr>
            <tr>
                <td class="sig-bd">
                    <span style="font-size:7pt; color:{{ $pdfTextColor }}; font-style:italic; display:block; margin-bottom:2pt;">(Ký, ghi rõ họ tên)</span>
                    <span class="sig-name">Vũ Quốc Vương</span>
                </td>
                <td class="sig-bd"></td>
                <td class="sig-bd">
                    <span style="font-size:7pt; color:{{ $pdfTextColor }}; font-style:italic; display:block; margin-bottom:2pt;">(Ký, ghi rõ họ tên)</span>
                    <span class="sig-name">Nguyễn Ngọc Hiển</span>
                </td>
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

    </div>{{-- /.signatures-page --}}

    </div>{{-- /.page-content --}}

</body>

</html>
