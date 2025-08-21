 <x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>
    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <!-- Email content -->
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            We're sorry to see you go. This email is to confirm that
                            your subscription for syllaby has been deactivated
                            successfully on {{ $endDate }}.
                        </p>

                        <p style="line-height: 1.6;">
                            While your subscription is deactivated, please note that you will no longer have access to the
                            exclusive features and benefits that syllaby offers. Should you decide to reactivate your
                            subscription in the future, we would be more than happy to welcome you back.
                        </p>

                        <p style="line-height: 1.6; margin-top: 15px;">
                            We genuinely value your feedback, and if there is anything we can do to improve our product or
                            service, please let us know at
                            <a href="mailto:info@syllaby.io">info@syllaby.io</a>
                        </p>

                        <p style="line-height: 1.6; margin-top: 15px;">
                            Thank you for being a part of syllaby Community.
                        </p>

                        <p style="line-height: 1.7;">Regards, <br> The syllaby Team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
