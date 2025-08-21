<?php

namespace App\Syllaby\Folders\Listeners;

use App\Syllaby\Users\User;
use Illuminate\Auth\Events\Registered;
use App\Syllaby\Folders\Actions\CreateFolderAction;

class CreateRootFolderListener
{
    public function __construct(protected CreateFolderAction $action) {}

    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->action->handle($user, ['name' => 'Home', 'parent_id' => null]);
    }
}
