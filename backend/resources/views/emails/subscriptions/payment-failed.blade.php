<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>
    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse:collapse">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <!-- Email content -->
                <div style="text-align:left;">
                    <p style="line-height: 1.6;">
                        We couldn't complete a charge on your card to process your Syllaby subscription fee.
                    </p>

                    <p style="line-height: 1.6;">
                        To avoid any disruption or cancellation of your subscription, go to your Manage Subscription
                        page and review all payment information - card number, expiration date and billing address.
                    </p>

                    <a href="{{ $url }}" target="_blank"
                       style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px;">
                        Go to Manage Subscription
                    </a>

                    <p style="line-height: 1.6;">
                        If your payment information is up-to-date, please contact your bank to find out why they
                        declined a charge from Syllaby for your subscription fee.
                    </p>

                    <p style="line-height: 1.6;">
                        Please contact us if you have any questions and we'll be glad to assist you.
                    </p>

                    <p style="line-height: 1.7;">Best, <br> Austin Armstrong, CEO</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
