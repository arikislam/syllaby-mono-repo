<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse: collapse;">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            Exciting news – you've just upgraded to Syllaby's <strong>{{ $plan->name }}</strong>!
                        </p>

                        <p style="line-height: 1.6;">
                            This upgrade unlocks a new world of possibilities. Here’s the scoop:
                        </p>

                        <p style="line-height: 1.4;">
                            <span>✨ More credits to set your ideas free</span>
                            <br>
                            <span>📦 Increased storage for your videos</span>
                            <br>
                            <span>📅 Unlock more scheduled posts</span>
                        </p>

                        <p style="line-height: 1.6;">
                            Your commitment to growth and creativity is inspiring, and we're here every step of the way
                            to support your journey. Get ready to explore more, create more, and achieve
                            more with Syllaby.
                        </p>

                        <p style="line-height: 1.6;">
                            Need assistance with your new features? Reach out to us at
                            <a href="mailto:customerrequest@syllaby.io">customerrequest@syllaby.io</a>
                        </p>

                        <p style="line-height: 1.6; margin-top: 15px;">
                           Congratulations on leveling up.
                        </p>

                        <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
