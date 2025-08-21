<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse: collapse;">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <div style="text-align:left;">
                    <p style="line-height: 1.6;">
                        We're excited to inform you that your Real Clone has been successfully generated!
                    </p>

                    <p style="line-height: 1.6;">
                        Congratulations on taking this step towards unleashing your creativity with Real Clone.
                        To start creating your masterpiece, simply click on the link below to access your real clone.
                    </p>

                    <a href="{{ $cta_url }}" target="_blank"
                       style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px; ">
                        Create Your Video Now!
                    </a>

                    <p style="line-height: 1.6;">
                        If you encounter any issues or have questions along the way, please don't hesitate to reach out
                        to us at <a href="mailto:customerrequest@syllaby.io">customerrequest@syllaby.io</a>
                    </p>

                    <p style="line-height: 1.6;">
                        Thank you for choosing Real Clone. We can't wait to see what you'll create!
                    </p>

                    <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
