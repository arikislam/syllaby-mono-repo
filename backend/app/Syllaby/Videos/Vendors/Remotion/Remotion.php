<?php

namespace App\Syllaby\Videos\Vendors\Remotion;

use Exception;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Aws\Credentials\Credentials;
use Remotion\LambdaPhp\PHPClient;
use Remotion\LambdaPhp\RenderParams;
use Aws\Credentials\CredentialProvider;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;

class Remotion
{
    private const int MAX_CONCURRENT_LAMBDAS = 100;

    private const int MAX_FRAMES_PER_LAMBDA = 300;

    private const int MIN_FRAMES_PER_LAMBDA = 30;

    /**
     * Render the faceless video source.
     */
    public function render(array $timeline, array $options = []): array
    {
        $lambda = $this->lambda();

        $params = new RenderParams;
        $params->setComposition('faceless-composition');

        $source = (new Transpiler)->handle($timeline, $options['type']);

        $frames = $this->getFramesFromSeconds($source);

        $params->setFramesPerLambda(
            $this->calculateFramesPerLambda($frames, self::MAX_CONCURRENT_LAMBDAS)
        );

        $params->setInputProps($source);

        $params->setWebhook([
            'url' => config('services.remotion.webhook_url'),
            'customData' => Arr::only($options, 'faceless_id'),
            'secret' => null,
        ]);

        $response = $lambda->renderMediaOnLambda($params);

        if ($response->type === 'error') {
            throw new Exception("Error rendering video: {$response->renderId}");
        }

        return [
            'provider_id' => $response->renderId,
            'status' => VideoStatus::RENDERING->value,
            'provider' => VideoProvider::REMOTION->value,
            'url' => Storage::disk('s3')->url("renders/{$response->renderId}/out.mp4"),
        ];
    }

    /**
     * Get the number of frames from the source.
     */
    private function getFramesFromSeconds(array $source): int
    {
        $duration = Arr::get($source, 'sourceElements.duration');
        $fps = Arr::get($source, 'sourceElements.frameRate');

        return ceil($duration * $fps);
    }

    /**
     * Calculate the number of frames per Lambda.
     */
    private function calculateFramesPerLambda(int $frames, int $concurrencyLimit = 4000): int
    {
        if ($frames < 0) {
            throw new InvalidArgumentException('Frame count must be a non-negative integer');
        }

        $availableConcurrency = (int) ($concurrencyLimit * 0.9);
        $framesPerLambda = ceil($frames / $availableConcurrency);
        $framesPerLambda = max(self::MIN_FRAMES_PER_LAMBDA, min(self::MAX_FRAMES_PER_LAMBDA, $framesPerLambda));

        $lambdasNeeded = ceil($frames / $framesPerLambda);

        if ($lambdasNeeded > $availableConcurrency) {
            $framesPerLambda = ceil($frames / $availableConcurrency);
        }

        return $framesPerLambda;
    }

    /**
     * Get the Lambda client.
     */
    private function lambda(): PHPClient
    {
        $provider = CredentialProvider::fromCredentials(
            new Credentials(
                config('services.remotion.access_key_id'),
                config('services.remotion.secret_access_key')
            )
        );

        return new PHPClient(
            config('services.remotion.region'),
            config('services.remotion.serve_url'),
            config('services.remotion.function_name'),
            $provider
        );
    }
}
