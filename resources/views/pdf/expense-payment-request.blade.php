@php($doc = $d['doc'])
@php($sig = array_pad(array_map(fn ($s) => $s['name'] ?? '', $d['signatories']), 3, ''))
@php($roles = array_pad(array_map(fn ($s) => $s['role'] ?? '', $d['signatories']), 3, ''))
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @include('pdf.partials.expense-style')
</head>
<body>
<table>
    <tr>
        <td style="width: 50%">Mã Hồ sơ: <b>{{ $d['code'] }}</b></td>
        <td class="r meta">{{ $d['form_code']['code'] ?? 'BM03-PKT-QT-TUTT' }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="r">Đơn vị: <b>{{ $d['settings']->company_name }}</b><br>Bộ phận: {{ $doc->department_label }}</td>
    </tr>
</table>

<p class="title" style="margin: 14pt 0 2pt">GIẤY ĐỀ NGHỊ {{ $d['title_suffix'] }}</p>
<p class="c muted" style="margin: 0 0 10pt">{{ $d['date_line'] }}</p>

<table class="kv">
    <tr><td class="k">Kính gửi:</td><td class="b">Phòng Kế toán</td></tr>
    <tr><td class="k">Họ và tên người đề nghị:</td><td class="b">{{ $doc->requester_name }}</td></tr>
    <tr><td class="k">Bộ phận (hoặc địa chỉ):</td><td>{{ $doc->department_label }}</td></tr>
    <tr><td class="k">Nội dung:</td><td>{{ $doc->purpose }}@if($d['plates']) — Xe {{ $d['plates'] }}@endif</td></tr>
    <tr><td class="k">Căn cứ theo đề xuất mua hàng:</td><td>{{ $d['basis'] }}</td></tr>
    <tr><td class="k">Số tiền:</td><td class="b">{{ number_format($d['total'], 0, ',', '.') }} đ</td></tr>
    <tr><td class="k">Viết bằng chữ:</td><td>{{ $d['amount_in_words'] }}</td></tr>
    <tr><td class="k">Thời gian thanh toán:</td><td>{{ $doc->payment_due_label }}</td></tr>
    <tr><td class="k">Hình thức thanh toán:</td><td>{{ $d['payment_method_label'] }}</td></tr>
    <tr><td class="k">STK người nhận:</td><td>{{ $d['bank_line'] }}</td></tr>
</table>

@if(count($d['lines']))
    <p class="b" style="margin: 10pt 0 3pt">Chi tiết khoản chi</p>
    <table class="grid">
        <thead>
        <tr><th style="width: 5%">STT</th><th style="width: 12%">Ngày</th><th>Nội dung</th><th style="width: 13%">Biển số</th><th style="width: 11%">Hóa đơn</th><th style="width: 16%">Số tiền</th></tr>
        </thead>
        <tbody>
        @foreach($d['lines'] as $i => $l)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td class="c">{{ $l['purchased_on'] }}</td>
                <td>{{ trim(implode(' — ', array_filter([$l['category'], $l['description']]))) }}</td>
                <td class="c">{{ $l['license_plate'] }}</td>
                <td class="c">{{ $l['has_invoice'] ? 'Có' : 'Không (bảng kê)' }}</td>
                <td class="r">{{ number_format($l['amount'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr><td colspan="5" class="r b">Tổng cộng</td><td class="r b">{{ number_format($d['total'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>
@endif

<table class="sign" style="margin-top: 14pt">
    <tr>
        <td class="b">Người đề nghị</td>
        <td class="b">{{ $roles[0] ?: 'Trưởng đơn vị' }}</td>
        <td class="b">{{ $roles[1] ?: 'P. Kế toán' }}</td>
    </tr>
    <tr><td class="name">{{ $doc->requester_name }}</td><td class="name">{{ $sig[0] }}</td><td class="name">{{ $sig[1] }}</td></tr>
</table>
<table class="sign" style="margin-top: 6pt">
    <tr><td class="b">{{ $roles[2] ?: 'Phó Tổng Giám đốc' }}</td></tr>
    <tr><td class="name">{{ $sig[2] }}</td></tr>
</table>
</body>
</html>
