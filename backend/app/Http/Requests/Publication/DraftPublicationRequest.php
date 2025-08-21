<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Enums\TikTokPrivacyStatus;
use App\Syllaby\Publisher\Publications\Enums\YoutubePrivacyStatus;

class DraftPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $publication = Publication::query()->find($this->integer('publication_id'));

        return $this->user()->can('update', $publication);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', Rule::exists('publications', 'id')->where('user_id', auth('sanctum')->id())],
            'scheduled_at' => ['sometimes'],

            'youtube' => ['sometimes', 'array'],
            'youtube.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::Youtube->toString())],
            'youtube.*.title' => ['sometimes', 'string', 'max:255'],
            'youtube.*.description' => ['sometimes', 'string'],
            'youtube.*.privacy_status' => ['sometimes', Rule::in(YoutubePrivacyStatus::values())],
            'youtube.*.category' => ['sometimes', 'int'],
            'youtube.*.tags' => ['sometimes', 'array'],
            'youtube.*.tags.*' => ['sometimes', 'string', 'max:255'],
            'youtube.*.license' => ['sometimes', 'string', Rule::in('creativeCommon', 'youtube')],
            'youtube.*.embeddable' => ['sometimes', 'boolean'],
            'youtube.*.notify_subscribers' => ['sometimes', 'boolean'],
            'youtube.*.made_for_kids' => ['sometimes', 'boolean'],

            'tiktok' => ['sometimes', 'array'],
            'tiktok.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::TikTok->toString())],
            'tiktok.*.caption' => ['sometimes', 'string', 'max:2200'],
            'tiktok.*.allow_comments' => ['sometimes', 'boolean'],
            'tiktok.*.allow_duet' => ['sometimes', 'boolean'],
            'tiktok.*.allow_stitch' => ['sometimes', 'boolean'],
            'tiktok.*.privacy_status' => ['sometimes', Rule::in(TikTokPrivacyStatus::values())],

            'linkedin' => ['sometimes', 'array'],
            'linkedin.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::LinkedIn->toString())],
            'linkedin.*.visibility' => ['sometimes', 'string', Rule::in('CONNECTIONS', 'PUBLIC')],
            'linkedin.*.title' => ['sometimes', 'string', 'max:255'],
            'linkedin.*.caption' => ['sometimes', 'string', 'max:2200'],

            'facebook' => ['sometimes', 'array'],
            'facebook.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::Facebook->toString())],
            'facebook.*.post_type' => ['required', 'string', Rule::in(PostType::facebook())],
            'facebook.*.caption' => ['sometimes', 'string', 'max:5000'],
            'facebook.*.title' => ['sometimes', 'string', 'max:255'],

            'instagram' => ['sometimes', 'array'],
            'instagram.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::Instagram->toString())],
            'instagram.*.post_type' => ['required', 'string', Rule::in(PostType::instagram())],
            'instagram.*.caption' => ['sometimes', 'string', 'max:2200'],
            'instagram.*.share_to_feed' => ['sometimes', 'boolean'],

            'threads' => ['sometimes', 'array'],
            'threads.*.channel_id' => ['sometimes', 'integer', new ValidSocialChannel(SocialAccountEnum::Threads->toString())],
            'threads.*.caption' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'youtube.*.title.max' => 'YouTube title must not exceed 255 characters.',
            'youtube.*.privacy_status.in' => 'Please select a valid YouTube privacy status.',
            'youtube.*.tags.*.max' => 'Each YouTube tag must not exceed 255 characters.',
            'youtube.*.license.in' => 'Please select a valid YouTube license type.',

            'tiktok.*.caption.max' => 'TikTok caption must not exceed 2200 characters.',
            'tiktok.*.privacy_status.in' => 'Please select a valid TikTok privacy status.',

            'linkedin.*.visibility.in' => 'Please select a valid LinkedIn visibility option.',
            'linkedin.*.title.max' => 'LinkedIn title must not exceed 255 characters.',
            'linkedin.*.caption.max' => 'LinkedIn caption must not exceed 2200 characters.',

            'facebook.*.post_type.required' => 'Please select a post type for Facebook.',
            'facebook.*.post_type.in' => 'Please select a valid Facebook post type.',
            'facebook.*.caption.max' => 'Facebook caption must not exceed 5000 characters.',
            'facebook.*.title.max' => 'Facebook title must not exceed 255 characters.',

            'instagram.*.post_type.required' => 'Please select a post type for Instagram.',
            'instagram.*.post_type.in' => 'Please select a valid Instagram post type.',
            'instagram.*.caption.max' => 'Instagram caption must not exceed 2200 characters.',

            'threads.*.caption.max' => 'Threads caption must not exceed 500 characters.',
        ];
    }
}
