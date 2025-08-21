<?php

namespace App\Http\Controllers\Api\v1\Templates;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Syllaby\Templates\Template;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\TemplateResource;
use App\Syllaby\Templates\Filters\TemplateTagFilter;

class TemplateController extends Controller
{
    /**
     * Allowed relationships includes
     */
    protected array $includes = [
        'tags', 'media', 'user',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a list of filtered templates.
     */
    public function index(Request $request): JsonResponse
    {
        $templates = QueryBuilder::for(Template::class)->allowedFilters([
            AllowedFilter::exact('type'),
            AllowedFilter::scope('active')->default(true),
            AllowedFilter::custom('tag', new TemplateTagFilter),
            AllowedFilter::callback('ratio', fn ($query, $value) => $query->where('metadata->aspect_ratio', $value)),
        ])
            ->allowedIncludes($this->includes)
            ->ownedBy($this->user())
            ->paginate($this->take());

        return $this->respondWithPagination(TemplateResource::collection($templates));
    }

    /**
     * Display the template with the given id.
     */
    public function show(int $id): JsonResponse
    {
        $template = QueryBuilder::for(Template::class)
            ->allowedIncludes($this->includes)
            ->ownedBy($this->user())
            ->where('id', $id)
            ->first();

        if (blank($template)) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithResource(TemplateResource::make($template));
    }
}
