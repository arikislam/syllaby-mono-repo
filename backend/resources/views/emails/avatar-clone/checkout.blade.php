<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse: collapse;">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <div style="text-align:left;">
                    <p style="line-height: 1.6;">
                        Thank you for choosing Real Clone to enhance your creative endeavors. We are confident that
                        you will find our product both innovative and intuitive to use. Your purchase of
                        {{ $product }} has been successfully processed and is in a review state.
                    </p>

                    <p style="line-height: 1.6;">
                        Processing time is around 3 to 5 business days, but we will strive to get back to you
                        sooner. In the meantime, if you have any questions or need assistance, feel free to reach
                        out to us at <a href="mailto:customerrequest@syllaby.io">customerrequest@syllaby.io</a>.
                    </p>

                    <p style="line-height: 1.6;">
                        Thank you again for choosing {{ $product }}. We appreciate your support
                        and look forward to serving you.
                    </p>

                    <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
