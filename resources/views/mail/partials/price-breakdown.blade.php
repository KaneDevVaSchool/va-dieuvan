<p style="margin:0 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:600;">Chi phí dịch vụ</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:rgba(139,26,58,0.06);border:1px solid rgba(139,26,58,0.15);border-radius:10px;margin:0 0 18px;">
    <tr>
        <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#475569;">
            @if($showPriceBreakdown)
            <table width="100%" cellspacing="0" cellpadding="6" role="presentation">
                <tr>
                    <td style="padding:4px 0;color:#475569;">Tổng đơn giá (cột Đơn giá BM.03)</td>
                    <td align="right" style="padding:4px 0;color:#0f172a;font-weight:600;">{{ $unitPriceTotalFmt }}</td>
                </tr>
                @if($showExtraLine)
                <tr>
                    <td style="padding:4px 0;color:#475569;font-size:12px;">Tổng phụ thu (cột Phụ thu BM.03)</td>
                    <td align="right" style="padding:4px 0;color:#64748b;">{{ $extraFeesFmt }}</td>
                </tr>
                @endif
            </table>
            <hr style="border:none;border-top:1px solid rgba(139,26,58,0.15);margin:10px 0;">
            @endif
            <table width="100%" cellspacing="0" cellpadding="6" role="presentation">
                <tr>
                    <td style="color:#0f172a;font-weight:600;">{{ $showPriceBreakdown ? 'Tổng cộng (sau Fill giá)' : 'Tổng chi phí dịch vụ' }}</td>
                    <td align="right" style="font-size:16px;font-weight:700;color:#8B1A3A;">{{ $grandTotalFmt }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
