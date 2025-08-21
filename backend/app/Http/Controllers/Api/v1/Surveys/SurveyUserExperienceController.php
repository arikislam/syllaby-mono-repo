<?php

namespace App\Http\Controllers\Api\v1\Surveys;

use Illuminate\Http\Request;
use App\Syllaby\Surveys\Survey;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\SurveyResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Survey\SurveyAnswerRequest;
use App\Syllaby\Surveys\Actions\SurveyAnswerAction;
use App\Syllaby\Surveys\Events\BusinessIntakeFormAnswered;

class SurveyUserExperienceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $slug = $request->query('slug', 'business-intake-form');

        $surveys = QueryBuilder::for(Survey::class)
            ->where('slug', $slug)
            ->allowedIncludes(['questions', 'questions.answers'])
            ->get();

        return $this->respondWithResource(SurveyResource::collection($surveys));
    }

    public function store(SurveyAnswerRequest $request, SurveyAnswerAction $action): JsonResponse
    {
        $user = $this->user();

        if (blank($request->all())) {
            $action->markAsCompleted($user);

            return $this->respondWithMessage('Survey successfully answered', 201);
        }

        if (!$survey = $action->handle($request->questions, $user, $request->validated())) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again.');
        }

        $action->markAsCompleted($user);

        if ($survey->slug === 'business-intake-form') {
            $industry = $request->validated('to_which_industry_you_belong_to');
            event(new BusinessIntakeFormAnswered($user, $industry));
        }

        return $this->respondWithResource(new SurveyResource(
            $survey->load(['questions', 'questions.answers' => fn ($query) => $query->where('user_id', $user->id)])
        ), Response::HTTP_CREATED);
    }
}
