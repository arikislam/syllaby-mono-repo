<?php

namespace App\Syllaby\Assets;

use App\Syllaby\Tags\Tag;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Video;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    use HasFactory;

    /**
     * Get all the tags for the media.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /**
     * Get the download filename for the media.
     */
    public function getDownloadFilename(): string
    {
        $filename = Str::limit($this->name, 100, '', true);

        if (morph_type($this->model_type, Video::class)) {
            $filename = Str::limit($this->model->title, 100, '', true);
        }

        if (morph_type($this->model_type, Asset::class) && $this->collection_name === 'default') {
            $filename = Str::limit($this->model->description ?? $this->name, 100, '', true);
        }

        $filename = Str::trim(preg_replace('/[^\w\s.-]+|\s+/', ' ', Str::ascii($filename)));

        return "{$filename}.{$this->extension}";
    }

    protected static function newFactory(): Factory
    {
        return MediaFactory::new();
    }
}
