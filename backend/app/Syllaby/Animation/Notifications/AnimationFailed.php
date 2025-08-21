<?php

namespace App\Syllaby\Animation\Notifications;

use App\System\Notification;
use Illuminate\Bus\Queueable;
use App\Syllaby\Videos\Faceless;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnimationFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Faceless $faceless)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'motion-failed';
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'faceless_id' => $this->faceless->id,
            'video_id' => $this->faceless->video_id,
            'video_title' => $this->faceless->video->title,
        ];
    }
}
