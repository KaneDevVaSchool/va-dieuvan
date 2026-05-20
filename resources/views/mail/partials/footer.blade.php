@if(!empty($showProgress))
<hr style="border:none;border-top:1px solid #e2e8f0;margin:18px 0;">
<p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Tiến trình xử lý</p>
<table width="100%" cellspacing="0" cellpadding="0" role="presentation" style="font-size:11px;color:#64748b;margin-bottom:14px;text-align:center;">
    <tr>
        @foreach($progressCells ?? [] as $cell)
        <td{!! !empty($cell['style']) ? ' style="'.$cell['style'].'"' : '' !!}>{!! $cell['label'] ?? '' !!}</td>
        @endforeach
    </tr>
</table>
@endif

<p style="margin:0 0 14px;color:#64748b;font-size:11px;line-height:1.65;">
    @if(!empty($closingNote))
    {!! $closingNote !!}<br><br>
    @endif
    Đây là email tự động từ VA Schools Điều vận — vui lòng không trả lời trực tiếp email này.
    @if(($helpdesk ?? '') !== '')
        <br>Nếu cần hỗ trợ: {{ $helpdesk }}
    @else
        <br>Nếu cần hỗ trợ, liên hệ IT Helpdesk của trường.
    @endif
</p>

@if(!empty($privacyScopeFooter))
<p style="margin:10px 0 0;font-size:11px;color:#94a3b8;line-height:1.5;">
    🔒 {{ $privacyScopeFooter }}
</p>
@endif
