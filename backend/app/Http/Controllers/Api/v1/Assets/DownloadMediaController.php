<?php

namespace App\Http\Controllers\Api\v1\Assets;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Media;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Assets\Jobs\BundleCompressedMedia;

class DownloadMediaController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Download media file by UUID.
     */
    public function download(string $uuid): string|JsonResponse
    {
        if (! $media = Media::where('uuid', $uuid)->where('user_id', $this->user()->id)->first()) {
            return $this->errorNotFound('Media file not found');
        }

        return response()->json(['url' => $media->getTemporaryUrl(expiration: now()->addHours(4), options: [
            'ResponseContentDisposition' => "attachment; filename={$media->getDownloadFilename()}",
            'ResponseCacheControl' => 'public, max-age=3600',
            'ResponseContentType' => $media->mime_type,
            'ResponseExpires' => '0',
        ])]);
    }

    /**
     * Bulk download media files by UUIDs.
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['required', 'uuid'],
        ]);

        dispatch(new BundleCompressedMedia($this->user(), Arr::get($validated, 'uuids')));

        return $this->respondWithMessage('Files being processed for download.');
    }
}
