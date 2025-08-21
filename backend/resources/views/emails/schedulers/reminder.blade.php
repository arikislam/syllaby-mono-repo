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
                        Just a quick reminder that your video, {{ $video->title }}, is scheduled to 
                        publish at {{ $event->starts_at->format('g:i A') }}. 
                        If you’d like to add a description or update the thumbnail before it goes live, you can make 
                        those edits now.
                    </p>

                    <a href="{{ $url }}" target="_blank"
                       style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px; ">
                        Edit Publication Details
                    </a>

                    <p style="line-height: 1.7;">Best Regards , <br> Syllaby team</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
