 <x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse;">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            Great news! We have extended your Syllaby free trial by an additional 7 days, giving you a
                            total of 14 days to explore all that Syllaby has to offer. Your trial will
                            end on <strong>{{ $subscription->trial_ends_at->format('l, M d') }}</strong>.
                        </p>

                        <p style="line-height: 1.6;">
                            We hope you continue to enjoy your trial, and please don't hesitate to reach out if you
                            have any questions or need assistance during this extended period.
                        </p>

                        <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
