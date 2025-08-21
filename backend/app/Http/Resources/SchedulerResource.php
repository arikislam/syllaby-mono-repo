<?php

namespace App\Http\Resources;

use RRule\RRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Scheduler */
class SchedulerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'idea_id' => $this->idea_id,
            'title' => $this->title,
            'color' => $this->color,
            'topic' => $this->topic,
            'status' => $this->status,
            'type' => $this->type,
            'source' => $this->source,
            'options' => $this->options,
            'details' => $this->details(),
            'character_id' => $this->character_id,

            'metadata' => [
                'ai_labels' => Arr::get($this->metadata, 'ai_labels', false),
                'custom_description' => Arr::get($this->metadata, 'custom_description'),
            ],

            'paused_at' => $this->paused_at?->toJSON(),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),

            'voice' => VoiceResource::make($this->whenLoaded('voice')),
            'music' => MediaResource::make($this->whenLoaded('music')),
            'character' => CharacterResource::make($this->whenLoaded('character')),
            'events' => EventResource::collection($this->whenLoaded('events')),
            'videos' => VideoResource::collection($this->whenLoaded('videos')),
            'social_channels' => SocialChannelResource::collection($this->whenLoaded('channels')),
            'occurrences' => $this->whenLoaded('occurrences', fn ($occurrences) => $this->occurrences($occurrences)),
        ];
    }

    /**
     * Get the hours from the details array.
     */
    private function details(): array
    {
        if (empty($this->rrules)) {
            return [];
        }

        $dates = collect($this->resource->rdates());

        $hours = $dates->map(fn ($date) => $date->format('H:i'))->unique()->toArray();
        $occurrences = $dates->map(fn ($date) => $date->format('Y-m-d'))->unique()->toArray();
        $rule = (new RRule(Arr::first($this->rrules)))->getRule();

        return [
            'hours' => $hours,
            'days' => count($occurrences),
            'occurrences' => $occurrences,
            'times_per_day' => count($hours),
            'start_date' => Arr::get($rule, 'DTSTART'),
            'weekdays' => Arr::get($rule, 'BYDAY'),
        ];
    }

    /**
     * Get the occurrences.
     */
    private function occurrences($occurrences): Collection
    {
        return $occurrences->groupBy(function ($occurrence) {
            return $occurrence->occurs_at->toDateString();
        })->map(function ($occurrences) {
            return OccurrenceResource::collection($occurrences);
        });
    }
}
