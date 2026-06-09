<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Giờ xuất phát thay đổi — #{{ $tripTypeLabel }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:20px 0;">
    <tr>
        <td align="center" style="padding:0 12px;">
            <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#92400e;padding:20px 26px;color:#ffffff;">
                        <table width="100%" cellspacing="0" cellpadding="0" role="presentation">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <span style="display:inline-block;font-size:12px;font-weight:600;color:#ffffff;background:rgba(255,255,255,0.18);padding:4px 10px;border-radius:6px;">VA Schools · Điều vận</span>
                                </td>
                                <td align="right" style="vertical-align:middle;">
                                    <span style="font-size:11px;color:#fde68a;border:1px solid rgba(253,230,138,0.6);padding:3px 10px;border-radius:10px;">⏰ ĐỔI GIỜ</span>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:14px 0 4px;font-size:17px;font-weight:600;line-height:1.35;color:#ffffff;">Giờ xuất phát chuyến đã thay đổi</p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.78);">Vui lòng cập nhật lịch của bạn.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 26px;color:#334155;font-size:14px;line-height:1.6;">
                        <p style="margin:0 0 6px;color:#0f172a;font-weight:600;">Kính gửi {{ $driverName }},</p>
                        <p style="margin:0 0 16px;color:#475569;">Điều vận đã điều chỉnh giờ xuất phát cho chuyến xe dưới đây.</p>

                        <table width="100%" cellspacing="0" cellpadding="8" role="presentation" style="border-collapse:collapse;margin:0 0 18px;font-size:13px;">
                            <tr><td width="42%" style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Loại chuyến</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $tripTypeLabel }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Hành trình</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $routeLine }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Giờ cũ</td><td style="border-bottom:1px solid #e2e8f0;color:#94a3b8;text-decoration:line-through;">{{ $oldDepartLabel }}</td></tr>
                            <tr><td style="padding:8px 0;color:#64748b;">Giờ mới</td><td style="padding:8px 0;color:#0f172a;font-weight:700;">{{ $newDepartLabel }}</td></tr>
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 22px;">
                            <tr>
                                <td style="border-radius:8px;background:#92400e;text-align:center;">
                                    <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">Xem chuyến →</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 22px;text-align:center;font-size:12px;"><a href="{{ $detailUrl }}" style="color:#92400e;text-decoration:none;">Hoặc mở liên kết trực tiếp trong trình duyệt của bạn</a></p>

                        @include('mail.partials.footer')
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
