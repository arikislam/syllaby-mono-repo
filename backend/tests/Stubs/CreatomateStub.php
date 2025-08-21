<?php

namespace Tests\Stubs;

class CreatomateStub
{
    public static function timeline(): array
    {
        return [
            'output_format' => 'mp4',
            'width' => 1080,
            'height' => 1080,
            'frame_rate' => '60 fps',
            'snapshot_time' => '2 s',
            'elements' => [
                [
                    'id' => '6dd98de0-9819-4389-a812-f58a138ee8ff',
                    'name' => 'avatar-amy-Aq6OmGZnMt',
                    'type' => 'video',
                    'track' => 3,
                    'time' => 0,
                    'dynamic' => true,
                    'x' => '75%',
                    'y' => '75%',
                    'width' => '50%',
                    'height' => '50%',
                ],
                [
                    'id' => '81a61c87-6278-4f91-b3ab-070bf9aa16dc',
                    'name' => 'Text 1',
                    'type' => 'text',
                    'track' => 2,
                    'time' => 0,
                    'duration' => 1.5,
                    'width' => '59.2784%',
                    'height' => '23.4107%',
                    'fill_color' => '#333333',
                    'text' => 'Heading',
                ],
                [
                    'id' => '8645e707-01a3-4e49-8272-67034aad3bde',
                    'name' => 'Image 1',
                    'type' => 'image',
                    'track' => 1,
                    'time' => 0,
                    'duration' => 2.5,
                    'dynamic' => true,
                    'source' => 'https://images.example.com/12/example.png',
                ],
            ],
        ];
    }
}
