<?php

namespace App\Feature\Surveys;

use App\Syllaby\Users\User;
use App\Syllaby\Surveys\Survey;
use App\Syllaby\Surveys\Industry;
use Database\Seeders\SurveyTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurveyTableSeeder::class);
});

it('can display business survey form', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/v1/surveys?slug=business-intake-form')->assertOk();

    expect($response->json('data.0'))->toHaveKeys([
        'id',
        'name',
        'slug',
        'description',
        'is_active',
        'created_at',
    ])->and($response->json('data.0.slug'))->toBe('business-intake-form');
});

it('can display business survey forms along with questions', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->actingAs(
        $user,
        'sanctum'
    )->getJson('/v1/surveys?slug=business-intake-form&include=questions')->assertOk();

    expect($response->json('data.0'))->toHaveKeys([
        'id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at', 'questions',
    ])->and($response->json('data.0.questions.0'))->toHaveKeys([
        'id', 'survey_id', 'title', 'slug', 'type', 'placeholder', 'selected', 'options', 'metadata', 'is_active',
        'created_at', 'updated_at',
    ]);
});

it('can store the answers of the survey', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $industry = Industry::factory()->create();
    $id = Survey::where('slug', 'business-intake-form')->first()->id;

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=business-intake-form', [
        'survey_id' => $id,
        'what_best_describes_your_role' => 'manager',
        'to_which_industry_you_belong_to' => $industry->slug,
        'do_you_use_this_product_as_an_individual_or_agency' => 'agency',
        'company_size' => '500-1000',
        'what_is_the_type_of_the_content_that_you_frequently_post_in_social_media' => [
            'long-form-videos' => 'long-form-videos',
            'user-generated-content' => 'user-generated-content',
        ],
        'what_is_the_duration_of_the_content_that_you_frequently_post_in_social_media' => [
            '30-seconds' => '30-seconds',
            '90-seconds' => '90-seconds',
        ],
        'which_product_interests_you_the_most_in_syllaby' => [
            'digital-twin' => 'digital-twin',
        ],
    ])->assertCreated();

    $this->assertTrue($user->settings['completed_experience_survey']);
    $this->assertDatabaseCount('answers', 7);
    $this->assertDatabaseCount('industry_user', 1);
});

test('survey-id is required for answering questions', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=business-intake-form')->assertJsonValidationErrors([
        'survey_id' => 'The survey id field is required.',
    ]);

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=business-intake-form', [
        'survey_id' => null,
    ])->assertJsonValidationErrors(['survey_id' => 'The survey id field is required.']);

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=business-intake-form', [
        'what_best_describes_your_role' => 'manager',
        'to_which_industry_you_belong_to' => 'finance',
        'do_you_use_this_product_as_an_individual_or_agency' => 'agency',
        'company_size' => '500-1000',
        'what_is_the_type_of_the_content_that_you_frequently_post_in_social_media' => [
            'long-form-videos' => 'long-form-videos',
            'user-generated-content' => 'user-generated-content',
        ],
        'what_is_the_duration_of_the_content_that_you_frequently_post_in_social_media' => [
            '30-seconds' => '30-seconds',
            '90-seconds' => '90-seconds',
        ],
        'which_product_interests_you_the_most_in_syllaby' => [
            'digital-twin' => 'digital-twin',
        ],
    ])->assertJsonValidationErrors(['survey_id' => 'The survey id field is required']);
});

it('user can not skip the survey', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $id = Survey::where('slug', 'business-intake-form')->first()->id;

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=business-intake-form', [
        'survey_id' => $id,
    ])->assertUnprocessable();
});

it('can display a subscription cancellation survey', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/v1/surveys?slug=cancellation-reasoning-form')->assertOk();

    expect($response->json('data.0'))->toHaveKeys([
        'id',
        'name',
        'slug',
        'description',
        'is_active',
        'created_at',
    ])->and($response->json('data.0.slug'))->toBe('cancellation-reasoning-form');
});

it('can store the answers of subscription cancellation survey', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $id = Survey::where('slug', 'cancellation-reasoning-form')->first()->id;

    $this->actingAs($user, 'sanctum')->postJson('/v1/surveys?slug=cancellation-reasoning-form', [
        'survey_id' => $id,
        'financial_concerns' => [
            'im-facing-budget-constraints' => 'im-facing-budget-constraints',
            'the-subscription-cost-is-outside-my-budget' => 'the-subscription-cost-is-outside-my-budget',
        ],
        'content_creation_issues' => [
            'the-ai-generated-content-didnt-resonate-with-my-preferred-voice' => 'the-ai-generated-content-didnt-resonate-with-my-preferred-voice',
        ],
        'usability_technical_challenges' => [
            'the-user-interface-wasnt-intuitive-for-my-content-creation-process' => 'the-user-interface-wasnt-intuitive-for-my-content-creation-process',
            'i-had-difficulties-with-the-direct-posting-feature-on-social-media-platforms' => 'i-had-difficulties-with-the-direct-posting-feature-on-social-media-platforms',
        ],
        'functionality_feature_mismatch' => [
            'the-credit-system-was-unclear-or-didnt-suit-my-usage-patterns' => 'the-credit-system-was-unclear-or-didnt-suit-my-usage-patterns',
        ],
        'personal_external_reasons' => [
            'i-prefer-not-to-specify-a-particular-reason' => 'i-prefer-not-to-specify-a-particular-reason',
        ],
    ])->assertCreated();

    $this->assertDatabaseCount('answers', 5);
});
