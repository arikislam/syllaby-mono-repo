<?php

namespace App\Syllaby\Credits\Enums;

use InvalidArgumentException;

enum CreditEventEnum: string
{
    case SUBSCRIBE_TO_TRIAL = 'subscribe_to_trial';
    case SUBSCRIPTION_AMOUNT_PAID = 'subscription_amount_paid';
    case IDEA_DISCOVERED = 'idea_discovered';
    case CONTENT_PROMPT_REQUESTED = 'content_prompt_requested';
    case ADMIN_CREDIT_ADDED = 'admin_credit_added';
    case CUSTOM_CREDIT_PURCHASED = 'custom_credit_purchased';
    case VIDEO_GENERATED = 'video_generated';
    case TEXT_TO_SPEECH_GENERATED = 'text_to_speech_generated';
    case REAL_CLONE_GENERATED = 'real_clone_generated';
    case SINGLE_AI_IMAGE_GENERATED = 'ai_image_generated';
    case BULK_AI_IMAGES_GENERATED = 'bulk_ai_images_generated';
    case FACELESS_VIDEO_GENERATED = 'faceless_video_generated';
    case FACELESS_VIDEO_EXPORTED = 'faceless_video_exported';
    case REFUNDED_CREDITS = 'refunded_credits';
    case PRORATED_CREDITS = 'prorated_credits';
    case REAL_CLONE_AND_TEXT_TO_SPEECH = 'real_clone_and_text_to_speech';
    case MONTHLY_CREDITS_ADDED = 'monthly_credits_added';
    case IMAGE_ANIMATED = 'image_animated';
    case AUDIO_TRANSCRIPTION = 'audio_transcription';
    case CUSTOM_CHARACTER_PURCHASED = 'custom_character_purchased';

    /**
     * Gets the credit event with the given name.
     */
    public static function find(string $name): CreditEventEnum
    {
        foreach (self::cases() as $event) {
            if ($name === $event->value) {
                return $event;
            }
        }

        throw new InvalidArgumentException("Invalid credit event: {$name}");
    }

    public static function primary(string $name): bool
    {
        return self::from($name) !== self::CUSTOM_CREDIT_PURCHASED;
    }

    /**
     * Description associated with a credit event.
     */
    public function details(): string
    {
        return match ($this) {
            self::SUBSCRIBE_TO_TRIAL => 'Subscription trial credits',
            self::SUBSCRIPTION_AMOUNT_PAID => 'Subscription amount paid',
            self::IDEA_DISCOVERED => 'Requested a new content idea',
            self::CONTENT_PROMPT_REQUESTED => 'Generated new contents',
            self::ADMIN_CREDIT_ADDED => 'Credit added by admin',
            self::CUSTOM_CREDIT_PURCHASED => 'Purchased Credit',
            self::REFUNDED_CREDITS => 'Credits refunded to user',
            self::TEXT_TO_SPEECH_GENERATED => 'Text to Speech generated',
            self::REAL_CLONE_GENERATED => 'Real clone and speech generated',
            self::FACELESS_VIDEO_GENERATED => 'Faceless video generated',
            self::FACELESS_VIDEO_EXPORTED => 'Faceless video exported',
            self::SINGLE_AI_IMAGE_GENERATED => 'AI image generated',
            self::BULK_AI_IMAGES_GENERATED => 'AI Images generated',
            self::PRORATED_CREDITS => 'Prorated Credits',
            self::REAL_CLONE_AND_TEXT_TO_SPEECH => 'Real clone and text-to-speech bundle',
            self::MONTHLY_CREDITS_ADDED => 'Release of monthly credits',
            self::VIDEO_GENERATED => 'Video generated',
            self::IMAGE_ANIMATED => 'Image animated',
            self::AUDIO_TRANSCRIPTION => 'Audio transcription',
            self::CUSTOM_CHARACTER_PURCHASED => 'Purchased custom character',
        };
    }
}
