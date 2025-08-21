<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>

    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse:collapse">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding: 30px 60px;">
                <!-- Email content -->
                <div style="text-align:left;">
                    <p style="line-height: 1.6; margin-bottom: 25px;">
                        Your Syllaby account has been successfully deleted as per your request.
                    </p>
                    
                    <p style="margin-bottom: 25px; margin-top: 25px; line-height: 1.6;">
                        All of your data, including videos, publications, clones, and other content have been permanently removed from our servers.
                    </p>
                    
                    <p style="margin-bottom: 25px; line-height: 1.6;">
                        Thank you for your time with Syllaby. We're sorry to see you go, but we respect your decision.
                    </p>

                    <p style="line-height: 1.7;">Best, <br> The Syllaby team</p>

                    <p style="color: #5E6278; line-height: 1.6;">
                        For any assistance or further inquiries, feel free to contact us at
                        <br/>
                        <a href="mailto:info@syllaby.io" style="color: #1886FF; font-weight: bold;">
                            info@syllaby.io
                        </a>
                    </p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main> 
