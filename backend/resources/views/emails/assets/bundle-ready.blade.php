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
                    <p style="line-height: 1.4;">
                        Your requested media is now ready for download! 🎉
                    </p>

                    <p style="line-height: 1.4;">
                        We've made your media ready to be downloaded.
                        Please note that this download link is valid for 24 hours, so be sure to grab your files before the time runs out.
                    </p>

                    <p style="line-height: 1.4;">
                        <a href="{{ $url }}" target="_blank" style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px; ">
                            Download Media
                        </a>
                    </p>

                    <p style="line-height: 1.4;">
                        If the link expires, you can always request a new download from your account.
                    </p>

                    <p style="line-height: 1.7;">
                        Regards, <br> The Syllaby Team
                    </p>
                </div>
            </td>
        </tr>
        <tr>
        </tbody>
    </table>
</x-emails.main>
