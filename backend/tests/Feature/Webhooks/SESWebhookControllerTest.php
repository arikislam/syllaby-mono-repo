<?php

namespace Tests\Feature\Webhooks;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('handles and records email bounces received from SES', function () {
    Carbon::setTestNow(Carbon::now());

    $response = $this->postJson('/ses/webhook', [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'feedbackId' => 'test-feedback-id',
                'bouncedRecipients' => [
                    ['emailAddress' => 'test@example.com', 'diagnosticCode' => 'test-diagnostic-code'],
                ],
            ],
        ]),
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('suppressions', [
        'email' => 'test@example.com',
        'bounce_type' => 'Permanent',
        'bounced_at' => now(),
        'reason' => 'test-diagnostic-code',
        'trace_id' => 'test-feedback-id',
    ]);
});

it('handles and records email complaints received from SES', function () {
    Carbon::setTestNow(Carbon::now());

    $response = $this->postJson('/ses/webhook', [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Complaint',
            'complaint' => [
                'complaintFeedbackType' => 'abuse',
                'feedbackId' => 'test-feedback-id',
                'complainedRecipients' => [
                    ['emailAddress' => 'test@example.com'],
                ],
            ],
        ]),
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('suppressions', [
        'email' => 'test@example.com',
        'complained_at' => now()->toDateTimeString(),
        'trace_id' => 'test-feedback-id',
    ]);
});
