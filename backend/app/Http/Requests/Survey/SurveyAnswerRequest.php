<?php

namespace App\Http\Requests\Survey;

use App\Syllaby\Surveys\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Collection;

class SurveyAnswerRequest extends FormRequest
{
    public Collection $questions;

    public function rules(): array
    {
        if (blank($this->questions = $this->getSurveyQuestions())) {
            return ['survey_id' => 'required'];
        }

        $rules = $this->questions->reduce(fn ($rules, $question) => [
            ...$rules,
            $question->slug => $question->rules,
        ], []);

        return [...$rules, 'survey_id' => 'required'];
    }

    private function getSurveyQuestions(): Collection
    {
        return Question::active()->where('survey_id', $this->input('survey_id'))->get();
    }
}
