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
                        We're thrilled to welcome you to Syllaby! Your {{ $trialDays }}-day free trial has officially begun, and you now have 
                        full access to all the powerful tools and features that make content creation a breeze!
                    </p>

                    <p style="line-height: 1.4;">
                        <strong style="font-size: 18px;">Here's everything you need to get started:</strong>

                        <ul style="padding-left: 22px;">
                            <li style="margin-bottom: 10px;">
                                💡 <a href="https://syllaby.featurebase.app/help" target="_blank"><strong> Help Center</strong></a>:
                                Your go-to guide for tips, tricks, and troubleshooting.
                            </li>
                            <li style="margin-bottom: 10px;">
                                🎥 <a href="https://www.youtube.com/@syllaby" target="_blank"><strong> Tutorial and Webinar Videos</strong></a>:
                                Step-by-step guides and in-depth webinars to help you master Syllaby's powerful features.
                            </li>
                            <li style="margin-bottom: 10px;">
                                🤝 <a href="https://www.facebook.com/groups/syllaby" target="_blank"><strong> Join Our Community</strong></a>:
                                Connect with other creators in our Facebook group for free tips, resources, real user stories, 
                                and exclusive offers.
                            </li>
                            <li style="margin-bottom: 10px;">
                                📖 <a href="https://syllaby.io/case-studies/" target="_blank"><strong> Explore Case Studies and Inspiration</strong></a>:
                                See how others have used Syllaby to achieve incredible results and get inspired to start your own journey.
                            </li>
                            <li>
                                📅 <a href="https://calendly.com/syllaby/syllaby-demo-support-call" target="_blank"><strong> Schedule a 1:1 Demo</strong></a>:
                                Need personalized help? Book a demo call with one of our experts to see Syllaby in action.
                            </li>
                        </ul>
                    </p>

                    <p style="line-height: 1.4;">
                        <strong style="font-size: 18px;">What's Next?</strong>
                        <p>
                            <span style="display: block; margin-bottom: 4px;">
                                Once you're ready, here are two great ways to start creating:
                            </span>

                            <span style="display: block; margin-bottom: 4px;">
                                1️⃣ <a href="https://ai.syllaby.io/faceless-video" target="_blank"><strong>Create your first faceless video</strong></a> 
                                — it's fast, fun, and simple!
                            </span>

                            <span style="display: block; margin-bottom: 4px;">
                                2️⃣ <a href="https://ai.syllaby.io/bulk-creater/scheduler" target="_blank"><strong>Create your first bulk schedule</strong></a> 
                                — see how to plan a month's worth of content in under 30 minutes!
                            </span>
                        </p>
                    </p>
                    
                    <p style="line-height: 1.4;">
                        <strong style="font-size: 18px;"> Your Plan Details:</strong>

                        <ul style="padding-left: 22px;">
                            <li style="margin-bottom: 4px;">
                                <strong>Plan:</strong> {{ $plan->name }} at {{ $invoice['amount'] }}
                            </li>
                            @if($user->onTrial())
                                <li style="margin-bottom: 4px;">
                                    <strong>Trial End Date:</strong> {{ $subscription->trial_ends_at->format('m-d-Y') }}
                                </li>
                            @endif
                            <li style="margin-bottom: 4px;">
                                <strong>Subscription Start Date:</strong> {{ $invoice['date'] }}
                            </li>
                        </ul>
                    </p>

                    <p style="line-height: 1.4;">
                        Your <strong>subscription will begin automatically</strong> once the trial ends, but no need to worry — 
                        we'll send you a reminder three days in advance.
                    </p>

                    <p style="line-height: 1.4;">
                        If you need any help, just send us a message at 
                        <a href="mailto:customerrequest@syllaby.io">customerrequest@syllaby.io</a>.
                    </p>
                    
                    <p style="line-height: 1.4;">
                        Your video marketing success starts here! 🚀
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
