<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse:collapse;">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <div style="text-align:left;">
                    <p style="line-height: 1.6;">
                        Time is ticking! Your Syllaby trial is ending in 3 days.
                        Here's a reminder of your subscription details:
                    </p>

                    <p style="line-height: 1.6; margin: 6px 0;">
                        <strong>Plan Details:</strong>
                    </p>

                    <p style="line-height: 1.4; margin: 2px 0;">
                        <span>
                            <strong>Plan</strong>: {{ $plan['name'] }}
                        </span>

                        <br>

                        <span>
                            <strong>Plan Price</strong>: {{ $plan['price'] }}
                        </span>

                        <br>

                        <span>
                            <strong>Trial End Date</strong>: {{ $subscription->trial_ends_at->format('m-d-Y') }}
                        </span>
                        <br>

                        <span>
                            <strong>Renewal Frequency</strong>: {{ ucfirst($plan['recurrence']) }}
                        </span>
                    </p>

                    <p style="line-height: 1.6;">
                            <span>
                                <strong>What to Expect:</strong>
                            </span>
                        <br>

                        <span>
                            On {{ $subscription->trial_ends_at->format('m-d-Y') }}, your trial will end and your
                            subscription will begin. You will be charged {{ $invoice['amount'] }}.
                        </span>
                    </p>

                    <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
