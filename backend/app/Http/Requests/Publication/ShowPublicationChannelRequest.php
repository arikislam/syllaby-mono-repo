<?php

namespace App\Http\Requests\Publication;

use DB;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

class ShowPublicationChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('publication')) && $this->user()->can('view', $this->route('channel'));
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->recordExists()) {
                    $validator->errors()->add('id', 'The requested data could not be found.');
                }
            },
            function (Validator $validator) {
                if ($this->filtersApplied() && ! $this->wantMetrics()) {
                    $validator->errors()->add('include', 'The include parameter in query string is missing');
                }
            },
            function (Validator $validator) {
                if ($this->isUnpublished()) {
                    $validator->errors()->add('id', 'Access to analytics is available only for successfully published posts.');
                }
            },
        ];
    }

    private function recordExists(): bool
    {
        return DB::table('account_publications')
            ->where('publication_id', $this->route('publication')->id)
            ->where('social_channel_id', $this->route('channel')->id)
            ->exists();
    }

    private function isUnpublished(): bool
    {
        if (!$this->wantMetrics()) {
            return false;
        }

        return DB::table('account_publications')
            ->where('publication_id', $this->route('publication')->id)
            ->where('social_channel_id', $this->route('channel')->id)
            ->whereIn('status', SocialUploadStatus::unpublished())
            ->exists();
    }

    private function wantMetrics(): bool
    {
        return $this->query('include') && in_array('metrics', explode(',', $this->query('include')));
    }

    private function filtersApplied(): bool
    {
        return $this->query('filter') && in_array('date', array_keys($this->query('filter')));
    }
}
