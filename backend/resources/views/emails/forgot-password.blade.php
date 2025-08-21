<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse:collapse">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding: 30px 60px;">
                <!-- Email content -->
                <div style="text-align:left;">
                    <p style="line-height: 1.6; margin-bottom: 25px;">
                        You are receiving this email because we received a password reset request for your Syllaby
                        account:
                    </p>
                    <a href="{{ $url }}" target="_blank" style="text-decoration: none; display: inline-block; background: #1886FF; color: #fff; padding: 15px 20px; border-radius: 6px;">
                        Reset Password
                    </a>

                    <p style="margin-bottom: 25px; margin-top: 25px; line-height: 1.6;">
                        This password reset link will expire in {{ config('common.token_expiration') }}.
                        If you did not initiate this password reset request, please ignore this email and your
                        account is still secure
                    </p>

                    <p style="line-height: 1.7;">Best, <br> The Syllaby team</p>

                    <p style="color: #5E6278; line-height: 1.6;">
                        For any assistance or further inquiries, feel free to contact us at
                        <br/>
                        <a href="mailto:info@syllaby.io" style="color: #1886FF; font-weight: bold;">
                            info@syllaby.io
                        </a>
                    </p>

                    <p style="color: #5E6278; margin-top: 30px;">We are here to help you.</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
