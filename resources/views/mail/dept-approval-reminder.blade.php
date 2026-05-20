<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nhắc duyệt — {{ $requestRefCode }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:20px 0;">
    <tr>
        <td align="center" style="padding:0 12px;">
            <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#8B1A3A;padding:20px 26px;color:#ffffff;">
                        <table width="100%" cellspacing="0" cellpadding="0" role="presentation">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <span style="display:inline-block;font-size:12px;font-weight:600;color:#ffffff;background:rgba(255,255,255,0.18);padding:4px 10px;border-radius:6px;">VA Schools · Điều vận</span>
                                </td>
                                <td align="right" style="vertical-align:middle;">
                                    <span style="font-size:11px;color:rgba(255,255,255,0.92);border:1px solid rgba(255,255,255,0.3);padding:3px 10px;border-radius:10px;">⏰ Nhắc duyệt</span>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:14px 0 4px;font-size:17px;font-weight:600;line-height:1.35;color:#ffffff;">Phiếu vẫn đang chờ xác nhận của {{ $deptHeadName }}</p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.78);">Vui lòng xem và duyệt khi thuận tiện để không trễ lịch vận hành.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 26px;color:#334155;font-size:14px;line-height:1.6;">
                        <p style="margin:0 0 6px;color:#0f172a;font-weight:600;">Kính gửi {{ $deptHeadName }},</p>
                        <p style="margin:0 0 16px;color:#475569;">Hệ thống gửi email nhắc vì phiếu dưới đây đã chờ duyệt một thời gian sau khi Điều vận fill giá.</p>

                        @if($hasDeadlineNotice)
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#FFF7ED;border:1px solid #FBBF77;border-radius:8px;margin:0 0 18px;">
                            <tr>
                                <td style="padding:12px 14px;color:#92400e;font-size:13px;line-height:1.5;">
                                    <strong style="color:#713f12;">Hạn duyệt tham khảo:</strong> {{ $deadlineNotice }}
                                </td>
                            </tr>
                        </table>
                        @endif

                        @include('mail.partials.request-summary-table')
                        @include('mail.partials.price-breakdown')

                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 22px;">
                            <tr>
                                <td style="border-radius:8px;background:#8B1A3A;text-align:center;">
                                    <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">Xem phiếu &amp; duyệt →</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 22px;text-align:center;font-size:12px;"><a href="{{ $detailUrl }}" style="color:#8B1A3A;text-decoration:none;">Hoặc mở liên kết trực tiếp trong trình duyệt của bạn</a></p>

                        @include('mail.partials.footer')
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
