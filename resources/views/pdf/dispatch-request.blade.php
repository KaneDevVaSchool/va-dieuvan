<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 18px 22px; size: A4 portrait; }

        * { box-sizing: border-box; font-family: 'DejaVu Sans', sans-serif; }

        body { font-size: 10pt; color: #1a1a1a; margin: 0; line-height: 1.4; }

        /* ── DOC HEADER ─────────────────────────────────── */
        .doc-header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .doc-header-table td { border: none; vertical-align: middle; }
        .doc-title-main { font-size: 15pt; font-weight: bold; text-align: center; letter-spacing: 0.5px; }
        .doc-title-sub  { font-size: 9pt;  text-align: center; color: #555; font-style: italic; margin-top: 2px; }
        .doc-header-divider { border: none; border-bottom: 2pt solid #8B1A1A; margin-bottom: 10px; }
        .doc-meta-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        .doc-meta-table td { padding: 1.5px 0; border: none; }
        .doc-meta-label { color: #888; width: 70px; }
        .doc-meta-value { font-weight: bold; text-align: right; }
        .doc-meta-id    { color: #8B1A1A; }

        /* ── SECTION HEADER ──────────────────────────────── */
        .sec-header {
            display: table;
            width: 100%;
            border-left: 3pt solid #8B1A1A;
            background: #f7f3f0;
            padding: 4pt 8pt;
            margin-top: 7pt;
            margin-bottom: 0;
        }
        .sec-badge {
            display: inline-block;
            background: #8B1A1A;
            color: #fff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1pt 5pt;
            border-radius: 2pt;
            letter-spacing: 0.5px;
            margin-right: 5pt;
        }
        .sec-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1a1a1a;
            letter-spacing: 0.3px;
        }
        .sec-subtitle {
            font-size: 8pt;
            color: #8B1A1A;
            font-style: italic;
            font-weight: normal;
        }

        /* ── SEC BODY WRAPPER ────────────────────────────── */
        .sec-body {
            border: 0.5pt solid #e0e0e0;
            border-top: none;
        }

        /* ── FIELD ROW ───────────────────────────────────── */
        table.field-table { width: 100%; border-collapse: collapse; }
        table.field-table td {
            border-right: 0.5pt solid #ebebeb;
            border-bottom: 0.5pt solid #ebebeb;
            padding: 4pt 7pt;
            vertical-align: top;
        }
        table.field-table tr:last-child td { border-bottom: none; }
        table.field-table td:last-child    { border-right: none; }
        .fl { font-size: 7.5pt; color: #777; font-weight: bold;
              text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2pt; }
        .fv { font-size: 9.5pt; color: #1a1a1a; min-height: 12pt; }
        .fv-empty { color: #ccc; font-style: italic; }

        /* ── HINT BAR ────────────────────────────────────── */
        .hint-bar {
            background: #fffbf3;
            border-left: 2.5pt solid #e6a817;
            padding: 3pt 8pt;
            font-size: 8pt;
            color: #7a5200;
            font-style: italic;
            border-bottom: 0.5pt solid #ebebeb;
        }

        .p2p-note-bar {
            background: #fffbf3;
            border-left: 2.5pt solid #e6a817;
            padding: 5pt 8pt;
            font-size: 8.5pt;
            color: #7a5200;
            font-style: italic;
            border-bottom: 0.5pt solid #ebebeb;
        }

        /* ── CHECKBOX GRID ───────────────────────────────── */
        table.cb-table { width: 100%; border-collapse: collapse; }
        table.cb-table td {
            width: 25%;
            padding: 2.5pt 7pt;
            border-right: 0.5pt solid #ebebeb;
            border-bottom: 0.5pt solid #ebebeb;
            font-size: 8.5pt;
            color: #1a1a1a;
            vertical-align: middle;
        }
        table.cb-table tr:last-child td { border-bottom: none; }
        table.cb-table td:last-child    { border-right: none; }
        .cb-sym { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt;
                  margin-right: 3pt; color: #8B1A1A; }

        /* ── DATA TABLE ──────────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.data th {
            padding: 4pt 5pt;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            border-right: 0.5pt solid #ddd;
            border-bottom: 1pt solid #ccc;
        }
        table.data th:last-child { border-right: none; }
        table.data th.col-go   { background: #eef4ff; color: #1a3a7a; }
        table.data th.col-back { background: #f0faf0; color: #1a4a1a; }
        table.data th.col-info { background: #f9f9f9; color: #333; }
        table.data td {
            padding: 4pt 5pt;
            text-align: center;
            border-right: 0.5pt solid #ebebeb;
            border-bottom: 0.5pt solid #ebebeb;
            color: #1a1a1a;
            min-height: 18pt;
        }
        table.data td:last-child { border-right: none; }
        table.data td.tl { text-align: left; }
        table.data td.tr { text-align: right; }
        table.data td.tc { text-align: center; }
        table.data tr:last-child td { border-bottom: none; }
        table.data tr.row-total td {
            background: #f7f3f0;
            font-weight: bold;
            border-top: 1pt solid #ccc;
        }

        /* ── SIGNATURE TABLE ─────────────────────────────── */
        table.sig-table { width: 100%; border-collapse: collapse; }
        table.sig-table td {
            width: 33.3%;
            border: 0.5pt solid #e0e0e0;
            text-align: center;
            vertical-align: bottom;
            padding: 4pt 6pt;
        }
        .sig-col-header {
            background: #f7f3f0;
            font-size: 8.5pt;
            font-weight: bold;
            color: #1a1a1a;
            padding: 4pt 6pt;
            vertical-align: middle;
            height: 18pt;
        }
        .sig-col-body { height: 52pt; vertical-align: bottom; }
        .sig-name { font-size: 8.5pt; font-weight: bold; color: #1a1a1a; }
        .sig-role { font-size: 7.5pt; color: #666; font-style: italic; }

        /* ── SECTION G ───────────────────────────────────── */
        .g-row { padding: 5pt 9pt; display: block; }
        .g-label { font-size: 8.5pt; font-weight: bold; color: #333; }
        .g-dots { border-bottom: 0.5pt dotted #aaa; display: inline-block;
                  min-width: 200pt; margin-left: 4pt; }

        /* ── FOOTER ──────────────────────────────────────── */
        .page-footer {
            text-align: center;
            font-size: 7pt;
            color: #bbb;
            margin-top: 8pt;
            padding-top: 5pt;
            border-top: 0.5pt solid #eee;
        }

        .sec-body--flush { padding: 0; }
        .muted-note { font-size: 8pt; color: #666; font-style: italic; }
    </style>
</head>
<body>
@php
    use App\Services\DispatchRequests\DispatchRequestPdfPresenter;

    /** @var callable(bool): string $cb Same output as checkbox helper; closure avoids redeclare on repeat renders. */
    $cb = static function (bool $checked): string {
        return $checked
            ? '<span style="font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#8B1A1A;">&#x2611;</span>'
            : '<span style="font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#666;">&#x2610;</span>';
    };

    $nTargets = count($targetGrid);
    $nRowsTargets = (int) ceil($nTargets / 4);
@endphp

{{-- Đường kẻ đỏ phân cách header --}}
<table class="doc-header-table">
    <tr>
        <td style="width:18%; vertical-align:middle;">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" style="width:80px; height:auto;" alt="VAS"/>
            @endif
        </td>
        <td style="width:64%; vertical-align:middle;">
            <div class="doc-title-main">ĐỀ NGHỊ ĐIỀU VẬN</div>
            <div class="doc-title-sub">
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
        <td style="width:18%; vertical-align:top;">
            <table class="doc-meta-table">
                <tr>
                    <td class="doc-meta-label">Kí hiệu:</td>
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
<hr class="doc-header-divider"/>

{{-- A --}}
<div class="sec-header">
    <span class="sec-badge">A</span>
    <span class="sec-title">Người đề nghị</span>
</div>
<div class="sec-body">
    <table class="field-table">
        <tr>
            <td style="width:50%;">
                <div class="fl">a.1 Họ và tên</div>
                <div class="fv">{{ $aName ?: '—' }}</div>
            </td>
            <td style="width:50%;">
                <div class="fl">a.2 Email VA của nhân viên</div>
                <div class="fv">{{ $aEmail ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="fl">a.3 Số điện thoại</div>
                <div class="fv">{{ $aPhone ?: '—' }}</div>
            </td>
            <td>
                <div class="fl">a.4 Đơn vị</div>
                <div class="fv {{ !$aUnit ? 'fv-empty' : '' }}">{{ $aUnit ?: '—' }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- B --}}
<div class="sec-header">
    <span class="sec-badge">B</span>
    <span class="sec-title">Mục đích sử dụng</span>
</div>
<div class="sec-body">
    <table class="field-table">
        <tr>
            <td colspan="2">
                <div class="fl">b.1 Mục đích sử dụng</div>
                <div class="fv {{ !$purpose ? 'fv-empty' : '' }}">{{ $purpose ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="fl">b.2 Căn cứ đề xuất — <span style="font-weight:normal; text-transform:none; letter-spacing:0; color:#888;">Tờ trình số …/ ngày &amp; nội dung</span></div>
                <div class="fv {{ !$basisLine ? 'fv-empty' : '' }}">{{ $basisLine ?: '—' }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- C --}}
<div class="sec-header">
    <span class="sec-badge">C</span>
    <span class="sec-title">Thời gian</span>
</div>
<div class="sec-body">
    <table class="field-table">
        <tr>
            <td style="width:50%;">
                <div class="fl">c.1 Ngày đề xuất</div>
                <div class="fv">{{ $proposedDate ?: '—' }}</div>
            </td>
            <td style="width:50%;">
                <div class="fl">c.2 Ngày cần sử dụng xe</div>
                <div class="fv">{{ $dateNeeded ?: '—' }}</div>
            </td>
        </tr>
    </table>
    <div class="hint-bar">
        Lưu ý: Tối thiểu 03 ngày làm việc trước ngày cấp xe;
        từ 2000 kg trở lên cần báo sớm ít nhất 05 ngày làm việc.
    </div>
    <table class="field-table">
        <tr>
            <td style="width:50%;">
                <span class="cb-sym">{!! $cb($isUrgent) !!}</span>
                <span style="font-weight:bold; font-size:9pt;">Gấp</span>
                &nbsp;
                <span style="font-size:8.5pt; color:#555;">
                    Lý do: {{ $isUrgent ? $urgentReason : '—' }}
                </span>
            </td>
            <td style="width:50%; text-align:right;">
                <span style="font-size:8pt; color:#888;">
                    Loại yêu cầu:
                    <strong style="color:#8B1A1A;">{{ $tripType }}</strong>
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- D --}}
<div class="sec-header">
    <span class="sec-badge">D</span>
    <span class="sec-title">Khu vực / đối tượng được phân bổ</span>
</div>
<div class="sec-body">
    <div style="padding:3pt 7pt 2pt; font-size:8pt; font-weight:bold;
                color:#555; border-bottom:0.5pt solid #ebebeb;">
        d.1 Đối tượng sử dụng
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
    <table class="field-table" style="border-top:0.5pt solid #e0e0e0;">
        <tr>
            <td style="width:34%;">
                <div class="fl">d.2 Nhân sự phụ trách điều phối — Họ tên</div>
                <div class="fv">{{ $coordName ?: '—' }}</div>
            </td>
            <td style="width:42%;">
                <div class="fl">Email</div>
                <div class="fv">{{ $coordEmail ?: '—' }}</div>
            </td>
            <td style="width:24%;">
                <div class="fl">SĐT</div>
                <div class="fv {{ !$coordPhone ? 'fv-empty' : '' }}">
                    {{ $coordPhone ?: '—' }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- E --}}
<div class="sec-header">
    <span class="sec-badge">E</span>
    <span class="sec-title">Nội dung đề nghị vận chuyển</span>
    @if(!$isCargo && !$isBusiness)
        <br/>
        <span class="sec-subtitle">
            @if($isP2P)
                E.1 Bảng nội dung hành trình (Điểm — Điểm)
            @else
                E.1 Bảng nội dung hành khách
            @endif
        </span>
    @elseif($isBusiness)
        <br/>
        <span class="sec-subtitle">E.2 Bảng nội dung công tác</span>
    @endif
</div>
<div class="sec-body sec-body--flush">
    @if($isP2P && $p2pNote)
        <div class="p2p-note-bar">{{ $p2pNote }}</div>
    @endif

    @if($isCargo)
        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" class="col-info tc" style="width:4%;">STT</th>
                    <th colspan="5" class="col-info tc">Thông tin hàng hóa</th>
                    <th colspan="3" class="col-go tc">Điểm tập kết hàng hóa</th>
                    <th colspan="3" class="col-back tc">Điểm giao hàng hóa</th>
                    <th rowspan="2" class="col-info tc" style="width:8%; color:#8B1A1A; font-style:italic;">Loại hình<br/><span style="font-size:7.5pt;">(NV điều phối)</span></th>
                    <th rowspan="2" class="col-info tc" style="width:8%;">Chi phí<br/><span style="font-size:7.5pt;">(Gồm VAT)</span></th>
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
                        <td class="tr">{{ DispatchRequestPdfPresenter::formatCostCell($row['name'] ?? '', $row['cost'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="row-total">
                    <td colspan="13" class="tr" style="padding-right:8pt;">Tổng:</td>
                    <td class="tr">{{ $grandTotalFmt }}</td>
                </tr>
            </tbody>
        </table>

        <table class="field-table" style="border-top:0.5pt solid #e0e0e0;">
            <tr>
                <td style="width:72%;">
                    <div class="fl">e.1.1 Các ghi chú khác đề xuất</div>
                    <div class="fv" style="font-size:8.5pt;">
                        <span class="cb-sym">{!! $cb($needPorters) !!}</span> Yêu cầu bốc xếp / nhân công hỗ trợ
                        &nbsp; Số lượng: <strong>{{ $needPorters ? $porterQty : '—' }}</strong>
                        &nbsp; Chi phí phát sinh: <strong>{{ $needPorters ? $porterCost : '—' }}</strong>
                    </div>
                    <div class="fv" style="font-size:8.5pt; margin-top:4pt;">
                        <span class="cb-sym">{!! $cb($interprovincial) !!}</span> Gửi chành xe đi tỉnh
                        &nbsp; Chi phí phát sinh: <strong>{{ $interprovincial ? $interprovincialCost : '—' }}</strong>
                    </div>
                    @if($cargoExtraNotes)
                        <div class="muted-note" style="margin-top:6pt;">Ghi chú thêm: {{ $cargoExtraNotes }}</div>
                    @endif
                </td>
                <td class="muted-note" style="width:28%; vertical-align:top; font-size:7.5pt;">(Vui lòng liên hệ NV Điều vận để điền thông tin chi phí)</td>
            </tr>
        </table>
    @endif

    @if(!$isCargo && !$isBusiness)
        <table class="data">
            <thead>
                <tr>
                    <th class="col-info tc" style="width:4%;">STT</th>
                    <th class="col-info tl" style="width:14%;">Diễn giải</th>
                    <th class="col-info tc" style="width:5%;">SL</th>
                    <th class="col-go tc" style="width:9%;">TG đi</th>
                    <th class="col-go tl" style="width:11%;">Điểm đón</th>
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
                    <td colspan="10" class="tr" style="padding-right:8pt;">
                        Tổng ước tính:
                    </td>
                    <td class="tr">{{ $grandTotalFmt }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if($isBusiness)
        <table class="data">
            <thead>
                <tr>
                    <th class="col-info tc" style="width:4%;">STT</th>
                    <th class="col-info tl" style="width:14%;">Diễn giải</th>
                    <th class="col-info tc" style="width:5%;">SL</th>
                    <th class="col-info tl" style="width:10%;">Điểm dừng / cung đường</th>
                    <th class="col-info tl" style="width:8%;">Ghi chú</th>
                    <th class="col-go tc" style="width:9%;">TG đi</th>
                    <th class="col-go tl" style="width:10%;">Điểm đi</th>
                    <th class="col-back tc" style="width:9%;">TG về</th>
                    <th class="col-back tl" style="width:10%;">Điểm đến</th>
                    <th class="col-info" style="width:21%;">Khác</th>
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

{{-- F --}}
<div class="sec-header" style="margin-top:8pt;">
    <span class="sec-badge">F</span>
    <span class="sec-title">Phần xác nhận của các bên liên quan</span>
</div>
<table class="sig-table" style="border:0.5pt solid #e0e0e0;">
    <tr>
        <td class="sig-col-header">Trưởng phòng mua hàng</td>
        <td class="sig-col-header">Người đề xuất</td>
        <td class="sig-col-header">Trưởng đơn vị đề xuất</td>
    </tr>
    <tr>
        <td class="sig-col-body">
            <span class="sig-name">Phạm Thanh Hùng</span><br/>
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

{{-- G --}}
<div class="sec-header" style="margin-top:8pt;">
    <span class="sec-badge">G</span>
    <span class="sec-title">Phần xác nhận của phòng Mua Hàng</span>
</div>
<div class="sec-body g-row">
    <div style="margin-bottom:5pt;">
        <span class="g-label">g.1 Mã vận đơn (PO):</span>
        <span class="g-dots">{{ $poCode }}</span>
    </div>
    <div>
        <span class="g-label">g.2 Ngày nhận đề nghị (đã được phê duyệt):</span>
        <span class="g-dots">{{ $g2Date }}</span>
    </div>
</div>

<div class="page-footer">
    BM.03/MH.QT.04 — Đề Nghị Điều Vận — Vietnam America Schools
</div>

</body>
</html>
