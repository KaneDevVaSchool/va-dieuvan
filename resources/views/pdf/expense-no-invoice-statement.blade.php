@php($doc = $d['doc'])
@php($s = $d['settings'])
@php($sig = array_pad(array_map(fn ($x) => $x['name'] ?? '', $d['signatories']), 1, ''))
@php($roles = array_pad(array_map(fn ($x) => $x['role'] ?? '', $d['signatories']), 1, ''))
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @include('pdf.partials.expense-style')
    <style>@page { size: A4 landscape; }</style>
</head>
<body>
<table>
    <tr>
        <td style="width: 60%">Mã hồ sơ: <b>{{ $d['code'] }}</b>@if($d['basis']) — kèm {{ $d['basis'] }}@endif</td>
        <td class="c meta">Mẫu số: {{ $d['form_code']['code'] ?? '01/TNDN' }}<br>(Ban hành kèm theo Thông tư số 78/2014/TT-BTC của Bộ Tài chính)</td>
    </tr>
</table>

<p class="title" style="margin: 10pt 0 0">BẢNG KÊ THU MUA HÀNG HÓA, DỊCH VỤ<br>MUA VÀO KHÔNG CÓ HÓA ĐƠN</p>
<p class="c muted" style="margin: 2pt 0 8pt">{{ $d['date_line'] }}</p>

<table class="kv">
    <tr><td>- Tên doanh nghiệp: {{ $s->company_name }}</td></tr>
    <tr><td>- Mã số thuế: {{ $s->tax_code }}</td></tr>
    <tr><td>- Địa chỉ: {{ $s->company_address }}</td></tr>
    <tr><td>- Địa chỉ nơi tổ chức thu mua: {{ $s->purchasing_place }}</td></tr>
    <tr><td>- Người phụ trách thu mua: {{ $doc->purchasing_person ?: $doc->requester_name }}</td></tr>
</table>

<table class="grid" style="margin-top: 6pt">
    <thead>
    <tr>
        <th rowspan="2" style="width: 10%">Ngày tháng năm mua hàng</th>
        <th colspan="2">Người bán</th>
        <th colspan="4">Hàng hóa mua vào</th>
        <th rowspan="2" style="width: 12%">Ghi chú</th>
    </tr>
    <tr>
        <th style="width: 14%">Tên người bán</th><th style="width: 18%">Địa chỉ</th>
        <th>Tên mặt hàng</th><th style="width: 6%">Số lượng</th><th style="width: 10%">Đơn giá</th><th style="width: 12%">Tổng giá trị thanh toán</th>
    </tr>
    </thead>
    <tbody>
    @foreach($d['lines'] as $l)
        <tr>
            <td class="c">{{ $l['purchased_on'] }}</td>
            <td>{{ $l['seller_name'] }}</td>
            <td>{{ $l['seller_address'] }}</td>
            <td>{{ trim(implode(' — ', array_filter([$l['category'], $l['description']]))) }}@if($l['license_plate']) <span class="muted">(Xe {{ $l['license_plate'] }})</span>@endif</td>
            <td class="c">{{ rtrim(rtrim(number_format($l['quantity'], 2, ',', '.'), '0'), ',') }}</td>
            <td class="r">{{ number_format($l['unit_price'], 0, ',', '.') }}</td>
            <td class="r">{{ number_format($l['amount'], 0, ',', '.') }}</td>
            <td>{{ $l['note'] }}</td>
        </tr>
    @endforeach
    <tr><td colspan="6" class="b">Tổng cộng</td><td class="r b">{{ number_format($d['total'], 0, ',', '.') }}</td><td></td></tr>
    </tbody>
</table>
<p style="margin: 5pt 0">Tổng giá trị hàng hóa mua vào: <b>{{ $d['amount_in_words'] }}</b></p>

<table class="sign" style="margin-top: 8pt">
    <tr><td class="b" style="width: 50%">Người lập bảng kê</td><td class="b">{{ $roles[0] ?: 'Phó Tổng Giám đốc' }}</td></tr>
    <tr><td class="name">{{ $doc->purchasing_person ?: $doc->requester_name }}</td><td class="name">{{ $sig[0] }}</td></tr>
</table>

<p class="muted" style="margin-top: 10pt">Ghi chú: Căn cứ vào số thực tế các mặt hàng trên mà đơn vị mua của người bán không có hóa đơn, lập bảng kê khai theo thứ tự thời gian mua hàng, doanh nghiệp ghi đầy đủ các chỉ tiêu trên bảng kê, tổng hợp bảng kê hàng tháng. Hàng hóa mua vào lập theo bảng kê này được căn cứ vào chứng từ mua bán giữa người bán và người mua lập trong đó ghi rõ số lượng, giá trị các mặt hàng mua, ngày, tháng mua, địa chỉ, số CMTND của người bán và ký nhận của bên bán và bên mua.</p>
</body>
</html>
