<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Users\Preferences\Notifications;

class NotificationSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'videos.mail' => ['required', 'boolean'],
            'real_clones.mail' => ['required', 'boolean'],
            'publications.mail' => ['required', 'boolean'],
            'scheduler.reminders.mail' => ['required', 'boolean'],
            'scheduler.generated.mail' => ['required', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $defaults = $this->user()->preferences('notifications');

        $this->merge([
            'videos' => ['mail' => $this->resolve('videos.mail', $defaults)],
            'real_clones' => ['mail' => $this->resolve('real_clones.mail', $defaults)],
            'publications' => ['mail' => $this->resolve('publications.mail', $defaults)],
            'scheduler' => [
                'reminders' => ['mail' => $this->resolve('scheduler.reminders.mail', $defaults)],
                'generated' => ['mail' => $this->resolve('scheduler.generated.mail', $defaults)],
            ],
        ]);
    }

    /**
     * Resolve the value for the given key.
     */
    private function resolve(string $key, Notifications $settings): bool
    {
        return $this->boolean($key, $settings->get($key));
    }
}
