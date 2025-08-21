<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>
    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse:collapse;">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <!-- Email content -->
                <div style="text-align: left;">
                    <p style="line-height: 1.6;">
                        We're thrilled to have you on board as part of our Syllaby creative community.
                        Get ready to revolutionize your social media content creation with the power of artificial
                        intelligence
                    </p>

                    <p style="line-height: 1.6;">
                        <strong>Not sure where to start?</strong>
                        We'll show you how Syllaby works. Watch the video below:
                    </p>
                </div>

                <div style="text-align: center;">
                    <a href="{{ $url }}" target="_blank"
                       style="text-decoration: none; padding: 15px 20px; display: block; ">
                        <x-emails.image imageName="{{ $thumbnail }}" alt="Welcome to Syllaby Intro Video" />
                        </a>
                    </div>

                    <div style="text-align: left;">
                        <p style="line-height: 1.7;">
                            Best Regards, <br>
                            Austin Armstrong, CEO
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
