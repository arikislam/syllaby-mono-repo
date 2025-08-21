<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse: collapse;">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            We've successfully updated your plan to Syllaby's <strong>{{ $plan->name }}</strong>.
                            We understand that needs change, and we're committed to providing a solution that
                            aligns with your current requirements.
                        </p>

                        <p style="line-height: 1.6;">
                            You still have access to all the features with:
                        </p>

                        <p style="line-height: 1.4;">
                            <span>📦 A bit less storage</span>
                            <br>
                            <span>📅 Fewer scheduled posts</span>
                            <br>
                            <span>📊 Your credits will only be adjusted when the next billing cycle starts</span>
                        </p>

                        <p style="line-height: 1.6;">
                            Your success continues to be our priority, and we're here to support you every step of the way.
                            If you have any questions or need assistance with your new plan, please don't
                            hesitate to reach out to us at
                            <a href="mailto:customerrequest@syllaby.io">customerrequest@syllaby.io</a>
                        </p>

                        <p style="line-height: 1.6; margin-top: 15px;">
                           Thank you for continuing your creative journey with Syllaby.
                        </p>

                        <p style="line-height: 1.7;">Regards, <br> The Syllaby Team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
