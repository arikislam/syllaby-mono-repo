<?php

namespace App\Syllaby\Assets\DTOs;

use Str;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;
use App\Syllaby\Animation\DTOs\AnimationGenerationResponse;

class AssetCreationData
{
    public function __construct(
        public User $user,
        public AssetProvider $provider,
        public string $provider_id,
        public AssetType $type,
        public ?Genre $genre,
        public AssetStatus $status,
        public int $order,
        public ?int $parentId = null,
        public bool $isPrivate = true,
        public ?string $description = null,
        public ?string $model = null,
        public bool $active = false,
        public ?string $name = null,
    ) {}

    public static function forAiImage(Faceless $faceless, ImageGeneratorResponse $response, int $order, bool $active = false): self
    {
        return new self(
            user: $faceless->user,
            provider: AssetProvider::from($response->provider),
            provider_id: $response->id,
            type: AssetType::AI_IMAGE,
            genre: $faceless->genre,
            status: AssetStatus::PROCESSING,
            order: $order,
            isPrivate: true,
            description: $response->description,
            model: $response->model,
            active: $active,
        );
    }

    public static function forAiVideo(Faceless $faceless, Asset $parent, AnimationGenerationResponse $data, int $order): self
    {
        return new self(
            user: $faceless->user,
            provider: AssetProvider::MINIMAX,
            provider_id: $data->id,
            type: AssetType::AI_VIDEO,
            genre: $faceless->genre,
            status: AssetStatus::PROCESSING,
            order: $order,
            parentId: $parent->id,
            isPrivate: true,
            description: $data->description,
            model: $data->model,
            active: true,
        );
    }

    public static function forCustomMedia(Faceless $faceless, UploadedFile $file, int $order, bool $active = false): self
    {
        $type = AssetType::fromMime($file->getMimeType(), 'custom');

        return new self(
            user: $faceless->user,
            provider: AssetProvider::CUSTOM,
            provider_id: Str::uuid(),
            type: $type,
            genre: $faceless->genre,
            status: AssetStatus::SUCCESS,
            order: $order,
            isPrivate: true,
            active: $active,
        );
    }

    public function success(): self
    {
        $this->status = AssetStatus::SUCCESS;

        return $this;
    }

    public static function forStockMedia(Faceless $faceless, string $mime, int $order, bool $active = false): self
    {
        $type = AssetType::fromMime($mime);

        return new self(
            user: $faceless->user,
            provider: AssetProvider::PEXELS,
            provider_id: Str::uuid(),
            type: $type,
            genre: $faceless->genre,
            status: AssetStatus::SUCCESS,
            order: $order,
            isPrivate: true,
            active: $active,
        );
    }

    public static function forScrapedMedia(Faceless $faceless, string $mime, int $order, bool $active = false): self
    {
        $type = AssetType::fromMime($mime, AssetType::SCRAPED->value);

        return new self(
            user: $faceless->user,
            provider: AssetProvider::CUSTOM,
            provider_id: Str::uuid(),
            type: $type,
            genre: $faceless->genre,
            status: AssetStatus::SUCCESS,
            order: $order,
            isPrivate: true,
            active: $active,
        );
    }

    public static function forStandaloneUpload(User $user, AssetType $type, ?string $name = null, ?Genre $genre = null): self
    {
        return new self(
            user: $user,
            provider: AssetProvider::CUSTOM,
            provider_id: Str::uuid(),
            type: $type,
            genre: $genre,
            status: AssetStatus::SUCCESS,
            order: 0,
            isPrivate: true,
            active: true,
            name: $name,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user->id,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'type' => $this->type,
            'genre' => $this->genre->id,
            'status' => $this->status,
            'order' => $this->order,
            'parent_id' => $this->parentId,
            'is_private' => $this->isPrivate,
            'active' => $this->active,
        ];
    }
}
