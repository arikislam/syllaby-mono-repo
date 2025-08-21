<?php

namespace App\Http\Requests\Assets;

class UploadAudioRequest extends UploadMediaRequest
{
    protected array $types = [
        'mp3', 'wav',
    ];
}
