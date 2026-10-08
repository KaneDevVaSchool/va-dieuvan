@php($doc = $d['doc'])
@php($sig = array_pad(array_map(fn ($s) => $s['name'] ?? '', $d['signatories']), 4, ''))
@php($roles = array_pad(array_map(fn ($s) => $s['role'] ?? '', $d['signatories']), 4, ''))
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @include('pdf.partials.expense-style')
</head>
<body>
<table class="head">
    <tr>
        <td rowspan="3" style="width: 26%" class="org">HỆ THỐNG TRƯỜNG VIỆT MỸ</td>
        <td rowspan="3" class="title">ĐỀ NGHỊ MUA HÀNG HÓA | DỊCH VỤ</td>
        <td class="meta" style="width: 26%">Kí hiệu: {{ $d['form_code']['code'] ?? 'BM.01/MH.QT.01' }}</td>
    </tr>
    <tr><td class="meta">Ngày ban hành: {{ $d['form_code']['issued_on'] ?? '' }}</td></tr>
    <tr><td class="meta">Lần ban hành: {{ $d['form_code']['revision'] ?? '01' }}</td></tr>
</table>
<p class="r meta" style="margin: 3pt 0 0">Mã hồ sơ: <b>{{ $d['code'] }}</b></p>

<div class="sec">A. Người đề xuất</div>
<table class="kv">
    <tr><td class="k">a.1 Họ và tên:</td><td class="b">{{ $doc->requester_name }}</td></tr>
    <tr><td class="k">a.2 Email nhân viên:</td><td>{{ $doc->requester_email }}</td></tr>
    <tr><td class="k">a.3 Số điện thoại:</td><td>{{ $doc->requester_phone }}</td></tr>
    <tr><td class="k">a.4 Đơn vị:</td><td>{{ $doc->department_label }}</td></tr>
</table>

<div class="sec">B. Mục đích sử dụng</div>
<table class="kv">
    <tr><td class="k">b.1 Mục đích sử dụng:</td><td>{{ $doc->purpose }}</td></tr>
    <tr><td class="k">b.2 Căn cứ đề xuất:</td><td>{{ $d['basis'] }}</td></tr>
</table>

<div class="sec">C. Thời gian</div>
<table class="kv">
    <tr><td class="k">c.1 Ngày đề xuất:</td><td>{{ $d['proposed_on'] }}</td></tr>
    <tr><td colspan="2" class="muted">Lưu ý từ ngày P. Mua Hàng nhận được đề xuất đến khi giao hàng cần tối thiểu 05 - 07 ngày làm việc (ngoại trừ các mặt hàng cần gia công sản xuất sẽ được thông báo số ngày cụ thể). Các trường hợp yêu cầu ít hơn số ngày trên được xem là gấp cần ghi lý do phía bên dưới.</td></tr>
    <tr><td class="k">c.2 Ngày cần nhận hàng hóa/sử dụng dịch vụ:</td><td>{{ $d['needed_by'] }}</td></tr>
    <tr><td class="k">Gấp:</td><td>{{ $doc->is_urgent ? 'Có — '.$doc->urgent_reason : '' }}</td></tr>
    <tr><td class="k">c.3 Ngày P. MH nhận đề xuất:</td><td></td></tr>
</table>

<div class="sec">D. Khu vực/ đối tượng phân bổ hàng hóa</div>
<table class="kv">
    <tr><td class="k">d.1 Đối tượng sử dụng:</td><td>{{ $doc->allocation_target }} &nbsp;&nbsp; <b>Biển số xe:</b> {{ $d['plates'] }}</td></tr>
    <tr><td class="k">d.2 Khu vực phân bổ sử dụng chung:</td><td>{{ $doc->allocation_area }}</td></tr>
    <tr><td class="k">d.3 Nhân sự nhận bàn giao tài sản:</td><td>{{ $doc->handover_person ?: $doc->requester_name }}</td></tr>
</table>

<div class="sec">E. Nội dung đề xuất hàng hóa - dịch vụ</div>
<table class="grid">
    <thead>
    <tr>
        <th style="width: 5%">STT</th><th style="width: 20%">Danh mục</th><th>Diễn giải chi tiết</th>
        <th style="width: 7%">Đvt</th><th style="width: 6%">SL</th><th style="width: 13%">Đơn giá (+VAT)</th>
        <th style="width: 13%">Thành tiền</th><th style="width: 11%">Ghi chú</th>
    </tr>
    </thead>
    <tbody>
    @foreach($d['lines'] as $i => $l)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $l['category'] }}</td>
            <td>{!! nl2br(e($l['description'])) !!}@if($l['license_plate'])<br><span class="muted">Xe {{ $l['license_plate'] }}</span>@endif</td>
            <td class="c">{{ $l['unit'] }}</td>
            <td class="c">{{ rtrim(rtrim(number_format($l['quantity'], 2, ',', '.'), '0'), ',') }}</td>
            <td class="r">{{ number_format($l['unit_price'], 0, ',', '.') }}</td>
            <td class="r">{{ number_format($l['amount'], 0, ',', '.') }}</td>
            <td>{{ $l['note'] }}</td>
        </tr>
    @endforeach
    <tr><td colspan="6" class="r b">Tổng:</td><td class="r b">{{ number_format($d['total'], 0, ',', '.') }}</td><td></td></tr>
    </tbody>
</table>
<p class="muted" style="margin: 3pt 0">Bằng chữ: {{ $d['amount_in_words'] }}<br>Lưu ý: đơn giá chưa bao gồm chi phí vận chuyển. Nếu chi phí thực tế mua hàng vượt quá 10% thì trình ký lại.</p>

<div class="sec">F. Phần xác nhận của các bên liên quan</div>
<table class="sign">
    <tr>
        <td class="b">{{ $roles[0] ?: 'Giám đốc vận hành' }}</td>
        <td class="b">{{ $roles[1] ?: 'Trưởng đơn vị đề xuất' }}</td>
        <td class="b">{{ $roles[2] ?: 'Tổ trưởng Tài xế' }}</td>
        <td class="b">Người đề xuất</td>
    </tr>
    <tr>
        <td class="name">{{ $sig[0] }}</td><td class="name">{{ $sig[1] }}</td><td class="name">{{ $sig[2] }}</td><td class="name">{{ $doc->requester_name }}</td>
    </tr>
</table>
<table class="sign" style="margin-top: 8pt">
    <tr><td class="b">Người đề xuất</td><td class="b">Trưởng đơn vị đề xuất</td><td class="b">{{ $roles[3] ?: 'Phó Tổng Giám đốc' }}</td></tr>
    <tr><td class="name">{{ $doc->requester_name }}</td><td class="name">{{ $sig[1] }}</td><td class="name">{{ $sig[3] }}</td></tr>
</table>

<div class="sec">G. Phần xác nhận của phòng Mua Hàng</div>
<table class="kv">
    <tr><td class="k">g.1 Mã đơn hàng (PO):</td><td></td></tr>
    <tr><td class="k">g.2 Ngày nhận đề nghị (đã được phê duyệt):</td><td></td></tr>
    <tr><td class="k">g.3 Nhân viên mua hàng:</td><td></td></tr>
</table>
</body>
</html>
