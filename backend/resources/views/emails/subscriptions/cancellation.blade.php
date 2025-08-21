<x-emails.main>
    <x-emails.banner name="{{ $user->name }}"/>
    <!-- Email content -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <!-- Email content -->
                <div style="text-align:left;">
                    <p style="line-height: 1.6;">
                        We're sorry to see you go! Your subscription has been successfully cancelled and will 
                        remain active until the end of your current billing cycle. You can continue enjoying 
                        all features and accessing your account as usual until then.
                    </p>


                    @if($hasOffer)
                        <p style="line-height: 1.6;">
                            As a valued customer, we want to show our appreciation, we're offering you an exclusive
                            deal:
                        </p>

                        <table>
                            <tbody>
                            <tr>
                                <td>
                                    <x-emails.image imageName="gift.png"/>
                                </td>
                                <td>
                                    <p style="color: #854EFF; font-size: 25px; line-height: 1.3; margin-left: 10px">
                                        Get a whopping 50% off on our <br> three-month plan!
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <p style="margin-bottom: 25px;">
                            Redeem this exclusive offer and enjoy our ever-growing creative tools
                        </p>

                        <a href="{{ $subscriptionsPageUrl }}" target="_blank" style="text-decoration: none; background: #1886FF; color: #fff; padding: 15px 20px; display: inline-block; border-radius: 6px;">
                            Redeem
                        </a>
                    @endif

                    <p style="line-height: 1.6; margin-top: 15px">We hope to welcome you back soon!</p>
                    <p style="line-height: 1.7;">Best, <br> Austin Armstrong, CEO</p>
                </div>
            </td>
        </tr>
        
        @if($hasOffer)
            <tr>
                <td style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                    **Offer valid until {{ $endDate }}**
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</x-emails.main>
