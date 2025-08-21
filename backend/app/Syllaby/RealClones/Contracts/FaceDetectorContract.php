<?php

namespace App\Syllaby\RealClones\Contracts;

use Illuminate\Http\UploadedFile;

interface FaceDetectorContract
{
    /**
     * Detects whether a face is present within provided image.
     */
    public function detectFaces(UploadedFile $image): ?array;
}
