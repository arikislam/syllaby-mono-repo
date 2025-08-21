<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse;">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            We are excited to inform you that your Syllaby storage has been updated.
                            Your storage limit is now <strong>{{ format_bytes($storage) }}</strong>.
                        </p>

                        <p style="line-height: 1.6;">
                            Your new <strong>{{ $quantity }} GB</strong> storage plan for <strong>{{ $amount }}</strong> 
                            has been successfully activated and is now available for use.
                        </p>

                        <p style="line-height: 1.6;">
                            Please note that you will be charged on a prorated basis for the remaining time of the 
                            current billing cycle. The full monthly charge will apply starting from your next 
                            billing cycle on <strong>{{ $billing_date }}</strong>.
                        </p>

                        <p style="line-height: 1.6;">
                            If you have any questions or need further assistance, feel free to reach out to our support team.
                        </p>

                        <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
