<?php

namespace App\Syllaby\Surveys\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Surveys\Answer;
use App\Syllaby\Surveys\Survey;
use App\Syllaby\Surveys\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Auth\Authenticatable;

class SurveyAnswerAction
{
    public function handle(Collection $questions, User $user, array $input): Survey
    {
        $survey = Survey::find(Arr::get($input, 'survey_id'));

        if ($this->alreadyAnswered($user)) {
            return $survey;
        }

        $answers = $questions->map(fn ($question) => [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'body' => $this->buildAnswerBodyFor($question, $input),
            'type' => $this->multipleChoice($question->type) ? Answer::ANSWER_TYPE_ARRAY : Answer::ANSWER_TYPE_TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        return tap($survey, fn ($survey) => Answer::insert($answers));
    }

    private function buildAnswerBodyFor(Question $question, array $input): string|null
    {
        return match ($question->type) {
            Question::TYPE_TAGS => json_encode(explode(',', Arr::get($input, $question->slug, ''))),
            Question::TYPE_CHECKBOX => json_encode(array_keys(Arr::get($input, $question->slug, []))),
            default => Arr::get($input, $question->slug),
        };
    }

    private function multipleChoice(string $type): bool
    {
        return in_array($type, [Question::TYPE_TAGS, Question::TYPE_CHECKBOX]);
    }

    public function markAsCompleted(User|Authenticatable $user): void
    {
        $settings = tap($user->preferences(), fn ($settings) => $settings->set('completed_experience_survey', true));

        $user->update(['settings' => $settings->all()]);
    }

    public function alreadyAnswered(User $user): bool
    {
        return !blank($user->answers) && $user->answers()->whereHas('question', function ($q) {
                return $q->where('survey_id', request()->get('survey_id'));
            })->count() > 0;
    }
}
