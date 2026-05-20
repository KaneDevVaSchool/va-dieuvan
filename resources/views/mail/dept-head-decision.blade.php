<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@if($decision === 'reject')Phiếu bị từ chối@elsePhiếu đã duyệt@endif — {{ $requestRefCode }}</title>
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
                                    @if($decision === 'reject')
                                    <span style="font-size:11px;color:#fecaca;border:1px solid rgba(254,202,202,0.6);padding:3px 10px;border-radius:10px;">✗ Đã từ chối</span>
                                    @else
                                    <span style="font-size:11px;color:#bbf7d0;border:1px solid rgba(187,247,208,0.5);padding:3px 10px;border-radius:10px;">✅ Đã duyệt</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @if($decision === 'reject')
                        <p style="margin:14px 0 4px;font-size:17px;font-weight:600;line-height:1.35;color:#ffffff;">Phiếu đề xuất của bạn đã bị {{ $deptHeadName }} từ chối</p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.78);">Vui lòng xem lý do và chỉnh sửa phiếu trên hệ thống Điều vận nếu cần gửi lại.</p>
                        @else
                        <p style="margin:14px 0 4px;font-size:17px;font-weight:600;line-height:1.35;color:#ffffff;">Phiếu đề xuất của bạn đã được {{ $deptHeadName }} duyệt</p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.78);">Điều vận sẽ tiếp tục điều phối phương tiện. Bạn có thể xem phiếu và xuất PDF trên hệ thống.</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 26px;color:#334155;font-size:14px;line-height:1.6;">
                        <p style="margin:0 0 6px;color:#0f172a;font-weight:600;">Kính gửi {{ $requesterName }},</p>
                        @if($decision === 'reject')
                        <p style="margin:0 0 16px;color:#475569;">Trưởng bộ phận đã từ chối phiếu đề xuất dưới đây. Vui lòng mở phiếu trên Điều vận để xem chi tiết và chỉnh sửa theo góp ý.</p>
                        @else
                        <p style="margin:0 0 16px;color:#475569;">Trưởng bộ phận đã xác nhận phiếu đề xuất dưới đây. Phiếu chuyển sang bước điều phối xe của Điều vận.</p>
                        @endif

                        @if($hasRejectionReason)
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#FFF7ED;border:1px solid #FBBF77;border-radius:8px;margin:0 0 18px;">
                            <tr>
                                <td style="padding:12px 14px;color:#92400e;font-size:13px;line-height:1.5;">
                                    <strong style="color:#713f12;">Lý do từ chối:</strong><br>
                                    {{ $rejectionReason }}
                                </td>
                            </tr>
                        </table>
                        @endif

                        <p style="margin:0 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:600;">Thông tin phiếu đề xuất</p>
                        <table width="100%" cellspacing="0" cellpadding="8" role="presentation" style="border-collapse:collapse;margin:0 0 18px;font-size:13px;">
                            <tr><td width="42%" style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Mã phiếu</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $requestRefCode }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Loại dịch vụ</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $tripTypeLabel }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Người đề xuất</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $requesterLine }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Hành trình</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $routeLine }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Khởi hành</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $timeLineDepart }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Kết thúc dự kiến</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $timeLineArrive }}</td></tr>
                            <tr><td style="border-bottom:1px solid #e2e8f0;color:#64748b;padding:8px 0;">Điều phối / Trưởng đoàn</td><td style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:600;">{{ $leaderLine }}</td></tr>
                            <tr><td style="padding:8px 0;color:#64748b;">Mục đích</td><td style="padding:8px 0;color:#0f172a;font-weight:600;">{{ $purposeLine }}</td></tr>
                        </table>

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

                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 22px;">
                            <tr>
                                <td style="border-radius:8px;background:#8B1A3A;text-align:center;">
                                    <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">Xem phiếu →</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 22px;text-align:center;font-size:12px;"><a href="{{ $detailUrl }}" style="color:#8B1A3A;text-decoration:none;">Hoặc mở liên kết trực tiếp trong trình duyệt của bạn</a></p>

                        <hr style="border:none;border-top:1px solid #e2e8f0;margin:18px 0;">

                        <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Tiến trình xử lý</p>
                        <table width="100%" cellspacing="0" cellpadding="0" role="presentation" style="font-size:11px;color:#64748b;margin-bottom:14px;text-align:center;">
                            <tr>
                                <td>✔ Tạo phiếu</td>
                                <td>✔ Fill giá</td>
                                @if($decision === 'reject')
                                <td style="color:#b91c1c;font-weight:700;">✗ Trưởng BP</td>
                                @else
                                <td style="color:#15803d;font-weight:700;">✔ Trưởng BP</td>
                                @endif
                                <td>@if($decision === 'reject')—@else Điều xe @endif</td>
                                <td>Lưu trữ</td>
                            </tr>
                        </table>

                        <p style="margin:0 0 14px;color:#64748b;font-size:11px;line-height:1.65;">
                            @if($decision === 'reject')
                            Bạn có thể chỉnh sửa và gửi lại quy trình theo hướng dẫn nội bộ của trường.<br><br>
                            @else
                            Sau khi duyệt, bạn có thể xuất PDF phiếu trên hệ thống. Điều vận tiến hành điều phối xe.<br><br>
                            @endif
                            Đây là email tự động từ VA Schools Điều vận — vui lòng không trả lời trực tiếp email này.
                            @if($helpdesk !== '')
                                <br>Nếu cần hỗ trợ: {{ $helpdesk }}
                            @else
                                <br>Nếu cần hỗ trợ, liên hệ IT Helpdesk của trường.
                            @endif
                        </p>

                        <p style="margin:10px 0 0;font-size:11px;color:#94a3b8;line-height:1.5;">
                            🔒 {{ $privacyScopeFooter }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
