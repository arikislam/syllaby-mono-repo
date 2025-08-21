<x-emails.main>
    <x-emails.banner name="Syllaby"/>

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
           style="border-collapse: collapse;">
        <tbody>
        <tr>
            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px; padding: 30px 60px;">
                <div style="text-align:left;">

                    <p style="line-height: 1.6;">
                        <strong>{{ $user->name }}</strong> purchased a real clone and is
                        now waiting for a review.
                    </p>

                    <p style="line-height: 1.6;">
                        Request details:
                    </p>

                    <p style="line-height: 1.4;">
                        <span>
                            <strong>Clone Intent ID:</strong> {{ $clone_intent_id }}
                        </span>

                        <br>
                        <span>
                            <strong>User email:</strong> {{ $user->email }}
                        </span>

                        <br>
                        <span>
                            <strong>Sample Url:</strong>
                            <a href="{{ $details['url'] }}" target="_blank">{{ $details['url'] }}</a>
                        </span>
                    </p>

                    <p style="line-height: 1.6;">
                        Please give the user extra details if necessary.
                    </p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</x-emails.main>
