<?php

namespace App\Http\Requests\Publication;

use App\Syllaby\Assets\Media;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Http\Requests\Assets\UploadMediaRequest;

class CreatePublicationRequest extends UploadMediaRequest
{
    public function authorize(Gate $gate): Response
    {
        if ($this->has('files') && $this->missing('video_id') && $this->file('files')) {
            $size = array_reduce($this->file('files'), fn($total, $file) => $total + $file->getSize());
            return $gate->inspect('upload', [Media::class, $size]);
        }

        return Response::allow();
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['nullable', 'string', 'max:255'],
            'files' => ['required_without:video_id', 'array'],
            'video_id' => ['required_without:files', 'int', Rule::exists('videos', 'id')],
        ]);
    }
}
