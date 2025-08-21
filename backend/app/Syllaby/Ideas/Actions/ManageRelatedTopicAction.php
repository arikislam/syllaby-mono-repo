<?php

namespace App\Syllaby\Ideas\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Ideas\Topic;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\RelatedTopicPrompt;

class ManageRelatedTopicAction
{
    /**
     * Create or retrieve a related topic for a user.
     */
    public function handle(User $user, array $data): Topic
    {
        $language = Arr::get($data, 'language');
        $title = Str::limit(Arr::get($data, 'title'), 250, '', true);

        if ($topic = $this->fetchTopic($user, $title, $language)) {
            return $topic;
        }

        $response = Chat::driver('gpt')->send(
            message: RelatedTopicPrompt::build($title, $language),
            config: new ChatConfig(responseFormat: config('openai.json_schemas.related-topics'))
        );

        if (! json_validate($response->text)) {
            throw new Exception("Could not retrieve related topics for {$title}");
        }

        return Topic::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => 'video',
            'provider' => 'gpt',
            'language' => $language,
            'ideas' => Arr::get(json_decode($response->text, true), 'topics', []),
        ]);
    }

    /**
     * Find an existing topic for the user with the given title.
     */
    private function fetchTopic(User $user, ?string $title, ?string $language = null): ?Topic
    {
        return Topic::where('user_id', $user->id)
            ->where('hash', md5($title))
            ->where('language', $language)
            ->first();
    }
}
