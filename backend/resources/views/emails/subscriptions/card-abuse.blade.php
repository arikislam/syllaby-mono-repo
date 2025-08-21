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
                        Thank you for your interest in Syllaby and for starting the journey with our free trial!
                        We wanted to inform you that the credit card you've provided was used
                        multiple times in a short period of time by other accounts in our system.
                    </p>
                    <p style="line-height: 1.6;">
                        To ensure fairness and prevent potential misuse of our 7-day free trial,
                        our policy restricts the same card from being used across multiple accounts
                        in short periods of time.
                    </p>

                    <a href="{{ $url }}" target="_blank"
                       style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px;">
                        Subscription Management
                    </a>

                    <p style="line-height: 1.6;">
                        If you have any questions or need further assistance, please feel free to reach out. We're here
                        to help and ensure you have a seamless experience with Syllaby.
                    </p>


                    <p style="line-height: 1.6;">
                        Thank you for your understanding and cooperation.
                    </p>

                    <p style="line-height: 1.7;">Best, <br> Austin Armstrong, CEO</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
