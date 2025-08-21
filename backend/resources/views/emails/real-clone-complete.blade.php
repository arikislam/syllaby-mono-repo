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
                        Great news! Your video about <b>{{ $title }}</b> is now ready for you to
                        enjoy.
                    </p>

                    <p style="line-height: 1.6;">
                        Start editing and sharing your video with the world! With our own editor you'll be able
                        to edit your videos seamlessly within our ecosystem, unlocking endless creative possibilities.
                    </p>

                    <a href="{{ $editor_url }}" target="_blank"
                       style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px; ">
                        Edit Video
                    </a>

                    <p style="line-height: 1.7;">Best Regards , <br> Syllaby team</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
