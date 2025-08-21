<table style="width: 100%; background: #021121; white-space:nowrap;" cellspacing="0" cellpadding="0">
    <tr>
        <td colspan="1" style="padding: 20px 0 20px 60px;">
            <a href="{{ config('app.frontend_url') }}" style="display: block;">
                <x-emails.image imageName="logo-footer.png"/>
            </a>
            <span style="margin-top: 10px; color: #fff; font-size: 11px; display: block;">
                201 W Main St, Durham, <br>
                United States of America
            </span>
        </td>
        <td colspan="1" style="padding: 20px 60px 20px 0; text-align: right;">
            <a href="https://www.facebook.com/trysyllaby" target="_blank" style="display: inline-block; margin-right: 5px;">
                <x-emails.image imageName="facebook-icon.png" alt="facebook"/>
            </a>
            <a href="https://twitter.com/TrySyllaby" target="_blank" style="display: inline-block; margin-right: 5px;">
                <x-emails.image imageName="twitter-icon.png" alt="twitter"/>
            </a>
            <a href="https://instagram.com/trysyllaby" target="_blank" style="display: inline-block;">
                <x-emails.image imageName="instagram-icon.png" alt="instagram"/>
            </a>
        </td>
    </tr>

    <tr>
        <td colspan="2" style="padding-top: 10px; text-align: center; color: #fff; font-size: 11px; border-top: 1px solid #3b3b3b; text-align: center;">
            <p style="margin: 0;">You received this email because you signed up on our website.</p>
            <a href="{{ config('app.frontend_url').'/my-account/notifications' }}" target="_blank" style="margin-top: 6px; display: block; text-decoration: underline; color: #fff;">
                Unsubscribe
            </a>
        </td>
    </tr>
    
    <tr>
        <td colspan="2" style="padding: 20px 0  20px 0; text-align: center; color: #fff; font-size: 11px;">
            <p style="margin: 0;">Copyright &copy; {{now()->format('Y')}} Syllaby</p>
        </td>
    </tr>
</table>
