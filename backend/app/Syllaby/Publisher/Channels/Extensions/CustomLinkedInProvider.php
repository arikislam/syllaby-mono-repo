<?php

namespace App\Syllaby\Publisher\Channels\Extensions;

use Laravel\Socialite\Two\LinkedInProvider;

class CustomLinkedInProvider extends LinkedInProvider
{
    /**
     * This method is used to fetch the email address of the user which requires special scopes from LinkedIn.
     * Our Community Management API doesn't have that scopes, and we can't request another app due
     * to LinkedIn policy of having only Community Management API in a particular app. That's why we are
     * overriding this method to return an empty array.
     */
    protected function getEmailAddress($token): array
    {
        return [];
    }
}