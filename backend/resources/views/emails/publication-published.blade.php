<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse;">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding: 30px 60px;">
                    <!-- Email content -->
                    <div style="text-align:left;">
                        <p style="line-height: 1.6;">
                            Your video <strong><em>"{{ $title }}"</em></strong> has
                            been published to <strong><em>{{ $channel->account->provider->name }}</em></strong>
                            successfully. Thank you for choosing Syllaby to share your creativity and engaging content.
                        </p>

                        <a href="{{ $url }}" target="_blank" style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px;">
                            View
                        </a>

                        <p style="line-height: 1.7;">Best Regards , <br> Syllaby team</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</x-emails.main>
