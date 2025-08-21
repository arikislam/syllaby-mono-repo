<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <!-- Email content -->
                    <div style="text-align:left;">
                        <p style="line-height: 1.6">
                            This email is to confirm that your password has been successfully updated for your Syllaby account.
                            If you made this password update, you can ignore this email.
                        </p>

                          <p style="margin-bottom: 25px; margin-top: 20px; line-height: 1.6;">
                            If you did not make this change, please contact our support team immediately at
                            <a href="mailto:info@syllaby.io" style="color: #1886FF; font-weight: bold;">
                                info@syllaby.io
                            </a>
                        </p>

                          <p style="line-height: 1.7;">Thank you, <br> The Syllaby team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
