<?php

namespace App\Syllaby\Characters;

use Illuminate\Support\Arr;
use App\System\Traits\HasEnumValues;
use App\Syllaby\Videos\Enums\StoryGenre;

enum Characters: string
{
    use HasEnumValues;

    case AVA = 'ava';
    case LEO = 'leo';
    case MAYA = 'maya';
    case THEO = 'theo';
    case NINA = 'nina';
    case ZION = 'zion';
    case SOFIA = 'sofia';
    case LUCA = 'luca';
    case HARUTO = 'haruto';
    case AMARA = 'amara';

    /**
     * Get the details for the given character.
     */
    public function details(): array
    {
        return match ($this) {
            self::AVA => [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'name' => 'Ava',
                'gender' => 'female',
                'description' => 'Young African-American woman with deep brown skin, slim build, curly black hair in a bun, wearing a casual mustard blouse and jeans.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/cc5c2385-4c46-4cb0-8f40-9794c525ab71:a8c1862962845d7d86c06683f0cbe0673d5b6e15d16bba6c8b2dcb577d7cef3d',
                        'trigger' => 'cc5c2385-4c46-4cb0-8f40-9794c525ab71',
                        'prompt' => 'Sharp headshot of a young black woman wearing a bright yellow button-down shirt and blue jeans. Hair is styled in loose curls. Soft and natural lighting, 8k, realistic.',
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/91aa89d7-742a-4a46-91af-25c77ebe13ea:6e3af7bbb2f3884dd51463e1f398cbb6f57cf28600f901daa9f867010d72ce98',
                        'trigger' => '91aa89d7-742a-4a46-91af-25c77ebe13ea',
                        'prompt' => 'Headshot of a cartoon illustration of a 12 year old young black girl with curly hair wearing a yellow t-shirt and blue overalls. Hair is styled in two large, curly pom-poms on top of the head. Wearing large hoop earrings and has a red ribbon tied around the neck',
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/82f6b53b-bc43-4fd9-9a51-6b62454c0ba9:636cebd4df831ee84064bd9f1944e15c97c1f43ace305302d46495bec4a30a4d',
                        'trigger' => '82f6b53b-bc43-4fd9-9a51-6b62454c0ba9',
                        'prompt' => 'Sharp headshot portrait of a young African-American woman with an afro hairstyle wearing a white blazer and a white tank top. Hair is styled in loose curls that frame her face and falls over shoulders. Soft and warm lighting, 8k, realistic',
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/5efae35268239f620f35e315c1df361f2fa75e89:6ecd94bfbe2bf97bd93d6ae99eba9b47b0cf1498184fa3679c28093f933adae4',
                        'trigger' => '5efae35268239f620f35e315c1df361f2fa75e89',
                        'prompt' => '3D disney pixar style rendering of a 10 year old young girl with curly hair, wearing a blue jacket with patches on the sleeves and a white t-shirt underneath. The jacket has a zipper closure and two pockets on the front. A pair of denim shorts and white socks with red and blue sneakers. The girl has a big smile on her face and is looking directly at the camera.',
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/ab4c9305-14e6-4ca5-9f07-c67c5ec48e65:b0d4d94f9b86b13327f2f11350265c4a6b25562d6e4d44d9eb57661bad9f4cad',
                        'trigger' => 'ab4c9305-14e6-4ca5-9f07-c67c5ec48e65',
                        'prompt' => 'Sharp headshot of a young woman with curly hair earing a belted beige tunic with a deep neckline, covered by a flowing blue mantle, resembling the layered garments worn by women in ancient Israel. The lighting is soft and warm, 8k, realistic.',
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/790844d8-99c0-4092-81dc-01320d445100:f89e4be59b8b082a76ba874ac8dd44a419e0a417a30b8d52d753f65a7c4748bb',
                        'trigger' => '790844d8-99c0-4092-81dc-01320d445100',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/12ddef4d-cd2b-4285-afac-30006638a473:cfc5fdcbf2b83261a16b545d4f1fbb19b155cd05c02e9ed6f4c48455090c38a5',
                        'trigger' => '12ddef4d-cd2b-4285-afac-30006638a473',
                        'prompt' => 'Ultra-realistic Pixar-style illustration of a young woman with a voluminous curly afro, large expressive eyes, and a warm smile, wearing a teal open-button shirt over a yellow top, large hoop earrings, layered necklaces, and bracelets',
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/c98089a5-3f09-4fc0-ada0-b437bdbd5648:9ede54496de79f64141810daa1992f739229043e6cc1187617534d38f132f67a',
                        'trigger' => 'c98089a5-3f09-4fc0-ada0-b437bdbd5648',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/bb1b7928-7941-4880-84e0-0cf33640b63c:d1c42694296805dc7bc726897ca80616b2bb32a195947779ff4da2f48717833f',
                        'trigger' => 'bb1b7928-7941-4880-84e0-0cf33640b63c',
                        'prompt' => null,
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/c7af529a-3566-433e-856d-928eeda965c9:b9107193c92e8d93813c94687a9a73b0e97afbf895a18f548536df62a40d0753',
                        'trigger' => 'c7af529a-3566-433e-856d-928eeda965c9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/4e9341ba-6b05-4efa-b48a-cd0512b39665:3fc8faefe4313fb097883f74827ebc856825d7ee183e5954553c39b5f53f1a62',
                        'trigger' => '4e9341ba-6b05-4efa-b48a-cd0512b39665',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/fa45c72b-ee06-4697-94a6-f13cb3a45d08:e8441fd185fd730f6b8dbc1d268859c15ab54a68c505bad53daf95403c3053e4',
                        'trigger' => 'fa45c72b-ee06-4697-94a6-f13cb3a45d08',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/cc0fb6c0-a03b-4864-ad42-2706721e4b70:a8c81f4898db4254cf62f56fc28cf6643adf28914d1d2e2c8e1c75aecd801029',
                        'trigger' => 'cc0fb6c0-a03b-4864-ad42-2706721e4b70',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/335e2bb1-b412-4cf5-a07a-f3cdb2455bb6:18afefeaa5ce754988ea75a86f31f9f72d6144f7b308a1cb3f62d5b188bd2ff8',
                        'trigger' => '335e2bb1-b412-4cf5-a07a-f3cdb2455bb6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/4784be67-5019-4804-aa8a-3489a65f4329:a0261ee31a328cd80b44907733ae0adad5e8c166c26f91666d3e17f2aa65cfa3',
                        'trigger' => '4784be67-5019-4804-aa8a-3489a65f4329',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/efcad2b6-e6bd-44e8-a028-9b5806971c49:98f704cc9223794f40a85a238b5e3c1c6dbdd38aa835cbce62e56ea46527b4ca',
                        'trigger' => 'efcad2b6-e6bd-44e8-a028-9b5806971c49',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/c9133b7a-a882-4917-8b5b-1f7f4896bf8f:c299581dc3622bff1d24b678fae8da20a7d6dd672e6accb2ed3daea968a94c49',
                        'trigger' => 'c9133b7a-a882-4917-8b5b-1f7f4896bf8f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/dd7ea2a3-c5e1-44ee-9c2f-d395f4e225b6:6e117d805a592cf23efcd37dde054d265d24fbc62197cd7826b9dd0713b65a06',
                        'trigger' => 'dd7ea2a3-c5e1-44ee-9c2f-d395f4e225b6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/4d6ee50e-995a-47ab-b8ed-c43ce7048e9e:892f989f07fdbf7c3fba806389cb1166e88446871841a8f316d5bc14fe9931e7',
                        'trigger' => '4d6ee50e-995a-47ab-b8ed-c43ce7048e9e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/69edf3d0-8eb6-49e3-a3e5-8d17899893cf:e9bc03bfb13373252d180d7a4a24719e781cb1b02f7569640af1dedc9b142617',
                        'trigger' => '69edf3d0-8eb6-49e3-a3e5-8d17899893cf',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/4449609e-5d8e-465b-82a4-ffda312394a0:64553356b650148f54fc5cf07e2a5cb270f505d94ac687d4128a6f3e6585999f',
                        'trigger' => '4449609e-5d8e-465b-82a4-ffda312394a0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/ba53a072-38e2-4db8-9f40-92de8a80af59:e4844165494ab2760474e00d32b8f6f5d6b5460c974e1eca0525a05bca68554b',
                        'trigger' => 'ba53a072-38e2-4db8-9f40-92de8a80af59',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'native american',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/fae98153-cde1-451f-b84d-d2620839bf09:0901f8b32aa9318b19463d62fae4cf633d1c8a678115d25f3d0223731d220bc2',
                        'trigger' => 'fae98153-cde1-451f-b84d-d2620839bf09',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/ac6f33ee-7d86-4525-9b97-4f5306a9d296:621e961ba36d5ce1ba59f451601056a71c7d8e6a2768450786bdf6f5db6a4f3b',
                        'trigger' => 'ac6f33ee-7d86-4525-9b97-4f5306a9d296',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/ffd41074-469e-47fd-a6e9-2dbc7c439c8a:920d4328ffef5587663cdae57a4d658ef3a0b6be7b31037c13b440b1b528136d',
                        'trigger' => 'ffd41074-469e-47fd-a6e9-2dbc7c439c8a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/845d2d07-6e85-4b7d-b626-912b9580043b:40215285e1f87df296a96fb2654ac4b3e80f3312415f55bf75ff1311aef5b473',
                        'trigger' => '845d2d07-6e85-4b7d-b626-912b9580043b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/53326781-9e6e-4681-9db2-3905b37aee6d:9fd7b40dcb54dd33c5b595fa6b5d850a0959706cd4f532449f13655c0fead4a6',
                        'trigger' => '53326781-9e6e-4681-9db2-3905b37aee6d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/f34afe12-78d7-4120-af2f-13d47a385026:7f634cb13d337e582691fc88ee15a0c060e17863fbaba2cc1ffd0dd7d6f8e74d',
                        'trigger' => 'f34afe12-78d7-4120-af2f-13d47a385026',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/85f3ac41-d3e9-436e-b9e6-3a77c1ee8e39:d0e89904661332b40b11222698fae1fd2f77b6ca4003e4765cbae30152207131',
                        'trigger' => '85f3ac41-d3e9-436e-b9e6-3a77c1ee8e39',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/e40a087e-53ac-491d-9651-4fd2251e9a3c:be5d19111290ecafbccae570c21016d7f3689f33615800d283af658a042c28f5',
                        'trigger' => 'e40a087e-53ac-491d-9651-4fd2251e9a3c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/1b11d299-432d-44ff-a368-df3ffaba4e22:d0e0830a086af6a493ffd4ce52096719aa50fe5d7caa2a5e1d6e2b8c5bdb86af',
                        'trigger' => '1b11d299-432d-44ff-a368-df3ffaba4e22',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/760e48c8-3649-4c15-a595-183edb238ab4:6c12dcd2a4163a3e75981e1268eff37b892801085022a83d9edf35713236e738',
                        'trigger' => '760e48c8-3649-4c15-a595-183edb238ab4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/21df560c-b452-4636-acbf-33850b45d12e:c4984ae4c48d045f057557214b8232f4e47376a11ccb2615569a9e3b1d4963c5',
                        'trigger' => '21df560c-b452-4636-acbf-33850b45d12e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/22deabc9-2a9e-4b2d-b664-2ce22cf3dbae:db9a487d33c3c0dca4a32e50f9bda161b949dc7efc7bb43521a8889146f62c39',
                        'trigger' => '22deabc9-2a9e-4b2d-b664-2ce22cf3dbae',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/4925c270-2822-4345-b1ec-2c7abca30a57:ea36043b752fe58e6d85c02c76a5b811b87e0b095911e415cabbb0c1246973e7',
                        'trigger' => '4925c270-2822-4345-b1ec-2c7abca30a57',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/bfaa0782-94b4-4cc7-93fa-11f0d9a035fd:faf060b0b48192a364e1fc9e70c0b73a340725d284440912c7b280464983ee43',
                        'trigger' => 'bfaa0782-94b4-4cc7-93fa-11f0d9a035fd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/cb65d2c1-fd07-455e-9423-a25889f0fc24:798947db3c61f1cd8313e6ed8caeec45ba6429570213653a4165c9c40c96e61d',
                        'trigger' => 'cb65d2c1-fd07-455e-9423-a25889f0fc24',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/cba56c2f-1911-4f60-87e2-82ce9b3d9ede:be821897d184e8e3e09edeb6b92a91dabc496a29b8f398f86736d609708e8972',
                        'trigger' => 'cba56c2f-1911-4f60-87e2-82ce9b3d9ede',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/35c75886-0b67-4f5e-809b-1e4b078215b8:e59f2aeab65343154ddd37a057d092d90adc072ebcde51c45e775ed3385535b9',
                        'trigger' => '35c75886-0b67-4f5e-809b-1e4b078215b8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/059fbe44-659c-4563-8945-eb10d4b7745e:f0a13a6965a1777c1f5792679ee7c93e937fb8d58d498f920b827f74ac7e7340',
                        'trigger' => '059fbe44-659c-4563-8945-eb10d4b7745e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, early 20s',
                            'race' => 'african american',
                        ],
                    ],
                ],
            ],

            self::LEO => [
                'uuid' => '4a1e1088-9b8b-4750-b6f9-f3468ab8ff85',
                'name' => 'Leo',
                'gender' => 'male',
                'description' => 'Middle-aged East Asian man, lean frame, short black hair with grey streaks, dressed in a minimalist white shirt and tapered pants.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/c0396d81-559d-49ed-8ea8-71bfe40d1e2a:0dbde2cb1e1b0a355d66ab830810e055e0979f1750c0e83a4ac71efb03d4b9aa',
                        'trigger' => 'c0396d81-559d-49ed-8ea8-71bfe40d1e2a',
                        'prompt' => 'Sharp headshot of a middle-aged Asian man in early 50s with short, grey hair wearing a white collared shirt. Soft and natural lighting, 8k, realistic.',
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/f77f26a7-0ffc-4142-ab62-a291680ea67f:7bd84451b7319ac581d34f00ab69bf391580a7e45c2895688514a10fc8a528d6',
                        'trigger' => 'f77f26a7-0ffc-4142-ab62-a291680ea67f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/8faa3faf-88ff-45f9-9a7f-d85fbc638509:97e1048c30eccf7e53683db776a0ff4432d2a8373b6b25dee21aea32434dd0be',
                        'trigger' => '8faa3faf-88ff-45f9-9a7f-d85fbc638509',
                        'prompt' => 'Sharp headshot portrait of a middle-aged Asian man wearing a white button-down shirt and dark trousers. Soft and warm lighting, 8k, realistic',
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/c8355219-177d-492f-a784-54582df176a4:e223a4d6872a7bd80b2d600f4a34524820449f477f683dc0f6897d30a79c0db6',
                        'trigger' => 'c8355219-177d-492f-a784-54582df176a4',
                        'prompt' => '3D Disney-Pixar style render of a 16-year-old boy with short, spiky dark grey hair, wearing a futuristic dark navy puffer vest with shiny padded texture, zippered pockets, and metallic shoulder emblems, over a black short-sleeve t-shirt, with black gloves.',
                        'details' => [
                            'age' => 'teenager',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/fffd5ded-0f56-4fc1-8410-b6bfe3a0f0a4:c95a0b1a64432d7a24dc5daaefa7c07d8e59cbbc7dc4a1ca504e07619875139a',
                        'trigger' => 'fffd5ded-0f56-4fc1-8410-b6bfe3a0f0a4',
                        '  prompt' => 'Sharp headshot of middle-aged Asian man with a beard, mustache and hair styled in a high bun, wearing a traditional ancient brown robe. Soft and warm light, 8k, realistic.',
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/2d3d9e8c-9f2a-4084-b617-dc65bc588065:1d928953f3e041651b9ea01a6a5157df8416d6afe0f227a36240eb2df07f1ddc',
                        'trigger' => '2d3d9e8c-9f2a-4084-b617-dc65bc588065',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747806933:ee6a9cef86eaecba63266c922ded5a0731e0e537666b7da4f4f4e65e46bee828',
                        'trigger' => '101db09e-f5ec-4aa1-849e-0cb7b61d3b48',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/fc2af893-e1fa-40ed-a3a6-caba57dfde37:f0c7678d1d2e39e87910040d49df65c7923f5b0553f7c9daf07176cefd832c92',
                        'trigger' => 'fc2af893-e1fa-40ed-a3a6-caba57dfde37',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/d5402d49-7cff-4e2d-b7d3-6a2934fe9b3c:d1f6c06afedb825e5969bc869436bbf3e5037fb382542cb8dffb9a99ae5750e0',
                        'trigger' => 'd5402d49-7cff-4e2d-b7d3-6a2934fe9b3c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/7cd3e806-76f4-49d4-a6ac-fad1ce5e83fc:f7b6767eba5da96724ba2cfa86ef4cf006b4a24d71559b604b90bdb1b0967394',
                        'trigger' => '7cd3e806-76f4-49d4-a6ac-fad1ce5e83fc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/e106f414-9d3a-4146-974a-7d983c8aad92:3961d417607f479ba5d94a0e8c970ef01b40d50021b7a1006e5532dd053225ca',
                        'trigger' => 'e106f414-9d3a-4146-974a-7d983c8aad92',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/f768c943-c88f-4481-af84-17047163acbb:72225cef3e8cbd79052ecaa4fafdcfb5dc5a847dc3020c911f59b62acd3edec4',
                        'trigger' => 'f768c943-c88f-4481-af84-17047163acbb',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/99f3f800-7f6e-4dfc-b337-6142e6395323:628fdd95e542085676e00bd570039614f9e2da7badce2cbd6c6aeb235e4398c5',
                        'trigger' => '99f3f800-7f6e-4dfc-b337-6142e6395323',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/fe00d5fa-ba6f-47bd-b65c-481e3a3f505f:f9a83aa00db84638d53155b3dfae9e82c724707e5ea18799fb529f8721081e93',
                        'trigger' => 'fe00d5fa-ba6f-47bd-b65c-481e3a3f505f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/c0a8c88c-b929-487d-8815-a776ae2d6da9:1bd11df3a55cc8fe0e983bdb6407d8d121fe511351e3d8f32ee62af8545d96cc',
                        'trigger' => 'c0a8c88c-b929-487d-8815-a776ae2d6da9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/a3380640-605e-4ea7-b802-1bf108e6f06c:8b5ed42e6639d5f00f70350d8862c77c1f3e3eb2c93f931894e85ed9dd92a8ac',
                        'trigger' => 'a3380640-605e-4ea7-b802-1bf108e6f06c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/8395e5eb-593b-4adb-9af8-680227602bf1:56284459ebd1462dffe98209ba92f1ad321dec390bee8f5457ec83d0ea02ee54',
                        'trigger' => '8395e5eb-593b-4adb-9af8-680227602bf1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/abd58ecc-7617-499f-86fd-ad312233e788:403369d4bfefbb6406274dc3c3a4b8e1ac3e6dc38d197f25951fb5819a3afb82',
                        'trigger' => 'abd58ecc-7617-499f-86fd-ad312233e788',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/048d60f5-d78a-4a2f-b23a-2501a34c43b0:b416ce6b542866700e5f196b29b3588323e30d81ee0e80f21b48a933eafd38df',
                        'trigger' => '048d60f5-d78a-4a2f-b23a-2501a34c43b0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/9b0b4bac-87f4-4b7a-9cd4-3ca970e30c66:ebcca74ea8f2b997b0c76089accf5a2afc8ffd33d73b0a9a3132e6cd67ce8a6e',
                        'trigger' => '9b0b4bac-87f4-4b7a-9cd4-3ca970e30c66',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/22520aa0-6ae4-4fca-8053-09141ec44d1f:b14fa8a85d5387859bfe97827b09c3e07e1cbceb6996b65ff56ffd70d552f6af',
                        'trigger' => '22520aa0-6ae4-4fca-8053-09141ec44d1f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/961fd496-3f60-4f34-9d69-da852f4eaa53:0e4a2d92699cfef5fc1495dba6e619fd0f291d1b79b58e431f2323918549fef2',
                        'trigger' => '961fd496-3f60-4f34-9d69-da852f4eaa53',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/79615a27-6d4a-4b3e-a0df-c00104e01542:f11bd3052e46a41630f6a02002d97239bcdba8e4e0648cba2cfd5d7c6c37a0fd',
                        'trigger' => '79615a27-6d4a-4b3e-a0df-c00104e01542',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/1e12411d-b0cc-4362-b2e7-d86dca7393ae:ba2e58ab1d958534aed108cebd7806043ab4fd8416b57239fc477011ec322de6',
                        'trigger' => '1e12411d-b0cc-4362-b2e7-d86dca7393ae',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/0d10209e-b6a9-47e5-8236-417ec8932670:8c6a1f302d25a79db8aebd27627c113a90aa6abaefe19a02f7c2ad9d2464b6c5',
                        'trigger' => '0d10209e-b6a9-47e5-8236-417ec8932670',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/06cafd93-fc4b-4ad5-878b-6d621e6f554e:bc1e99abe261da7a13dc56e5eed590548356e5b12106abf6883513dda6dd5767',
                        'trigger' => '06cafd93-fc4b-4ad5-878b-6d621e6f554e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/989ee509-1580-4314-a5c8-35a3bb24c1e3:8239b8316e4307465903353a3590151e2bb81ee32e9fc72d774fe93a4fd8a381',
                        'trigger' => '989ee509-1580-4314-a5c8-35a3bb24c1e3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/b3719100-7692-433a-830b-6dbbeb5352e0:439ceea51c8ddc17e5e30721ee594a92da2b6f29b895c4439444544db99c9624',
                        'trigger' => 'b3719100-7692-433a-830b-6dbbeb5352e0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/dd47d455-7e21-4ed8-9e27-3035d853ed5a:95b3d9aab8ac69a25e5ff013fe75e36f24e362a0ff2dc62956a64aea0a4fc195',
                        'trigger' => 'dd47d455-7e21-4ed8-9e27-3035d853ed5a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/732d409d-08ba-46c2-a50e-1b64bc348080:9ff89b8ed634b64c92132efc6b5d7b77001d7c8b6a6019ae5456577ec74fecd4',
                        'trigger' => '732d409d-08ba-46c2-a50e-1b64bc348080',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/25d5dbea-fa38-4326-8da2-838212198559:51f08ba3b0585a9d23ef3af70ab16954c62ea518339bfb5c337f665cd733c302',
                        'trigger' => '25d5dbea-fa38-4326-8da2-838212198559',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/511cf5fe-5ecd-4643-8e12-1cfc88217016:f671d5d967df47c19a0b7e916b9cb356c1ee4db71eed9c1318bd3de2bdde31ab',
                        'trigger' => '511cf5fe-5ecd-4643-8e12-1cfc88217016',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/57b57855-fc6d-4bac-8d4c-43fb813614df:ffaef01732ff8c6ada27894e8d9f02a6d71a3a0a981b457fb77a82969708cd48',
                        'trigger' => '57b57855-fc6d-4bac-8d4c-43fb813614df',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/00cea199-c8e1-431a-8d6b-a9e5ee59e6e7:26ec81d48f109ff110b3fed193ef8c566cbb0f86f401b7dee04dc1043371a9fa',
                        'trigger' => '00cea199-c8e1-431a-8d6b-a9e5ee59e6e7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/82e3ece9-c24f-4779-bdd0-6364b5d0dd23:4ca6d10fbd42845e98b543f565d96177457a162f2365e674448c1817260397ed',
                        'trigger' => '82e3ece9-c24f-4779-bdd0-6364b5d0dd23',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/675ce86a-8eb8-4a61-92f6-cfdfc419a8e4:b5a09846d7f2e01458f560b01b4199dc11b146b73cd83a1dc2422c674adf37ed',
                        'trigger' => '675ce86a-8eb8-4a61-92f6-cfdfc419a8e4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/c7e35a31-167b-4414-87b6-f770e3cc4cb5:7c3e7a9b4c7ba40818b0096db4edaab1795f84c0aa8b333250ee16d3035f29e7',
                        'trigger' => 'c7e35a31-167b-4414-87b6-f770e3cc4cb5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/89cf0fc3-9d4d-446c-aaca-7da043385ca8:9b44492abe19b8c93173ab5cf4197e04a9fa57e2b0573faf7436c0be589d94e2',
                        'trigger' => '89cf0fc3-9d4d-446c-aaca-7da043385ca8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/d1f2aa7f-4efd-4643-ae33-d0ad6f89d6c6:31d4b12e84c7b961d8076ea1d072591588c52d4c114732c63f19c14db3067fd7',
                        'trigger' => 'd1f2aa7f-4efd-4643-ae33-d0ad6f89d6c6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'middle-aged',
                            'race' => 'east asian',
                        ],
                    ],
                ],
            ],

            self::MAYA => [
                'uuid' => '1643e7e0-866b-4dc0-9b91-e3be78c898c4',
                'name' => 'Maya',
                'gender' => 'female',
                'description' => 'Teenage Latina with tan skin, athletic build, long dark brunette hair in a high ponytail, sporting a vibrant crop hoodie and joggers.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/bd719539-cd61-4703-a3ad-0b2bfcad4ef3:49e6cadd9839707032ce66f9406a84caf8b648b865db1b173f91a3d8f7bc40e4',
                        'trigger' => 'bd719539-cd61-4703-a3ad-0b2bfcad4ef3',
                        'prompt' => 'Sharp headshot of a teenage Latina with tan skin wearing a vibrant and colorful crop hoodie. Hair is long, dark and tied up in a high ponytail. Soft and natural lighting, 8k, realistic.',
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/b4061d00-faba-4ffe-af81-c67ea6094413:e3fb2e23402039cda1a3055b112f9ef88f960cf79527b5e95afad1929ab4eee0',
                        'trigger' => 'b4061d00-faba-4ffe-af81-c67ea6094413',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/4b33be87-bec4-4e49-a7ea-319a8d8ffb6c:4ca45b95bb4385bf0c2c54695e0474273f07ae20fe1d50074217d485cebcfffb',
                        'trigger' => '4b33be87-bec4-4e49-a7ea-319a8d8ffb6c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/63494c26-8702-4ac8-84de-e7e2ccbe5bc2:b3a1c54cf905c6c5157762b41b3876b6f32c6e78e41712f1aa30503df3d87815',
                        'trigger' => '63494c26-8702-4ac8-84de-e7e2ccbe5bc2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/d2788ae5-42d2-4e24-a183-97bf80425863:9076c9b51b2d6020fc78ba08e460a238412898c30199736d23741dc628ac5a12',
                        'trigger' => 'd2788ae5-42d2-4e24-a183-97bf80425863',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747823203:5e7fc52206fa28a30e62e176908683a8bd4197eb56e6e396fd20ee32a7917e98',
                        'trigger' => 'ea0c777c-b411-471c-b927-082246e333a9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 9 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/0a7d491f-3f26-4ad7-8128-f130ba3152d5:8397248f3fb83559a01cd79af218460431d8ab3530bd8fb2d7b10212985ec9c0',
                        'trigger' => '0a7d491f-3f26-4ad7-8128-f130ba3152d5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/1841f95a-bf84-4e7f-bb17-d00d70fd15ff:78fb08e177077575f4f49bc2e62f1f18d3d627c043bf4779e27084d64becf056',
                        'trigger' => '1841f95a-bf84-4e7f-bb17-d00d70fd15ff',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/9cdbfefb-b70f-400a-8df5-2bd08ec4664f:fb055c7d3a2327e0307c0d3f3cc7925d20ec214c801905f4179b0d93794209c0',
                        'trigger' => '9cdbfefb-b70f-400a-8df5-2bd08ec4664f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/ce548dcf-180b-4b9c-910f-52ffa9302a2b:a29fde37aa3a4db2ee81a668f25452d59de773376ddfd687325ad2aa25d176c3',
                        'trigger' => 'ce548dcf-180b-4b9c-910f-52ffa9302a2b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/21ca9383-ca5e-459c-b43a-9231d31cc5b6:0555a7ca0058d088e042db56180a9a07db01831b3714102d84242891292efe44',
                        'trigger' => '21ca9383-ca5e-459c-b43a-9231d31cc5b6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/0dc4f8aa-4bca-4a83-89e8-15234cbe7536:ccfec17c575713575707972914b0000a28b72bfd47b358da5e7b1d2491ae7cd5',
                        'trigger' => '0dc4f8aa-4bca-4a83-89e8-15234cbe7536',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/3eac547f-85ac-4907-a80b-5534d438772f:136e864f38b7ed41c1f52c7026e13cab3a3ab8a266128ca9e68325ace5b99a4f',
                        'trigger' => '3eac547f-85ac-4907-a80b-5534d438772f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/b941f1e2-7077-4f60-8281-4c8af3235c70:7690049789fdfc10278e3fd20c7c6e05b705a155857d7b6ed491c3e8e677bbd2',
                        'trigger' => 'b941f1e2-7077-4f60-8281-4c8af3235c70',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/b0cf2aeb-ce86-4b2e-81a3-5c2c551aa4e7:ec7d4e8b0cec9e5306c28a6a75c8d317337c3f7707c9a5500650143584e54cb5',
                        'trigger' => 'b0cf2aeb-ce86-4b2e-81a3-5c2c551aa4e7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/0165cd3c-1229-484d-a0d9-91c329078af0:9b073ca59ca3dccadaf7949d6278456c50002f3ef16ee0829fb6a6aae4f837cf',
                        'trigger' => '0165cd3c-1229-484d-a0d9-91c329078af0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/69053f93-cc62-423b-ab6f-4c6504e3663f:fd90c7428b7e63436815f3c2e4f2a95a944f4605eb484b98aa67a5fc88c20ef9',
                        'trigger' => '69053f93-cc62-423b-ab6f-4c6504e3663f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/77ab3945-5c4f-48e1-a0ed-4735e56e298a:aecb1ef5123d7533f3e48fdd4dd2ba06e878b03fa1a5dfd5f95b185125ff7a56',
                        'trigger' => '77ab3945-5c4f-48e1-a0ed-4735e56e298a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/3a008436-c8f8-4def-95b0-f22b6394005d:26cb4a5fbe0820c47d8221595f5940eb5f6df8d59edec99cd2ad890aa19e0ea4',
                        'trigger' => '3a008436-c8f8-4def-95b0-f22b6394005d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/490d1aef-ad10-4c81-82cd-e3026d88a84c:294e7ca79b0c73324490f2d2eafead541a847fb5faacc96ecaab550cee95319f',
                        'trigger' => '490d1aef-ad10-4c81-82cd-e3026d88a84c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/7d4effac-1336-4df1-8015-0d341b86d5d3:a06356d900196a300bb3947078b7405e8f041828b64b718fe4981b986148b9bb',
                        'trigger' => '7d4effac-1336-4df1-8015-0d341b86d5d3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/84527c1f-9a15-44be-ab1e-8865d7a2fd34:434bd8b53a4fb0dc97fc6d3c089877a498d585d718ceb943c0119c9908ada5ab',
                        'trigger' => '84527c1f-9a15-44be-ab1e-8865d7a2fd34',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/06cdaa6c-2b38-43ee-b950-860aece0b98f:a0a62b3b795c16280c717595a70db2131f1e0a5e21d619af1bf369d199d2dea0',
                        'trigger' => '06cdaa6c-2b38-43ee-b950-860aece0b98f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/1799cf5b-cd12-4eff-859e-00bf291c4962:079e1566df231a6c72ae1cc774e5ff4b4835c71061096066de4b71e31caa002c',
                        'trigger' => '1799cf5b-cd12-4eff-859e-00bf291c4962',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/d0f834c5-b834-40a9-be9c-19281ad71403:488db2e3ae47c514579e388916a04ed76b6a646767b76132dc309559b28e1953',
                        'trigger' => 'd0f834c5-b834-40a9-be9c-19281ad71403',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/dce7f045-c05a-4b1e-a2f5-5df8a7ddc3fa:6c8e4e22943c2759341a41bcb427286684b4b8ea0c885d5f53f42175f536b683',
                        'trigger' => 'dce7f045-c05a-4b1e-a2f5-5df8a7ddc3fa',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/9c5c4604-9380-4667-92b6-25806d6365e9:b57eecaf3cf3527618bf0085678387d2216989f8ab32dcdf276a3e6d5cc3eef6',
                        'trigger' => '9c5c4604-9380-4667-92b6-25806d6365e9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/0b84b241-a7db-46e7-bc84-2dc940369a78:1840c94c281fabc6e0f0e8d29527569d439e86e677518674887a49cbf738e6e4',
                        'trigger' => '0b84b241-a7db-46e7-bc84-2dc940369a78',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/80814532-efdb-4a88-a8cb-e9b9d6e9011a:f54149d4c3f35be3211687314239cfe88159d484101fadac4d358598be45889b',
                        'trigger' => '80814532-efdb-4a88-a8cb-e9b9d6e9011a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/c0624f18-3b1b-40c9-bba1-556ccd9a1bb6:02a8944b5b87188a513354f3017de5f1e4f2051f81895c921d4a39d0a9e3fbac',
                        'trigger' => 'c0624f18-3b1b-40c9-bba1-556ccd9a1bb6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/017ede26-1ecd-4ee2-aca6-76d9d32c2f98:47408355e86010fac928c56b4483959db7038e5afa3575f7280080f571499f78',
                        'trigger' => '017ede26-1ecd-4ee2-aca6-76d9d32c2f98',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/7afbceb8-7cbd-47e1-91c0-abaaae59d441:e6d6ccd724cbd08c6031623c42271b5c70902ed2a1308693c2fb39ff16ef793d',
                        'trigger' => '7afbceb8-7cbd-47e1-91c0-abaaae59d441',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/59c63746-fcd4-4fcf-bac4-b418e287fd86:f8c47adca3953501a903f1599b372a0edca03655c3db4e86fb3e3ba50f14ebd1',
                        'trigger' => '59c63746-fcd4-4fcf-bac4-b418e287fd86',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/369c2c68-da16-4c80-8aa6-be5174720cdc:27da4dcd7b0540fe78deff5cb8edc9c641e1913238ea4d9e3fb9030ae5a73521',
                        'trigger' => '369c2c68-da16-4c80-8aa6-be5174720cdc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/c7db2a84-3ed0-4a8e-82ca-93d58392587b:924399cd4d65dd64405b5770ccbf76b1830ca44427d138e5616b2403ef37c36e',
                        'trigger' => 'c7db2a84-3ed0-4a8e-82ca-93d58392587b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/d4da1c1e-c408-47ab-8f38-7b2ba50b03da:88f55f19df954c7407d5b51c2589d51bb8091c4ed4c8f96a661fdc8849c4673f',
                        'trigger' => 'd4da1c1e-c408-47ab-8f38-7b2ba50b03da',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/9a58769f-de2d-49bd-bfd1-e9b747f29d8d:6da389a1a57fdeeedc90cf1c62d4e3323d00003389937fd8c1d2728082ad633e',
                        'trigger' => '9a58769f-de2d-49bd-bfd1-e9b747f29d8d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/3043e308-9e52-416c-a6bb-f58f6db23f4f:a0863f64528b5eb97dd74884049ff01ad7b9f342af1cd828bda7cf0f189e4369',
                        'trigger' => '3043e308-9e52-416c-a6bb-f58f6db23f4f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/25692d3a-50a3-409b-8b94-abf94b7c2b58:081803aef46d33bc83522f7aefc49dfd6969c24216b4daf3130355211a7db82f',
                        'trigger' => '25692d3a-50a3-409b-8b94-abf94b7c2b58',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/7cbd7717-9f73-42c8-9237-7c6bff0f5208:6448768506f5d1c7eef4b3698d1a087c2657274b67fa99f9cec8afb646d6235c',
                        'trigger' => '7cbd7717-9f73-42c8-9237-7c6bff0f5208',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'latina',
                        ],
                    ],
                ],
            ],

            self::THEO => [
                'uuid' => '565f7c31-753b-47fb-be4d-470d5e705f4f',
                'name' => 'Theo',
                'gender' => 'male',
                'description' => 'Adult Caucasian male, muscular build, short blonde hair with stubble, wearing a charcoal bomber jacket over a plain tee.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/d0228cd6-f89f-4b04-8264-4457e0a20ae1:d500e6facf23d21705e14b967f19249d457aba09d2408e927d00332118b71d2f',
                        'trigger' => 'd0228cd6-f89f-4b04-8264-4457e0a20ae1',
                        'prompt' => 'Ultra-sharp headshot of a muscular adult Caucasian male with short spiky blonde hair and light stubble, wearing a charcoal bomber jacket over a plain t-shirt, soft natural lighting, highly realistic, 8K resolution.',
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/38380efa-d8e1-421a-a7d7-76c1d88b4803:e22373ac768ad1c48bc02dd60db623230924211e6f3297728f4fc324a5f2fc0a',
                        'trigger' => '38380efa-d8e1-421a-a7d7-76c1d88b4803',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/986851fd-0542-455f-8e7b- a0b61a4505d5:e6d0e2df2dd4f0ad86b39630623010bb99ec60e8fe1b2bcd1dda86bf6ed36f47',
                        'trigger' => '986851fd-0542-455f-8e7b-a0b61a4505d5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/2e1b1946-7e88-4b70-9301-1880be4bdfc1:fafc9afc28d0b6855cc345a24db7af2362c3fdd6cae504543d6ea4fbc6bc86bb',
                        'trigger' => '2e1b1946-7e88-4b70-9301-1880be4bdfc1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/c2f89e76-c42f-4619-a088-3f313c3943a4:52e4ef0cf71eda9ea12e935880cdaa1423a98a32a6f9234e0710c3693eec9bb7',
                        'trigger' => 'c2f89e76-c42f-4619-a088-3f313c3943a4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/ec956229-39e0-4168-a7af-d7c07c74e361:b50185e9fd981e3bcd636c87512a012a5c1a614e4da3a94e04fee186e58bbe9f',
                        'trigger' => 'ec956229-39e0-4168-a7af-d7c07c74e361',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/359aea2c-612d-48cc-b0a3-5e3f6d561eb5:63d9bd481769bde0c64780144bf27daddac81e02e98bb9c863d59702b6443cdd',
                        'trigger' => '359aea2c-612d-48cc-b0a3-5e3f6d561eb5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/5269e442-c539-4119-bb8c-8a8265e5e487:be7634513a436ed123aa0eb4f7871f0ca16afc84b16e3962a066674ea174d199',
                        'trigger' => '5269e442-c539-4119-bb8c-8a8265e5e487',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/0544ef8d-4538-4363-ae4b-9856e1f935b3:b3ef50ddadc23a61b6fd2a2cf642b18fc874c65fc1082aa3b434a424235f283f',
                        'trigger' => '0544ef8d-4538-4363-ae4b-9856e1f935b3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/fd624c65-6d62-45a6-a387-d02fca3330a1:861e2c4e01295f81df99b61472195e98b26b95e12a6a02787cb3e69c73289e34',
                        'trigger' => 'fd624c65-6d62-45a6-a387-d02fca3330a1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/1324ee55-c875-4997-82c1-6c6ffbc40415:10ad584b679ddf302fbaabc6e07f19379c9955ba65fbabb04212c2645be34106',
                        'trigger' => '1324ee55-c875-4997-82c1-6c6ffbc40415',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/52e8ddb1-c526-4b86-8f26-1b40484d2940:aa8e4feee5c55f38bbec1323b319cd49ad53eccf7fed1c5cd7adba3091f08a36',
                        'trigger' => '52e8ddb1-c526-4b86-8f26-1b40484d2940',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/22b20096-319e-451f-b35f-3136547885bf:cc4a6b553fa2e42a02a0cff22730983facdc70dfd62fc02548942b01d0363d79',
                        'trigger' => '22b20096-319e-451f-b35f-3136547885bf',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/a2ef35a1-d6db-4723-85c0-c6aab08a7b19:836f4ec840ccadbaf477740d1def0920b29afe702160f9418f70ab2a07b0f438',
                        'trigger' => 'a2ef35a1-d6db-4723-85c0-c6aab08a7b19',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/218d4a00-82c4-40e0-ba89-72c2096044e5:7202db75d369b05e093cf838f8daad3e5aa309bd170821dcec9db78c765f07a4',
                        'trigger' => '218d4a00-82c4-40e0-ba89-72c2096044e5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/69e6d5fb-c7bf-40a4-914c-02230e67ba89:5d125ed10753838f68f7709b618efa73ecb716a621d68646697ce00c77e9f9c8',
                        'trigger' => '69e6d5fb-c7bf-40a4-914c-02230e67ba89',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/9ea61e3d-1ea3-41eb-a263-baefb5af07c2:2c7cd8d8502f716a2c00a932daa756d5107dd87c3154ffcff52eadaeb0b39636',
                        'trigger' => '9ea61e3d-1ea3-41eb-a263-baefb5af07c2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/9fe36495-3a6b-4c35-8244-d73f7f67dcf8:87dfd534148e8bff2da2a3f53bb9aa03b8d983aeb8834b5180dd5750b29e38d7',
                        'trigger' => '9fe36495-3a6b-4c35-8244-d73f7f67dcf8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/c897769c-7248-4b1e-be39-1c437a5a7290:9f2a96d7299b2ffac3b32f6c5a2b6fe08444f4a1cfdd05f8a221810d44a2e79d',
                        'trigger' => 'c897769c-7248-4b1e-be39-1c437a5a7290',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/e9d00c07-c998-4b87-9da8-ab1c70628957:d4978523ac2da57a73f06dbbc7161c62a20ff611037c1aa9b3ec47e340f03854',
                        'trigger' => 'e9d00c07-c998-4b87-9da8-ab1c70628957',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/09ae7cd7-28c0-4b47-85d5-a4eda4b1f7e4:b9860a21d2972c39263651ce17e27e5f4ffbcd1c5b333790be50fb90c1b3cbce',
                        'trigger' => '09ae7cd7-28c0-4b47-85d5-a4eda4b1f7e4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/7355c6c5-b84c-453f-82f3-8275d4a1e13d:900a21cca00d0b8f1ac1429356d3b6ef922ef98b26e6c9938a3afb20ffaba518',
                        'trigger' => '7355c6c5-b84c-453f-82f3-8275d4a1e13d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/80be776a-2ca1-4952-b43c-39c03791cadb:7a2125e5762d119b2dd1c001aeb802dbbe329880c8bf27d8c23df8ffd4176a66',
                        'trigger' => '80be776a-2ca1-4952-b43c-39c03791cadbe',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/a4ad4400-ad6f-4b20-9989-a515694b902a:47acd4df676c73ce3f8c223457342ab46dd228be22134e0a96e3ff00170c6acc',
                        'trigger' => 'a4ad4400-ad6f-4b20-9989-a515694b902a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/f23debef-8f58-4d0b-b480-8a77c50ae93c:08c00035561568ca3315c5435ff40a663d287d8b4071dc5eb4df1638152e5833',
                        'trigger' => 'f23debef-8f58-4d0b-b480-8a77c50ae93c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/15de4703-8b8f-461a-8a49-6ab9f7d4cedf:b403a54d2cb4711e3c7cbed434e7d68838a8a3ac75eb9d3c63e9458f1e1b14fd',
                        'trigger' => '15de4703-8b8f-461a-8a49-6ab9f7d4cedf',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/c10e0750-1cb6-4e76-88a5-d4e070a02592:ed574526f97c49ae9371f8c28f7e154b7aad64f51fe32d432f1c5f88e88ee101',
                        'trigger' => 'c10e0750-1cb6-4e76-88a5-d4e070a02592',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/f82d4800-db10-4beb-b390-40afc5f03986:d779ec1716046b764e59bceb6414e5812b845b00f256120473259ad814145012',
                        'trigger' => 'f82d4800-db10-4beb-b390-40afc5f03986',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/67b3f2df-a655-4df9-870c-b830e6e4bc4c:37324b5bee5ec9137563df7d9894814dd7f5aa19f327b17001ba01353e39d7cc',
                        'trigger' => '67b3f2df-a655-4df9-870c-b830e6e4bc4c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/3343f386-009a-4531-922d-906c51ddb73b:62257b4ab7b957c8c6f042b7adea6cc56139c91e367f8aee2d24a98e85490b68',
                        'trigger' => '3343f386-009a-4531-922d-906c51ddb73b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/1a0700de-4225-4a8e-922a-7123f6826e10:f57bbf2a5d3424cb74e977498e949b31aaf9151538611882c8077c608b0487ec',
                        'trigger' => '1a0700de-4225-4a8e-922a-7123f6826e10',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/ca4e9c88-fe51-43e3-bf7d-bb9cb0f5a2a6:6b7087612f1f661ce7dece639e16bbd55c853023ff73d49d0550f6e210a0f20b',
                        'trigger' => 'ca4e9c88-fe51-43e3-bf7d-bb9cb0f5a2a6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/6b61630e-ab64-4cc6-a476-1e6d8625e845:42010a0105c27ddd870dd7555979bd31e1068a8fc1272dfa201dbb9a9b597bf0',
                        'trigger' => '6b61630e-ab64-4cc6-a476-1e6d8625e845',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/725f8fe0-3af3-4909-bad3-1ce96804c3b5:63df10a7bfb439aeccdbd0f7d7c746d0a833aaf7d2cfede460acdea1f9cdd5b5',
                        'trigger' => '725f8fe0-3af3-4909-bad3-1ce96804c3b5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/d6df93f3-86e2-4f9c-bb18-b32d4627f744:f276d44212381ae1f4a01dbd9f1b7ba5d07a12dc59e6fc4ad9cf0b82daed8dca',
                        'trigger' => 'd6df93f3-86e2-4f9c-bb18-b32d4627f744',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/d4a65351-8bc0-4ae0-8479-95a39381226a:a2ea11131a472ee3dc52eea46ac08a1d854a5c0c3650513d4190402d8e9f4dcf',
                        'trigger' => 'd4a65351-8bc0-4ae0-8479-95a39381226a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/6c6efb92-c6f6-41a0-bc6c-88d2f7388056:816dffea2231fd7a1a27d46ca3c838a31d4a45a9b581b28fea5b76346543ffa1',
                        'trigger' => '6c6efb92-c6f6-41a0-bc6c-88d2f7388056',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/cee040f4-a0ce-40d8-8c49-4fae56e19a2a:76e843677b6d06c88ef06dcb0c41e09561fc3afbe86d3acae3a55baf4833f4cc',
                        'trigger' => 'cee040f4-a0ce-40d8-8c49-4fae56e19a2a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'caucasian',
                        ],
                    ],
                ],
            ],

            self::NINA => [
                'uuid' => '5552670b-2f5b-4baa-97fe-838805e7b420',
                'name' => 'Nina',
                'gender' => 'female',
                'description' => 'Older South Asian woman with a fuller figure, medium-brown skin, shoulder-length silver hair, dressed in a soft floral kurta and scarf.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/5f4d8ad1-7efc-4236-8d7a-4ac80c4d2b15:5c82954472a7c2b6e721d36f8262e2176eb91c34a668ce725b06d9d54761483a',
                        'trigger' => '5f4d8ad1-7efc-4236-8d7a-4ac80c4d2b15',
                        'prompt' => 'Ultra-sharp portrait of an older South Asian woman with medium-brown skin, shoulder-length silver hair, wearing an floral kurta, red dupatta, gold jewelry. Soft natural lighting, highly realistic, 8K resolution.',
                        'details' => [
                            'age' => 'senior, around 70 years old  ',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/001667f1-73b8-4f52-a961-969e2b4ac047:8b9269b0b6d72a2d6cf2d1bbef555bb028946fb9e73be761962f207f30e09c40',
                        'trigger' => '001667f1-73b8-4f52-a961-969e2b4ac047',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/cbc19ddb-1c99-45af-a8b6-1515d0ed9c98:718ff72e7c5d34f47946b240925139c24e15166ed62c3fb5bfb38cfa3b4ef8b7',
                        'trigger' => 'cbc19ddb-1c99-45af-a8b6-1515d0ed9c98',
                        'prompt' => null,
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/876013ad-39e1-4a31-b65b-e48a039b0332:6d4f4a727bce184a144ca98da33d08f272410279c6d79f76c3cf1367a7340bd1',
                        'trigger' => '876013ad-39e1-4a31-b65b-e48a039b0332',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/1c9964e0-b9c1-4ebf-a559-88f2de69b9fa:8a06ba8e4ef2b985f66cbe31035b77106944e20755be5994be877a498e722761',
                        'trigger' => '1c9964e0-b9c1-4ebf-a559-88f2de69b9fa',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747823498:022ebb93d15de04913f5867229359cfb3308dc41b7027f963a5551df9325830b',
                        'trigger' => '12723129-3b50-40b6-bca4-e6953fb106ab',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/f7b9f898-f41f-4b27-9417-fa271b7e7bcc:a9f3c9dac774d727cc966c50fe425037143608018cd1c427bda7321fdcea05d5',
                        'trigger' => 'f7b9f898-f41f-4b27-9417-fa271b7e7bcc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, in their early 20s',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/ba97cbc1-33d7-4b3e-84cc-67a0ccbf32ca:afba51a520690c250b5cfc5ea038f9ea2d68e6021bdd78934628f919bc638e65',
                        'trigger' => 'ba97cbc1-33d7-4b3e-84cc-67a0ccbf32ca',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/ccb2d1ba-9e49-4a8c-89ee-9e4b26774f7e:791067a493a898dacae721b52c4391d52d06fab9abf85f3f34b91572a689bbe0',
                        'trigger' => 'ccb2d1ba-9e49-4a8c-89ee-9e4b26774f7e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/1f02cd03-4502-4af3-ac39-2a9b7dd1e6bb:39198adfd1d63f436c3c39103da99c3e91b0ecf320d884d83d21b85996f948cc',
                        'trigger' => '1f02cd03-4502-4af3-ac39-2a9b7dd1e6bb',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/839e340f-6780-42f0-86cb-d0e10bf490e5:3ee39e3099e237c150bc68a45c7561cf25505cf044186d834be779b96cd092bd',
                        'trigger' => '839e340f-6780-42f0-86cb-d0e10bf490e5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/2fa9b74e-6864-4a12-a54e-e96399e135b3:048165d0cdeac944556daf41fcd4efbf0431dffa07614831d9e1c6058b753cd1',
                        'trigger' => '2fa9b74e-6864-4a12-a54e-e96399e135b3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/1b92f311-2635-4110-978e-f78d6f6347a0:dac00a058e931d2346cd17993d552e59c077e18f5f9a495c10c0df473142785b',
                        'trigger' => '1b92f311-2635-4110-978e-f78d6f6347a0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/9bc9ecd1-84b6-4b76-8b97-3b390a707698:3eab0ae481008ee88eb6b4cb630f556fbe6d62ee8a3817e3483172fe8ddafb3f',
                        'trigger' => '9bc9ecd1-84b6-4b76-8b97-3b390a707698',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/208fb232-e6c5-460b-b22d-447db9f2e016:e5c02b45221cbba2a02b08d981408a6ff85578ee2cac9f43817295e8c404a319',
                        'trigger' => '208fb232-e6c5-460b-b22d-447db9f2e016',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/d298ea00-2eec-4b9e-ae22-ba221cd91ba6:1cbdc55bfff769e979ac9dcff34b3377809a66e16adf374c4dab18276c6e216d',
                        'trigger' => 'd298ea00-2eec-4b9e-ae22-ba221cd91ba6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/c702c7e7-2540-4fd3-bbda-704bafbb801a:63e162b3fd02de29e1293bc0592e5f16f9424df82227ec1e8726fdd748fe7117',
                        'trigger' => 'c702c7e7-2540-4fd3-bbda-704bafbb801a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/e1008d15-062d-4be5-a804-9780659f24ef:b7487cb3bd4aa01c880d7c50e81b4c9529cf55f92f92c9906c50d1e5964d89b9',
                        'trigger' => 'e1008d15-062d-4be5-a804-9780659f24ef',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/0b0d66c4-f7d7-40d2-9fbd-5cb46da9354f:f11503f9320826fc1a3b6e6bd903e923f718a85dc4300549069120717bfd632f',
                        'trigger' => '0b0d66c4-f7d7-40d2-9fbd-5cb46da9354f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/62a59239-83d5-4d57-9af5-7e1820ac4738:b1c3b4d473c0c447547da1f740d375d2fde6c30a7b5921b54fd7c795d5df816b',
                        'trigger' => '62a59239-83d5-4d57-9af5-7e1820ac4738',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/490685a8-7029-4bce-9d44-e5ec1c6c10f2:acf6a81da6309a0b0a93f43a1665947f8baa37191280486917ccfabf6f43e961',
                        'trigger' => '490685a8-7029-4bce-9d44-e5ec1c6c10f2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/7793bbf1-348b-4cd3-8dab-ed2387a395ad:c0d402c1d8760adc026f428258f05dbabf4316b1fda76e3f2f6ec9e4bf15aaa2',
                        'trigger' => '7793bbf1-348b-4cd3-8dab-ed2387a395ad',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/d775e09d-1671-4a0b-9ea1-2d258c7da0d1:7b0470037e6df4e8956528dc921ab30d11e75691229c9e7f7b62ed0e920d5629',
                        'trigger' => 'd775e09d-1671-4a0b-9ea1-2d258c7da0d1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/9e8c1a7e-3ea1-4462-a679-47b1606d75e5:29208da1c3d1d031df9b7027186a3cd0718d51c35e549ab448eec92853988e82',
                        'trigger' => '9e8c1a7e-3ea1-4462-a679-47b1606d75e5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/62acb0fd-e207-4479-80e8-7a84fa3a28f8:585ca712cf9acf7c0d8ad67ef9e273d466ef9882a54dc777e8b7a3b6c3e1bd0a',
                        'trigger' => '62acb0fd-e207-4479-80e8-7a84fa3a28f8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/74881ab1-1617-47ab-9956-535217ac4f2f:b40c32db9e39b0819bc3272402c589afce4b48533502241f6351a0040ab7b1af',
                        'trigger' => '74881ab1-1617-47ab-9956-535217ac4f2f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/87315e08-2392-464a-a2b8-72691fc78dd5:1cf4f2e1dc3e013a95a7836c9365bf4ff9bcb8791db1f1d63debd017dae8ea4b',
                        'trigger' => '87315e08-2392-464a-a2b8-72691fc78dd5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/66554763-437c-4b66-844b-8c73d81827da:96b225d194479deb4cdf23c08edcc9901be1b1695f1f56643b5ee21c44051da7',
                        'trigger' => '66554763-437c-4b66-844b-8c73d81827da',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/c85ae8d3-0cb0-4120-88e2-3808ce985dc5:c9af9d3f07529fa21ecb74a308f11b458fd6ea29e79a4f467d140bb2838116c3',
                        'trigger' => 'c85ae8d3-0cb0-4120-88e2-3808ce985dc5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/0b3ff65f-61a1-439d-a632-ccc768a250ec:5f7a0d347218346b6830677908b84001d034a7d39a68382e36c75eddf9f1fb03',
                        'trigger' => '0b3ff65f-61a1-439d-a632-ccc768a250ec',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/a94bdd00-7c8f-4b69-ba4b-3492ccf9e321:2af5cba23a43a54dcb0c138902ba14a9a9fa37c82a95c721e85c0053e0807cfe',
                        'trigger' => 'a94bdd00-7c8f-4b69-ba4b-3492ccf9e321',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/d31acf9b-7ef6-46d8-b844-42544c953416:c82e9c98b7e9ee2527f24502f6f8306048b8d1b69f60d7f9c054a5575d1a5c8d',
                        'trigger' => 'd31acf9b-7ef6-46d8-b844-42544c953416',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/b13d3ac5-1db2-47d9-9a33-ffc4af924494:a1d7a934e7ec5c97328f5e04dfd49e03945b9989c7efdd8883bc54836154cee1',
                        'trigger' => 'b13d3ac5-1db2-47d9-9a33-ffc4af924494',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/dbeedadf-cdb1-43be-84b3-bfca2b1abace:0f5e9d02694961f58368707a05cce79002f738f8c516546cd7a8ebab1ef4b190',
                        'trigger' => 'dbeedadf-cdb1-43be-84b3-bfca2b1abace',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/c280c558-dc04-421f-a3c7-ab2c3ee7f5b0:705b2a70f82e16f48ded97ef0d6f13ac5c0f049a1ee1afdfbaa2713257e00c08',
                        'trigger' => 'c280c558-dc04-421f-a3c7-ab2c3ee7f5b0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/0c0223f7-acae-4412-bba8-ba97069c99f3:042bea5e50c42927d505f1f2ccce5db861c111082eefec5e10e2f4317b421417',
                        'trigger' => '0c0223f7-acae-4412-bba8-ba97069c99f3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/aa71988b-233d-4764-8dab-25f3becfbb3d:ec3baa33bba845aa68b04d25c35cc3d5f0d56c028718b7535162db970dc6c5c9',
                        'trigger' => 'aa71988b-233d-4764-8dab-25f3becfbb3d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/9fca95b8-217e-46dd-bd95-f5c674ddb2ad:f77313b37ebb51eedfa13216534ebc552d71c607e355f8eeebc710b5fd1fd951',
                        'trigger' => '9fca95b8-217e-46dd-bd95-f5c674ddb2ad',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/0afa46b7-430c-4039-b9e9-32ffafcaa8ae:733f1cc82bcab0cf9db612c6ddfb1955ce04a48d5d7543dfe5f379e100fb7f0b',
                        'trigger' => '0afa46b7-430c-4039-b9e9-32ffafcaa8ae',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/1729ab35-c795-4a30-9af2-fa89f08187cc:7d03b661785ca72091fe5fbd6c3d4574005b57ce1a56b8d2704763b05e08eaa3',
                        'trigger' => '1729ab35-c795-4a30-9af2-fa89f08187cc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'senior, around 70 years old',
                            'race' => 'south asian',
                        ],
                    ],
                ],
            ],

            self::ZION => [
                'uuid' => '7ed05d72-289e-4a5e-8fcd-f073b30d11ce',
                'name' => 'Zion',
                'gender' => 'male',
                'description' => '10-year-old Black boy, lean frame, short coiled hair, wearing a bright graphic tee and denim shorts with sneakers.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/c6e73f02-7aa1-4780-9662-7c7ea606d0ac:8651e673b8f2de095d46fe96f412a73ad2c445634b20402b2fa7a6506558fed5',
                        'trigger' => 'c6e73f02-7aa1-4780-9662-7c7ea606d0ac',
                        'prompt' => 'Ultra-sharp portrait of a 10-year-old Black boy with a lean build, short coiled hair, wearing a bright orange graphic t-shirt, denim shorts, and sneakers, standing on a street, serious expression, soft natural lighting, highly realistic, 8K resolution.',
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/82a356ab-a20e-4c5a-b9ec-24ef91194bec:b74236176b69a1effa75fe0aab1e21853bfda1df47d2793161909d9fa418f606',
                        'trigger' => '82a356ab-a20e-4c5a-b9ec-24ef91194bec',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/d552919c-ff30-4fcd-a641-a1f15b01d0fe:4c446bb5e57a1d2650e601dac85f22f963c6bc8da3b325b18a5ea1c019eccd73',
                        'trigger' => 'd552919c-ff30-4fcd-a641-a1f15b01d0fe',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/6ce70ce4-b033-4554-925e-d66875d271e7:7aa59c2d16b041eb392558c9c800a4c0226e6c55e7158b6896a6fc8505ffea8d',
                        'trigger' => '6ce70ce4-b033-4554-925e-d66875d271e7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/c56d3622-1b8a-41f2-aa2f-ffe3d38a8226:8482b4c9bb8f763e217edac43d29bb6066c95ea77dc5ff89d36879561239a50c',
                        'trigger' => 'c56d3622-1b8a-41f2-aa2f-ffe3d38a8226',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'black',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747897201:b9fae51913fee335251c3ed47dcf719ba6214facc6f91d6f12031eea855e6b93',
                        'trigger' => 'd031bb81-e0ff-470e-b272-8876b70ceec8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/d8f2a5a8-0399-4e30-839c-1aa9195b29ae:b0c0d582fbfc9b0c36e01b7153953e11eb8a94ab5008e0506b20e192dc1d9527',
                        'trigger' => 'd8f2a5a8-0399-4e30-839c-1aa9195b29ae',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/d021de4b-7970-4b6e-a355-7208447dd6bd:47daf5c8f2882a6e2de39f885dfbfdb55c7cfcb0f0e955da352ba9e86a8b7a32',
                        'trigger' => 'd021de4b-7970-4b6e-a355-7208447dd6bd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/fc3abf09-3e48-4022-ad2d-f890db1c8940:ecdd26fdaf8e9958d25f03df88fdcf2b4d4bee0774fc258052bc17cc3176542d',
                        'trigger' => 'fc3abf09-3e48-4022-ad2d-f890db1c8940',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/6b7e611f-34ff-4554-ace8-23ad3217b4cc:bb617cb5cf07455b2213426625d14ff763526889fa902064b88000416c9fdd06',
                        'trigger' => '6b7e611f-34ff-4554-ace8-23ad3217b4cc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/96f475a9-90e4-4451-b324-8614525098cc:c2a4d1e8998ab042e7f243d39cce759fe46ec869a0fc2a9a461a42021ee39c87',
                        'trigger' => '96f475a9-90e4-4451-b324-8614525098cc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/95d5d7d4-cce1-4076-be6a-25d6f5a14b59:e5627516660d876fe48e5aae16cf2b26f103c3e11e8a4c797f4df67678a5ba03',
                        'trigger' => '95d5d7d4-cce1-4076-be6a-25d6f5a14b59',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/496178c0-f1de-4e6c-91d9-23717fc1db19:cefd27364c3baf7aaa1440171172c28069757ff6d800be46730857b368ec40be',
                        'trigger' => '496178c0-f1de-4e6c-91d9-23717fc1db19',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/8cfa92fb-814a-4365-9ca4-fa0127bd129f:323957b60a4b6aac2180f98b3b6b7780e6eb69fac4105281793fd20b65809706',
                        'trigger' => '8cfa92fb-814a-4365-9ca4-fa0127bd129f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/19f031a5-3997-43cf-9c69-c2bc6c721c7b:2a3594af12e7ca64b9856de287109f39301f3ef66c5cae1cebcfe197bb6dc68b',
                        'trigger' => '19f031a5-3997-43cf-9c69-c2bc6c721c7b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/21f950ec-d8f3-46d4-b59d-1d0738a1f493:412be69bcef1b192a0b9d914c97d8cfedd969ec832d634b7f0c91763e683ff7c',
                        'trigger' => '21f950ec-d8f3-46d4-b59d-1d0738a1f493',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/026015d0-50ad-4094-9704-11a05094cc9c:cf8168ce4e33b37e5ee39613ad64abacb73c15ea667a434da9c911d625cd01d2',
                        'trigger' => '026015d0-50ad-4094-9704-11a05094cc9c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/ef147b60-8628-45a6-82d0-b7341d3fba56:ac0ce5c9a5b4dd4b3a340b7656191a85b9985bc92f1ff30431aaa760a9da6e7d',
                        'trigger' => 'ef147b60-8628-45a6-82d0-b7341d3fba56',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/3271c01a-ee0b-4f40-a05d-146ab2426d03:519b2f083fe22dad87400c85d5df4f872f2f1f216fb0fb8c9aebaa57740ad018',
                        'trigger' => '3271c01a-ee0b-4f40-a05d-146ab2426d03',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/63ab7e05-81db-4686-953c-db3313693c05:f3bab3facdcdc19a4911ab5da73734fbf7b30fd735b70052296522483a0cd7f4',
                        'trigger' => '63ab7e05-81db-4686-953c-db3313693c05',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/67f6b2ec-8659-444f-ae19-0d06fa53fc4c:765dad1e44afd1ddbfdc13eb9c38f98972e76146d1d609d366073250492d4a49',
                        'trigger' => '67f6b2ec-8659-444f-ae19-0d06fa53fc4c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/c436b29e-12a9-4c2f-ad05-573979a8d136:fe9626cb95986f6f09a41e3d7460f20b0dd87f95e0537879dad0654bc7319ac9',
                        'trigger' => 'c436b29e-12a9-4c2f-ad05-573979a8d136',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/2771d677-2acc-4156-afb9-799fba70bd4e:130cbfe1237e8fb1796d0019dcaa966dbe2c9bed4cabd1766b6d94bf4c1c18ff',
                        'trigger' => '2771d677-2acc-4156-afb9-799fba70bd4e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/c756db7d-18db-4055-8177-fb371c1bdcc4:0ac45713c99a3a85ebcbf59927e2f2633fe82b348bd611dad358099c3f794938',
                        'trigger' => 'c756db7d-18db-4055-8177-fb371c1bdcc4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/aa1cecb6-ac54-4747-85ff-d8c165799b4a:796f4702c745042f81cdc40832ee63f3032e10736b395d2f4f6f95e0ef98f979',
                        'trigger' => 'aa1cecb6-ac54-4747-85ff-d8c165799b4a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/5c4b3f4c-bb6d-410a-bde1-ae8147d59a5e:02b191a3e8b5d7da07a9ff9c5847dac6f315dbeb565bdbae8b7803ad722ccf75',
                        'trigger' => '5c4b3f4c-bb6d-410a-bde1-ae8147d59a5e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/4bcc6d06-ced2-4322-af52-c912e803a636:5f0c264814df46846e450ca0167c74555b3458642003bf0193835e7b752b2df6',
                        'trigger' => '4bcc6d06-ced2-4322-af52-c912e803a636',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/e0c04ae0-760f-4a91-b01e-15949722a835:48f50bbc5f940b589c0e6573178979f47e98f1b7b6870363fa406070e9d959c3',
                        'trigger' => 'e0c04ae0-760f-4a91-b01e-15949722a835',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/8b10f5be-5b3c-4dc6-bb7e-b3f868ad7830:49d20d0c0012619759f5122a0c887a405e1f00337841f16ce369bbd924d07368',
                        'trigger' => '8b10f5be-5b3c-4dc6-bb7e-b3f868ad7830',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/77e3cf2b-1ab7-4922-9edc-ae0037ee4c07:5c05b513ba9c7078e739a7e8ab7afbb6d9d4f05ed5dddceb7799602f10a065b4',
                        'trigger' => '77e3cf2b-1ab7-4922-9edc-ae0037ee4c07',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/d27d773f-821d-435b-be2c-a52ebeedb448:820c2539e6a5f233efcfbb6d9a965d398f5936c8c72346983eaf98761c699cee',
                        'trigger' => 'd27d773f-821d-435b-be2c-a52ebeedb448',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/f2530997-682c-4f2c-b0cc-c9e1de9392ea:f998fd7a2116ce64cc8371e61a76b2694a091e829aa2521b5df9e1cbb950a1b3',
                        'trigger' => 'f2530997-682c-4f2c-b0cc-c9e1de9392ea',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/90933546-a912-4c0f-a9c2-a8eb3a32dc76:9c6e20e63336c8fded25aa42fc9b2fdf5246bc9b7fae78101e1f81a9d58254a2',
                        'trigger' => '90933546-a912-4c0f-a9c2-a8eb3a32dc76',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/e271d58b-909c-49c8-912a-f21a255a6ff7:a69152584034d92cd06ab0174365c3ab9fa83aeff46665aa6ef134ddb581741e',
                        'trigger' => 'e271d58b-909c-49c8-912a-f21a255a6ff7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/65815e03-37cd-4658-b267-cc7e29af6376:ebc315097acc83dcfdfd8a217f7f40ddbd7bfb2673b96b24cae64369598c47cc',
                        'trigger' => '65815e03-37cd-4658-b267-cc7e29af6376',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/2bd4ac31-7097-482c-b607-c8a23ad79b06:a37a099e0dedea99896d90696847c433d4809afa7d674578b3229482feda9e5d',
                        'trigger' => '2bd4ac31-7097-482c-b607-c8a23ad79b06',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/b15cb838-011d-4e75-9387-11d70e168320:592a3727d240957f8cfab8dc62c0678a2664ce4f7c9c464af6d13fe837a302fd',
                        'trigger' => 'b15cb838-011d-4e75-9387-11d70e168320',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/613c5287-7eb7-4ed0-9081-5847a5a6dfa7:b2bad79ff3d92feb6de4e8d886109bcd1aa4f6a89780b88d506b56d92fce49e9',
                        'trigger' => '613c5287-7eb7-4ed0-9081-5847a5a6dfa7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/c947f5f8-f0dd-421b-a595-6a9bac024f7c:e7987cc7cd8af7181971cdb79c9e86a839244c3cc054199efd02636792767d54',
                        'trigger' => 'c947f5f8-f0dd-421b-a595-6a9bac024f7c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/9f54c7d0-23d6-4adf-9ec5-5a5aa8d1e9e9:4d2d6664e52842872b0a6c6b53cff22630a0122333719b5c99e6c453e7270899',
                        'trigger' => '9f54c7d0-23d6-4adf-9ec5-5a5aa8d1e9e9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'kid, around 10 years old',
                            'race' => 'african',
                        ],
                    ],
                ],
            ],

            self::SOFIA => [
                'uuid' => '685b6cd2-7a5c-4035-9f3c-87140138c635',
                'name' => 'Sofia',
                'gender' => 'female',
                'description' => 'Young Middle Eastern woman with olive skin, hourglass figure, long dark red hair, wearing a pastel trench coat and ankle boots.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/3405cd2a-13d1-4723-81dd-0d609769b58e:482ddfef3f8660f62c8277277d16a0acc937f1df00cbb5b5e0a8c7e1d0b4985f',
                        'trigger' => '3405cd2a-13d1-4723-81dd-0d609769b58e',
                        'prompt' => 'Ultra-sharp portrait of a young Middle Eastern woman with olive skin, hourglass figure, long dark red wavy hair, wearing a beige trench coat over a white tank top, denim shorts, and brown knee-high boots, walking on a wet street, soft natural lighting, highly realistic, 8K resolution',
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/b184b7f6-dc50-4c2d-bf77-5856bfd512fb:08788fff7cad09cc895a93a494a13976a2544fa7735df3973d0b00cd76423e74',
                        'trigger' => 'b184b7f6-dc50-4c2d-bf77-5856bfd512fb',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/2fc5c2d7-6f78-4559-8a38-a10cf0ad11c6:47def1b7d3ac2dcc81dc048c5699711c3b867c8240aea6ab4ea775d74434a2a1',
                        'trigger' => '2fc5c2d7-6f78-4559-8a38-a10cf0ad11c6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/18beb7a9-82b1-4987-97e5-b7fe15a8ac7d:93103a1e2a59cfb9fbe48b8f0377ccaf5661e3b7234c825f14f3cce6e3dd017f',
                        'trigger' => '18beb7a9-82b1-4987-97e5-b7fe15a8ac7d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/66d83cb3-401a-4432-9b2d-11b0061adbf5:55407f2b087c0d523ed7f6d2ff884cba2ada6c76c66c9b085c09bd6ed744a940',
                        'trigger' => '66d83cb3-401a-4432-9b2d-11b0061adbf5',
                        'prompt' => '',
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747897389:e6c055941d5474a1491ded106cb4ff104981e6ca2abd6501c10f918211af284f',
                        'trigger' => '9ff25494-e0f0-4e11-99b3-acdfeaf37189',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/28daf605-f2ef-4a5d-8da0-87fbd260a72b:bd14bfdc5d10a73b6b06d23e633695833d880d030035f6225cc19554809accad',
                        'trigger' => '28daf605-f2ef-4a5d-8da0-87fbd260a72b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/a161753d-8007-44cf-80d0-e0eaece21691:05265e77b131e2255396a1acf7f18a6a367e8349f1329abe747d319c45f6e975',
                        'trigger' => 'a161753d-8007-44cf-80d0-e0eaece21691',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/f6688062-3104-47b6-906b-2a4945b7c7c1:4e12cec583527ce267e6179d2446c97e9e1dbb48897021c9c957e0ab398c41fc',
                        'trigger' => 'f6688062-3104-47b6-906b-2a4945b7c7c1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/0494566c-2be9-484d-9082-2efaf5ded926:d05d761887c4a58cff2f28301f4b412aa1a20fc9aebe0a721b5b51ff425de342',
                        'trigger' => '0494566c-2be9-484d-9082-2efaf5ded926',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/53fc1d08-c057-4524-987f-2de56f76bf66:0ae0488e305dfcceb4450a72c64e228fe056b01fa65b4e31ae072e308921f591',
                        'trigger' => '53fc1d08-c057-4524-987f-2de56f76bf66',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/b0386b48-a3cd-48eb-95df-e07aa51fe7f8:932bc3dc9e17d8c780a9850de05f50647f5426ebaa53d67e8813a0f4ddcefc94',
                        'trigger' => 'b0386b48-a3cd-48eb-95df-e07aa51fe7f8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/e979b2d7-7ae9-4613-b1f3-439a4692fe8f:f3dea28d4b8d117eb9428646d90380854f0d285122d5b060627e88a2d47e7704',
                        'trigger' => 'e979b2d7-7ae9-4613-b1f3-439a4692fe8f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/b2a9e321-df3e-4f61-af73-21f0eebdffcf:4aabe2cd07a2d62669f703a7047a4136df89bc13b3dffac152c2eaf83661bd71',
                        'trigger' => 'b2a9e321-df3e-4f61-af73-21f0eebdffcf',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/6b85f863-e2ae-4d4c-bf64-5856c42d1e78:8685b65433914da01d0d9f04ad4365619c9cd51880e34d07931b66f779a0e042',
                        'trigger' => '6b85f863-e2ae-4d4c-bf64-5856c42d1e78',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/00e77ff6-a047-4600-a71d-eefe5e4698de:19e04cdaa6f973faf1284154dec20a1441adec62213d51a7c6b3f4d5b6033d62',
                        'trigger' => '00e77ff6-a047-4600-a71d-eefe5e4698de',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/4ac56a8b-c1d1-4e29-95d1-d8c422087549:be78a0ef402df0bdb5f7e2881588229135cd1761164a5c8a81e105301d1cd026',
                        'trigger' => '4ac56a8b-c1d1-4e29-95d1-d8c422087549',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/92cbbd33-56b7-417c-94a6-48bf86cbd180:c09e8a816d03096264e2733ef140c753d1aef5e4c97d4375aae68d49be8d8e3d',
                        'trigger' => '92cbbd33-56b7-417c-94a6-48bf86cbd180',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/2a47a540-98c1-4e1f-b207-dc5332d0c332:da27e3d65a1214930e542c38439e7cc1dce89996cf47c013f90416ec2e7a922f',
                        'trigger' => '2a47a540-98c1-4e1f-b207-dc5332d0c332',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/a1474a84-bde2-4660-aeb6-1c7c18ede19d:13c7d29ad00a33f0a2622c5d55e0bc995817bf60f653d0806759c1c5c32eff4d',
                        'trigger' => 'a1474a84-bde2-4660-aeb6-1c7c18ede19d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/47f1c3d6-cfab-468f-b1a6-3cf6ef3a9284:fe70f67281d0fb29d15ccef62526ec7b6abc0f9eceb65b5606eaab08a765b5ad',
                        'trigger' => '47f1c3d6-cfab-468f-b1a6-3cf6ef3a9284',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/ed60e47b-d387-4318-aa65-e98001a3ecff:c3058d8ec4cc6592f3e3aae732911ef325486d300b5420285f99d93126cfd6c5',
                        'trigger' => 'ed60e47b-d387-4318-aa65-e98001a3ecff',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/066a4394-40c6-43ec-a156-dfc631a5847f:a412012ef4ac5aad503b8cab6461db068636ea00b6fc4be039c784e66c6b240f',
                        'trigger' => '066a4394-40c6-43ec-a156-dfc631a5847f',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/b20d3868-00f2-4238-9800-29aaf7dd0e60:ba2ffa48f4f5f0bcc654caf3c40f24a54f836d65f1abdc691b4a3699dac14cc0',
                        'trigger' => 'b20d3868-00f2-4238-9800-29aaf7dd0e60',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/501c2546-0d01-425b-b9e2-ca8902242c2a:afc767cf38ecaaf16c5e4a91f1ef11b0d8f68b62968981b561fceb6d786c6cfb',
                        'trigger' => '501c2546-0d01-425b-b9e2-ca8902242c2a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/b5137d0d-5ec1-47d9-90dc-2b1ea0ed88e5:5a001b13f7a9a418d36253304d5b7436f391f97cfc5582c5e8ec96e8c34db2fe',
                        'trigger' => 'b5137d0d-5ec1-47d9-90dc-2b1ea0ed88e5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/0248102a-42e6-4c80-a4fd-8c2eedf95dc4:0b9cdde6f0ac26a24725e912ff392045f6f68ea6a445e659a21b2eae40639d2e',
                        'trigger' => '0248102a-42e6-4c80-a4fd-8c2eedf95dc4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/438768e7-d06f-4ebf-a63e-7e32b6989b80:725057c8436dd05bf169036eebc526863082a1c41d4d16e671ca582ab0fa94f3',
                        'trigger' => '438768e7-d06f-4ebf-a63e-7e32b6989b80',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/3005002b-2d73-4ffc-8108-6f2544111ece:7376c9a0ed034d752376282c30e3d5551ebb76a37122a6ecbee317e4b24644a8',
                        'trigger' => '3005002b-2d73-4ffc-8108-6f2544111ece',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/90d2c539-32c6-4571-b2ae-09a5d4b00267:9ba98f6c5816eff3dc08d68d0b00b23e6f10cbadaa2e6d11ec3ff1b142c8415e',
                        'trigger' => '90d2c539-32c6-4571-b2ae-09a5d4b00267',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/f415232b-b7e4-4272-9cfe-7ae9f84b08c3:2a61f2d1e3e7166e31385e2d27d1825c2acddd79d87d53c269fd30ca97540d14',
                        'trigger' => 'f415232b-b7e4-4272-9cfe-7ae9f84b08c3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/8caa6e2f-faa0-400e-a219-dc9815a286ea:8881ab8c7b939a11834b7203f0b597c82de253b6d0181ab1d10902ff3105b4f3',
                        'trigger' => '8caa6e2f-faa0-400e-a219-dc9815a286ea',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/686a7829-fba2-4747-acb4-0b35ef5059b2:67ce39b8f6f8ff70d71878332b82c54da7fd1153bc290daa76a7ac7d72727f30',
                        'trigger' => '686a7829-fba2-4747-acb4-0b35ef5059b2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/1a740806-88b5-40de-a852-1752cff07180:2f37643a167d8a3c786e85cf951c46089699cde086c1b4bf33eb1fc38df7b16e',
                        'trigger' => '1a740806-88b5-40de-a852-1752cff07180',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/2f9ebde3-afd1-409c-9186-538225d070a6:a3ffe08a47caa864e3660cb609d59b3a6c4f71e495507e5ac910e25a32419ef1',
                        'trigger' => '2f9ebde3-afd1-409c-9186-538225d070a6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/ad20c4ab-eb72-4389-ad88-490e1958be98:b4f015289db46c8d6500860e0dd8ee17615bf674c9a3b21982fc3d8faa86c9a5',
                        'trigger' => 'ad20c4ab-eb72-4389-ad88-490e1958be98',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/ac482b19-efd4-41ad-a7b5-dbebfcb20051:b45b199070abde7f8f23c5dfe2ea6610879769bb13fb1671f8afdfc3049f3904',
                        'trigger' => 'ac482b19-efd4-41ad-a7b5-dbebfcb20051',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/2b2aae24-9201-4cca-841a-32b001b87840:7e82c5d6b5d29a4fdf6d95638e1458d8eb50b73e4fa2604415e2033fe3a2cfc1',
                        'trigger' => '2b2aae24-9201-4cca-841a-32b001b87840',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/31127104-f19b-47bd-9ff2-c1708cbebc70:74d8901262842095e47a406997be5178adac7a1ad14a663e4e36b46408251d75',
                        'trigger' => '31127104-f19b-47bd-9ff2-c1708cbebc70',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/aa632290-62e4-42c9-a161-d8a45fa92106:65171149ccb7c5f45788a9941add1e581b66d0888ed4d861623ba0257daaf688',
                        'trigger' => 'aa632290-62e4-42c9-a161-d8a45fa92106',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'middle eastern',
                        ],
                    ],
                ],
            ],

            self::LUCA => [
                'uuid' => '011315cc-364b-4b63-b65b-8d8e16df106b',
                'name' => 'Luca',
                'gender' => 'male',
                'description' => 'Teenage white male, lanky build, messy ginger hair, wearing a black hoodie with ripped jeans and high-top sneakers.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/2631307f-6b13-4eda-b18f-61d4520b8cd3:50d48c7d8c856b3ae5821165e8358b7c2ac39525f2e490782794c641cb15be91',
                        'trigger' => '2631307f-6b13-4eda-b18f-61d4520b8cd3',
                        'prompt' => 'Ultra-sharp portrait of a teenage white male with messy ginger hair, wearing a black drawstring hoodie. Soft and natural lighting, highly realistic, 8K resolution.',
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/3ff7e5fb-4ea7-450b-8adb-a14897a33ccd:2b67d56d4f4114f30a4ebe3f646c544cf9ca5315cc2a91551f3bfc0feb700803',
                        'trigger' => '3ff7e5fb-4ea7-450b-8adb-a14897a33ccd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/d17da9ef-4cfd-47e5-9147-a1aebbb13c91:ee6a26a2c2cde3bf8b675b46173e1dd7962cc89de388add39f4518b0edd126b8',
                        'trigger' => 'd17da9ef-4cfd-47e5-9147-a1aebbb13c91',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/5d26cd94-3fe3-49bf-81a5-30e5aec5d805:3b23acd570a59e73721aff010d47e3e4047fafa41651f0ffda3d165d6f1ac666',
                        'trigger' => '5d26cd94-3fe3-49bf-81a5-30e5aec5d805',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/a1a2cfa4-c3dc-430c-8874-6a6c87cfe94d:5aafc246dae4019bfc94158f8045f20d315706cff2db7047830c75909129ce9f',
                        'trigger' => 'a1a2cfa4-c3dc-430c-8874-6a6c87cfe94d',
                        'prompt' => '',
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747907183:28b1a73f8951b4f59b5e75ce8b1338d40d0d2c18435e8442e895b12d5b029e65',
                        'trigger' => '60263c90-6f27-49e4-90ad-dc1de4e23ac8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/d4b03cd5-488d-4ea7-a0a6-ac145d5c3b31:b10fd1f047e9fbbe01400ae63e99f39aed4fe8fa0d2ea46fd8c01e4fb0abaa6e',
                        'trigger' => 'd4b03cd5-488d-4ea7-a0a6-ac145d5c3b31',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/68c90309-d66a-4f3b-8f82-5cf5a7858a83:9026b764d9594beee2aa37130ec81617244836a1f48f4ddbce861f22c96bf301',
                        'trigger' => '68c90309-d66a-4f3b-8f82-5cf5a7858a83',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/c7277fc7-2ab3-442e-b389-fbfb6fa12fd7:842b79e7ccb39873549bdc895e56fd627cc20afaf28aedb2b24d5d5615dbfaf4',
                        'trigger' => 'c7277fc7-2ab3-442e-b389-fbfb6fa12fd7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/de4e2121-731c-49ff-8792-44fef6cafabc:b288ffca78fb02b719da3b46ea13d5a9550db3a6e9a44cf717eb2ebe817a949f',
                        'trigger' => 'de4e2121-731c-49ff-8792-44fef6cafabc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/86d1786e-3fe4-46f4-a16f-bc85c2c1212d:f3d7f44bd2fd6c14526be98c3fb6644c6e29c31f724ced47071dbb5b685f5221',
                        'trigger' => '86d1786e-3fe4-46f4-a16f-bc85c2c1212d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/6c94cb5d-ba1b-4f66-8a93-cfec843b755d:340bddf41aba5e0d04e5fb8f7818aecf50a785df9885ed610f4b88ecb7e4a1f7',
                        'trigger' => '6c94cb5d-ba1b-4f66-8a93-cfec843b755d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/eee19181-02f1-452f-bc8b-940ce47ccfa1:d32d8294a59cc021466f97a92e04bdf03e6db6897c61db28a5d544c2209b8bf6',
                        'trigger' => 'eee19181-02f1-452f-bc8b-940ce47ccfa1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/842c61c1-760a-43e2-952d-076576358b51:e240470ef57668f67c9c30c90410151fa376d52629c19034385548494cb55cad',
                        'trigger' => '842c61c1-760a-43e2-952d-076576358b51',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/72480bd7-c73c-4f86-b7ff-9bfe75bc50d3:1b167750cd7cc64f9d8ed7811ca7c689bacd5464a10c3b9025a9a970d67eaa7f',
                        'trigger' => '72480bd7-c73c-4f86-b7ff-9bfe75bc50d3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/a29f90a2-401b-40a4-9d68-9a39b85272cb:9e0b58817fb883a99d9916f0af8d4c72f5472a7fb790e2bcac29bc17ab6ca86b',
                        'trigger' => 'a29f90a2-401b-40a4-9d68-9a39b85272cb',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/1c159bb2-79ec-4809-95f6-42ae55224f65:fa3310e233e101e9e27528784d45a6f02143e5264ea25baf9ab05c77b05513d0',
                        'trigger' => '1c159bb2-79ec-4809-95f6-42ae55224f65',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/7a19c0b1-d0a1-4df6-8baa-28d2a46c96c4:65e282a2ad3e28225e1fe389a0c4f176f57a0b0e858f8f00d526e59b17eb92df',
                        'trigger' => '7a19c0b1-d0a1-4df6-8baa-28d2a46c96c4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/734e2a1f-72de-40b9-8d42-9609aefed500:0e9e4ab4f93f9ec961274d1aa0bfaff233cb8cd0c045f9a4be990c597d0ac6d5',
                        'trigger' => '734e2a1f-72de-40b9-8d42-9609aefed500',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 13 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/8c177b38-29af-48a6-b25b-b104fed97fd8:eafa64a2022462fa3a51cc1466a09b886478dd3dd112fb68df747dfeb0e65ce3',
                        'trigger' => '8c177b38-29af-48a6-b25b-b104fed97fd8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/aed54a01-eab9-4b82-a9c9-5c3323a81112:43275133664f397f8a82507d8e8f376f87a5057845d9d18dd9dcf30851ebc4b7',
                        'trigger' => 'aed54a01-eab9-4b82-a9c9-5c3323a81112',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/a73d9f5b-83d8-4b54-bed9-7a9bb540e5d2:7a9e54c7b90d4d9de1a1e42665391b9c23f48565ed6e9e9f2daab5c184f3b01a',
                        'trigger' => 'a73d9f5b-83d8-4b54-bed9-7a9bb540e5d2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/568b79c8-7387-4d6b-87e9-9e2c6423bdbe:290549148d662446a85b9382564feed79b86d23026c5d9c9233be17abed87374',
                        'trigger' => '568b79c8-7387-4d6b-87e9-9e2c6423bdbe',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/7970a29f-56d4-43d0-aa3b-934f90904116:242508d66012a39f31d4fdc1719b6a8cb11590ea3af73195ece324abb1514b2f',
                        'trigger' => '7970a29f-56d4-43d0-aa3b-934f90904116',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/cecb71e4-b24f-4280-8026-830c7a2e59f7:76dbbc36fbf4aaa0cc6bceee41a1d76514cb5ee4b584fde8bd765abab880210f',
                        'trigger' => 'cecb71e4-b24f-4280-8026-830c7a2e59f7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/8e2f697b-e417-481f-8481-c21f23a31e53:d572fb4bf0f057b883d4951e67729df51ddcd9c0306cd89be6ab52b2e768eb60',
                        'trigger' => '8e2f697b-e417-481f-8481-c21f23a31e53',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/de0995a1-13ec-4c94-911b-6912bdcb77de:0ec310288fbdab37731024a288e217876e20401f7c51a65c4e2eb902f7bffe4f',
                        'trigger' => 'de0995a1-13ec-4c94-911b-6912bdcb77de',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 25 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/4cb23a4a-64d6-40b3-ab45-6eaacb29c199:c21cbaf986f5379610171165dfad90f8a6f8dac4331c4da05da65d522a482bbf',
                        'trigger' => '4cb23a4a-64d6-40b3-ab45-6eaacb29c199',
                        'prompt' => null,
                        'details' => [
                            'age' => 'young person, around 20 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/ddfbf27c-ce8f-4adf-b5bf-9e130453a742:d1b58a15d0a8e49f2f2d84dc83d411556d2d3b79dad5747c61abf0aa39eb9da5',
                        'trigger' => 'ddfbf27c-ce8f-4adf-b5bf-9e130453a742',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'caucasian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/f13b00e3-0224-4f76-b24e-5319fc7bef99:b7b8549144284f55e906dfb077948586aedfce07585223c76b5ca6b7337ba125',
                        'trigger' => 'f13b00e3-0224-4f76-b24e-5319fc7bef99',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/bfeee0d8-0c0f-4bbe-ad60-fe1b1f5dbb37:dea42ca0338d05bb67530148bca38ea65e274fcb86de4de807b57119e8f9c638',
                        'trigger' => 'bfeee0d8-0c0f-4bbe-ad60-fe1b1f5dbb37',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/40a79d9c-811d-451e-849b-bba4cd33d7c1:a443b923721acd435a763289febbf6852d99556b601215b8804fbebca3a22b34',
                        'trigger' => '40a79d9c-811d-451e-849b-bba4cd33d7c1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/cb9c1fc7-52e1-4bff-8402-7740217e8cd5:91d01e02fc4862af98ba478b11ef3f33f68a5e1e82d0e8ac21b402dbc3ad74a9',
                        'trigger' => 'cb9c1fc7-52e1-4bff-8402-7740217e8cd5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/8f9a1fac-1365-44e9-818d-4b725e801222:f4d5bf8ec0f8439af9926c3ab33d17864cc15d130cd47298ac42c2fdfff4b632',
                        'trigger' => '8f9a1fac-1365-44e9-818d-4b725e801222',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/c7af318d-efb6-4dca-b6b4-c26f3b57fab2:7b9dfddb0e1885e97b99979028ca8b8e7739a437ac061cb149187873a55b8bd2',
                        'trigger' => 'c7af318d-efb6-4dca-b6b4-c26f3b57fab2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/627ace1d-01df-4227-892a-cd2d71076890:f2ed804b379843888d8278cbce28282f1725c5c0ed32065eca7a3abcd3c5a5fc',
                        'trigger' => '627ace1d-01df-4227-892a-cd2d71076890',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/8bb39c61-1a4f-49a3-8cdf-1acd57a3a604:7d53380134d3fe2d474ce91b83e10e66adb17b893c757f2f6145cbda5b12b442',
                        'trigger' => '8bb39c61-1a4f-49a3-8cdf-1acd57a3a604',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 13 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/9e2d07fa-b5c0-42c3-99a5-c97a175b12dd:92e9e54773dbe2b2b27a8f10b1e591c8f2520e0541b2cfd188ccc830da5bd130',
                        'trigger' => '9e2d07fa-b5c0-42c3-99a5-c97a175b12dd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/be498a87-c277-40de-95f0-872d84bf702a:117996dd1c61d16833cbce79b86607126e122c2c148304462ee8df7a044b405f',
                        'trigger' => 'be498a87-c277-40de-95f0-872d84bf702a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/f13afee3-2657-4f51-9e2b-ef4ca99e3044:56563cbed5f5a77a6354b3f627aebdb3361a65135335eec1f824bdd38ed42570',
                        'trigger' => 'f13afee3-2657-4f51-9e2b-ef4ca99e3044',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'white',
                        ],
                    ],
                ],
            ],

            self::HARUTO => [
                'uuid' => '90791b6e-d309-4c84-8d52-399b1295b8b0',
                'name' => 'Haruto',
                'gender' => 'male',
                'description' => 'Adult Japanese man, slim and tall, clean-cut with jet black hair, wearing a fitted navy suit and thin glasses.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/b1bb5a8a-c0ea-47dc-a104-833779ddb3ce:ebbfdf7b1bbfd2dcb42d94f990c351a3116ba0d0713fb709eccacf345ea43170',
                        'trigger' => 'b1bb5a8a-c0ea-47dc-a104-833779ddb3ce',
                        'prompt' => 'A sharp headshot photo of a young Asian man wearing a navy blue suit with a white shirt, a black tie and black-framed glasses. Soft and warm lighting, 8k, realistic',
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/409a05a6-cc05-40d4-99c2-d924ce24d2a3:58f17bc7139a056fa3c0b16ccec0133678ea2f5fd9282c5e9de4ba4282f3000b',
                        'trigger' => '409a05a6-cc05-40d4-99c2-d924ce24d2a3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/593c11cb-c23c-444e-94eb-47592b65b048:c0a67f5eeda97d4e1ca3ac51b401b0ad5c7089cb9067ad223728e51cc1ebd31a',
                        'trigger' => '593c11cb-c23c-444e-94eb-47592b65b048',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/df5ea771-8138-4e3f-894e-5c56eff2c7f3:0d6766446174fd0690e81833b06c96aad4ca062c28b8c729eafae0e9f42b05c2',
                        'trigger' => 'df5ea771-8138-4e3f-894e-5c56eff2c7f3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/17d9a679-0edc-43d3-840a-8f3a4ef01c63:6e816e25b98f9b979f2589cdfdd52712673d10bc7b409c4aa9b176bb060a9c49',
                        'trigger' => '17d9a679-0edc-43d3-840a-8f3a4ef01c63',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/63f5cab7-a8f9-4089-b4cc-ab044038bf0d:ebf903dcbc03e20fe35c0bc2ba8ae63562ebb7114b555cb91ec2aeda48e9692c',
                        'trigger' => '63f5cab7-a8f9-4089-b4cc-ab044038bf0d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/662a6a9d-88ea-418e-a522-fdb22e4e6d58:37bf675227a09356e4cf3549e1d0f622e368515a98e569ddb5dd1bb8c770919a',
                        'trigger' => '662a6a9d-88ea-418e-a522-fdb22e4e6d58',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/28cc4cd8-61bd-44a1-b998-6932882bf782:691e045905dbb062e212627e4825ac59b66471d98f5d7fe6425582f7f614af6f',
                        'trigger' => '28cc4cd8-61bd-44a1-b998-6932882bf782',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/96f4b07c-8d27-47d7-a62a-db5471ce00a0:aca04413bab79ecf51826651ed250c475db76a3f1fcc798afef0e7a1b188cf5b',
                        'trigger' => '96f4b07c-8d27-47d7-a62a-db5471ce00a0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/4db55519-a6c1-47ca-9257-9ec93d538f97:482d49018f33b98dc1d45c4346cb1041594ed26f711dc0cedcd017685f132b23',
                        'trigger' => '4db55519-a6c1-47ca-9257-9ec93d538f97',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/41103c2f-c78a-4b45-a658-290ba7d5796c:dfd783153d2b3dd317c06d1bf0a8fccbc5c036456fc3d316d48e9c020c95dd68',
                        'trigger' => '41103c2f-c78a-4b45-a658-290ba7d5796c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/f7f59621-9d21-4bab-978b-819621362d7a:436bc9d573aeebe1303c4384f3abfc41b35ac77146c9f4250f6bfba64f6cc514',
                        'trigger' => 'f7f59621-9d21-4bab-978b-819621362d7a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/03878184-5848-4377-8b11-a7fa3e55451a:cb51fed8feb98c5bb3df2b3f6fc95c5dcb4a07159ea537a42cdea04d32efff4b',
                        'trigger' => '03878184-5848-4377-8b11-a7fa3e55451a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/a8d2f0d7-ff1c-442b-9e97-431d03a8fd24:1fc9af77109ac16ab0639f362d971810924dcfc4f2ee2c1064e3b686955619b9',
                        'trigger' => 'a8d2f0d7-ff1c-442b-9e97-431d03a8fd24',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/a7cf20ba-2345-49ce-b48a-fe0ec8878803:aa63357f5c8285d8b2b4c712a3cb1ba77c6d3a744f6b4e6fdc674f290894dccd',
                        'trigger' => 'a7cf20ba-2345-49ce-b48a-fe0ec8878803',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/ceb18dd1-eede-4dad-b94c-8e7f1f2aae7c:80a0ca9d9f0b7d8d74fcdf288f19e0f847025b8d6a772c24077317ff11fe3bd4',
                        'trigger' => 'ceb18dd1-eede-4dad-b94c-8e7f1f2aae7cb',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/279e4a3d-babb-4cd7-91e2-11abdbace47a:bc8919f64fecee6401636c0445163abd0a8bcc46c05e633804d8670d0587edba',
                        'trigger' => '279e4a3d-babb-4cd7-91e2-11abdbace47a',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/035bb04e-f216-4d5e-9003-101fe347dda7:24bfc7901f6bec6ff9c6d79a9aa6ae35032b2fdbb31fa90a084abdaecc42bece',
                        'trigger' => '035bb04e-f216-4d5e-9003-101fe347dda7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/dd16bfd9-940c-4cf2-9af8-33dda7c075ef:3d093e159933dbe1148cad43bc5a616d32b9242e159a95329c8c446167eb6084',
                        'trigger' => 'dd16bfd9-940c-4cf2-9af8-33dda7c075ef',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/e91c180d-4329-4c05-8f18-898f36153016:5a263ee6c379e1be7e80f2ab7b92725c72805e24408f0f3598165442d72d4b77',
                        'trigger' => 'e91c180d-4329-4c05-8f18-898f36153016',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/f0594d3e-83ca-4cad-a94c-d6b863c2df76:bc865a140d2262904b5bc728b5aa936a38a65b655437ced33346c916548bd735',
                        'trigger' => 'f0594d3e-83ca-4cad-a94c-d6b863c2df76',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/c6f782d6-c26e-4bdb-a7cf-2468f08a3f3b:4bd40ff35ec54594140f62c4f0e682d8b214ffb4a93971758ac17d958eebe144',
                        'trigger' => 'c6f782d6-c26e-4bdb-a7cf-2468f08a3f3b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/4444fb4e-abe4-4dc4-a76b-23c4b94422c2:7a1efd5c69c74a352436c2782987bba4756cd4996d4773f2126e9d528ff748a1',
                        'trigger' => '4444fb4e-abe4-4dc4-a76b-23c4b94422c2',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/3866c994-9aba-4117-90c7-91e658b727b1:cb53fb08ba526f632f11f8966960a6e6bda26e848aa65868fbcdad6a3d5d5395',
                        'trigger' => '3866c994-9aba-4117-90c7-91e658b727b1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/a4b0018c-c19f-46cc-81c8-a042263108f6:a16b7f1b41c5efa8985aeeefaf6cee1f07adf3cd75645922a6ef46bbda65582c',
                        'trigger' => 'a4b0018c-c19f-46cc-81c8-a042263108f6',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/c20d926f-e8cf-4125-b8db-26b86dab39bd:270e20dd60073fe959d3ca493962885763066ca187d7f8e234bb75b54b8bed5d',
                        'trigger' => 'c20d926f-e8cf-4125-b8db-26b86dab39bd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/57a8821d-4b7e-4982-a9e4-31a9ecc9a57c:0f70c76e322b9ffc687b16cf9be34035ad08787be32632e8d7451d4dd1bc6cda',
                        'trigger' => '57a8821d-4b7e-4982-a9e4-31a9ecc9a57c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/e5fcf1fb-6626-41d8-9f56-c4ed58a955c9:a4968b5014680fab75bf0d895dd5a3e4ce5b2c9c5851cf9e8016c59a9e8c5980',
                        'trigger' => 'e5fcf1fb-6626-41d8-9f56-c4ed58a955c9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/11d7632f-c589-4a00-b4c8-8c32d47ebb3c:5e46ca42b9baa618ac9914d20804dab5f1ba8bdc6ab7da2eaf33e9e308c249b5',
                        'trigger' => '11d7632f-c589-4a00-b4c8-8c32d47ebb3c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/4c12bd08-d990-4034-a5ca-32dd3992e113:8f51b340f1e071c7db8c374fa2c9fd326d1e02a08db2485a36f8961c3e2810cf',
                        'trigger' => '4c12bd08-d990-4034-a5ca-32dd3992e113',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/44ec8030-f645-4518-974b-74c8cc2538fd:7274a1f5bc0241bd22e460e545b1d3429232ae029fb183deecdfecbeac75b0e8',
                        'trigger' => '44ec8030-f645-4518-974b-74c8cc2538fd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/586474b1-26cc-4695-b425-156011e7f319:08379b26cc56ada368c8212a270adb999513f2b65172ace2642be8cfaff8caf6',
                        'trigger' => '586474b1-26cc-4695-b425-156011e7f319',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/5d6c07f3-4eef-4d65-85d9-202924a33f46:a10ec33cebc7c046ae3d28a082b171ed3107d355ed59dbe8b6712fd545edab14',
                        'trigger' => '5d6c07f3-4eef-4d65-85d9-202924a33f46',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/9d6479ae-8cd6-440b-a8a9-f882f9a0a1cc:f131b162e838bf13d83c34f7d78eb060aed77c5e9d35310d327cdc59baaf801b',
                        'trigger' => '9d6479ae-8cd6-440b-a8a9-f882f9a0a1cc',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/6e1537f0-6db5-4af0-9c09-b9d78d381c79:952220ba08852e7e3de6009ab39b60954aa52a4d1f54c95c9e83d9eeb1a58860',
                        'trigger' => '6e1537f0-6db5-4af0-9c09-b9d78d381c79',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/d45f576c-cb0c-4ce4-8c0e-12a0e93fbe82:4cb4df636ba1e77409b55b42ec2c06e1475afb63504d8873851341df49ecadfd',
                        'trigger' => 'd45f576c-cb0c-4ce4-8c0e-12a0e93fbe82',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/2a83431b-5b06-4597-b844-631a22dcaf6c:c693e2a2156b71da70be6fd7af33439b7b1086d29e6d9e1870b2f04701c180bf',
                        'trigger' => '2a83431b-5b06-4597-b844-631a22dcaf6c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/803046ac-5a3d-446e-98f1-0e9dabe37772:0017cc0612259ea3217e3132308dd56774456d5fc5b7c3fad87a6ead9d43241d',
                        'trigger' => '803046ac-5a3d-446e-98f1-0e9dabe37772',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/f749ed49-a00e-48a7-9345-23196e2002d3:a16b8446f809f3bd35e381d072f615bbe2a60036f5a331f1984009ca8a591641',
                        'trigger' => 'f749ed49-a00e-48a7-9345-23196e2002d3',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/6c3eefd5-290a-475e-8fa8-3e89399c55f0:410572947a2c2320cb00aa9d742b2f2c4f773bbba6744bc6b3acc6510e4d652c',
                        'trigger' => '6c3eefd5-290a-475e-8fa8-3e89399c55f0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'in their 30s',
                            'race' => 'asian',
                        ],
                    ],
                ],
            ],

            self::AMARA => [
                'uuid' => 'cf06f773-76ee-4de1-8e12-65ead91d7ee9',
                'name' => 'Amara',
                'gender' => 'female',
                'description' => 'Young biracial woman (Black & White), medium build, afro-textured ombré hair, wearing a cropped leather jacket over a graphic tee.',
                'genres' => [
                    StoryGenre::ACTION_MOVIE->value => [
                        'model' => 'syllaby-ai/57097b94-df40-4c6d-8d53-7dd99154b752:44355a788da9a1a7087ab16aff1ac3e3683933cff8060afc3e88d8aa024fcb00',
                        'trigger' => '57097b94-df40-4c6d-8d53-7dd99154b752',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 6 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_CARTOON->value => [
                        'model' => 'syllaby-ai/8fc7966e-1754-40fe-b7b4-8d7bc4679015:f593fd07d741f02dba3d07b7e470a0e589eb2a87b98ebb2dc1d22cddc562a9ea',
                        'trigger' => '8fc7966e-1754-40fe-b7b4-8d7bc4679015',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 6 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CINEMATIC_REALISM->value => [
                        'model' => 'syllaby-ai/5e9f321f-c840-489a-8487-9e866cc69313:fbe7007e3961ef8cc8408cb91b22e2160d1d5f6f3041844eaa8b1d5e2ceca31b',
                        'trigger' => '5e9f321f-c840-489a-8487-9e866cc69313',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::VAPORWAVE->value => [
                        'model' => 'syllaby-ai/7284190b-1d9d-41e3-ac74-631de7d828e0:8540f8cf15eacd921558ad7b2617bb1913398adfa89e06f752fcbbf1498ccec9',
                        'trigger' => '7284190b-1d9d-41e3-ac74-631de7d828e0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 17 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::COMIC_BOOK->value => [
                        'model' => 'syllaby-ai/bf779c86-4f9b-4b77-998d-7b9a7ec225b8:6d5b2337bf63c009febeef7a00f3751c3f135f682060a954f987ec00a2064ff5',
                        'trigger' => 'bf779c86-4f9b-4b77-998d-7b9a7ec225b8',
                        'prompt' => '',
                        'details' => [
                            'age' => 'teenager, around 15 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::REALISTIC_CARTOON->value => [
                        'model' => 'syllaby-ai/lora-1747908485:11befd7ecb4bab5ed528fc10b37474bfc22123ec3bcc62995b4c55cdf13de86c',
                        'trigger' => '38130730-e0db-4dbc-9ed5-3fdf9394f800',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DREAMSCAPE->value => [
                        'model' => 'syllaby-ai/1d53cd2f-ef62-476b-b474-f86a831ca1b0:cb32010c67ab1da957efe6d07d22b45353b3c0eb010a5c251f53df8b43a9e95c',
                        'trigger' => '1d53cd2f-ef62-476b-b474-f86a831ca1b0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BIBLICAL_HISTORY->value => [
                        'model' => 'syllaby-ai/8b31e8ed-e93e-454b-a12d-0072eb8f6d30:e1073695d6f277fa7c6b30d1c5cb6db78b102c94d6c372d8f0910f7be3dc35fc',
                        'trigger' => '8b31e8ed-e93e-454b-a12d-0072eb8f6d30',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::HYPER_REALISM->value => [
                        'model' => 'syllaby-ai/37ef74f0-ccd0-49d0-bad0-b67f95f9f079:9e38514d81f44e6c5badb3f632096dd50b2fc4c185a8afadb7cb9cd230254c24',
                        'trigger' => '37ef74f0-ccd0-49d0-bad0-b67f95f9f079',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::OLD_CAMERA->value => [
                        'model' => 'syllaby-ai/bbb0aa61-0191-4e24-b8cb-8b02ceb83dd9:1306a1d106c21825b991bda11ebec73124de117c28fefb0ef1e4d71487ba9795',
                        'trigger' => 'bbb0aa61-0191-4e24-b8cb-8b02ceb83dd9',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CRIME_MOVIE->value => [
                        'model' => 'syllaby-ai/8c421e51-3d68-4a55-b6f9-f3b80e56b59d:61158a06e20587f1c0ae1c0795684eae6a4146be33db604df1b25de7383ca819',
                        'trigger' => '8c421e51-3d68-4a55-b6f9-f3b80e56b59d',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DARK_FANTASY->value => [
                        'model' => 'syllaby-ai/8c1fe72f-38ea-4bb2-b122-8c54abfa13e4:0f7b2a92f0a95b42a8c6936178cb47f5b64138d28c55c19a8d0fc3aa9719ec41',
                        'trigger' => '8c1fe72f-38ea-4bb2-b122-8c54abfa13e4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::HORROR_FANTASY->value => [
                        'model' => 'syllaby-ai/5899160f-5c73-4455-8d60-d46111544462:d34cc4006b70efdb719e1296ad82dac5e6cca0c89d767578d930789b5222224e',
                        'trigger' => '5899160f-5c73-4455-8d60-d46111544462',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 13 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::GREEK_MYTHOLOGY->value => [
                        'model' => 'syllaby-ai/aced8f93-a3bd-4e51-b596-a7174620bc7e:4c41d558e05d070be791634057a1581338dea3971614d41bd8b27a452cad8b74',
                        'trigger' => 'aced8f93-a3bd-4e51-b596-a7174620bc7e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 15 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::FUTURISTIC_SCI_FI->value => [
                        'model' => 'syllaby-ai/75929b4b-452b-4d51-ae35-951fe63dfc37:45d551468c9ee95020c27f502aeda64d455c1fa5ab7d61c6713af7ec30dd5380',
                        'trigger' => '75929b4b-452b-4d51-ae35-951fe63dfc37',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 14 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_EGYPT->value => [
                        'model' => 'syllaby-ai/0aaf7215-6f16-4739-b062-f4f9e9629f29:9cd70a94428cc645b500cc17fd911af55c653b16e2fbd0ad92b5214a484bf1cf',
                        'trigger' => '0aaf7215-6f16-4739-b062-f4f9e9629f29',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PIXEL_ART->value => [
                        'model' => 'syllaby-ai/262a6220-8be4-4ed3-aa6f-7818fd579ec4:4d09e958496f62e12e9dcec487b5c261cbc7ffa1bb3d5a816f67f320d0512e22',
                        'trigger' => '262a6220-8be4-4ed3-aa6f-7818fd579ec4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CARICATURE->value => [
                        'model' => 'syllaby-ai/d2f442a5-bb71-456e-ab41-4e7a8334bbd1:1cb70f6851cad84c85b9eba9fbf75206547716c7106fa7901fb6707735ee2663',
                        'trigger' => 'd2f442a5-bb71-456e-ab41-4e7a8334bbd1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::WESTERN_PUNK->value => [
                        'model' => 'syllaby-ai/4a1a88ed-0688-44c3-8573-2756643d7b41:dd2b24ea318d1d2b05a5227b89a59e82d4b06b6117a07dd3135a54c466c735c8',
                        'trigger' => '4a1a88ed-0688-44c3-8573-2756643d7b41',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::WATERCOLOR->value => [
                        'model' => 'syllaby-ai/3bc3a8d3-f21c-4413-94c3-3c092a1facce:510951b77e18a8cb3076660baa14b951322fe47969bf0fc2e6ba0a41c1fbd300',
                        'trigger' => '3bc3a8d3-f21c-4413-94c3-3c092a1facce',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 15 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_INDIA->value => [
                        'model' => 'syllaby-ai/3c92e0ca-0e25-40d9-b0d5-c98717653661:03641041ec94e32dfc41d9a9110ecc263840a9f533ec4ecec73714c70da9c483',
                        'trigger' => '3c92e0ca-0e25-40d9-b0d5-c98717653661',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::NATIVE_AMERICAN->value => [
                        'model' => 'syllaby-ai/d34b8164-b735-4a55-a141-0e8fb298fff1:28a9efba356e151178fd1091f3d1715b2e1494342dc581d7ba8c851cb6c72b04',
                        'trigger' => 'd34b8164-b735-4a55-a141-0e8fb298fff1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::VIKING->value => [
                        'model' => 'syllaby-ai/72848636-6d64-431a-bf2f-afdbdb8994f0:26e12c864de74a03dcf2fd4f7e90626ff7df29f2796bf92336e66e90e11fd22e',
                        'trigger' => '72848636-6d64-431a-bf2f-afdbdb8994f0',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::STEAMPUNK->value => [
                        'model' => 'syllaby-ai/d8527518-06df-4cda-b73d-1295faa11585:bf51f802edb3ca3898ccf80bec32ea4a63c8e55b125c725fed37ef6713f77fa0',
                        'trigger' => 'd8527518-06df-4cda-b73d-1295faa11585',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BLOCKIFY->value => [
                        'model' => 'syllaby-ai/4e6d26bc-4a01-43f3-8a93-0b6217afbd44:71f444c99148c1e4adcc7d5ac8b06a66d0277782938dfdd86b5dd00291f09fb7',
                        'trigger' => '4e6d26bc-4a01-43f3-8a93-0b6217afbd44',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CLAYMATION->value => [
                        'model' => 'syllaby-ai/08ef2414-004a-4772-ae7c-e934668d942c:bbfc5c147c73b55e772dbbe0e5b7a8795914f07af2b591205e31de40d8b9045c',
                        'trigger' => '08ef2414-004a-4772-ae7c-e934668d942c',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PENCIL_DRAWING->value => [
                        'model' => 'syllaby-ai/5913d975-1b14-46b1-b0ca-197526e17e66:cc94054e1b76bfa2f0c338d6bda74181d2c7fc1ef2e5602d29f0a0942b49c0a2',
                        'trigger' => '5913d975-1b14-46b1-b0ca-197526e17e66',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::APOCALYPSE->value => [
                        'model' => 'syllaby-ai/d7f75cc6-7094-4fa4-a7e9-72650320e1d4:1e330d5987c394ec7ffd1aa2d663a89b5b43e0bb11cba7a5ae256364a67161a8',
                        'trigger' => 'd7f75cc6-7094-4fa4-a7e9-72650320e1d4',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANCIENT_FAIRYTALE->value => [
                        'model' => 'syllaby-ai/9923bace-d1be-4ffb-a4b7-de024e947e61:0dad836cffe9e9626bdd99e36ed8c24e6cf5aeb66cf7dca23d12a8e0c1f67436',
                        'trigger' => '9923bace-d1be-4ffb-a4b7-de024e947e61',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::BLACK_AND_WHITE->value => [
                        'model' => 'syllaby-ai/00adb8ad-72be-4474-9dff-173f4326314b:855dd11e8d8e35fa27188410262d3faf3509ac18f8be2dc66c104c22d39b367e',
                        'trigger' => '00adb8ad-72be-4474-9dff-173f4326314b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 12 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ANIME->value => [
                        'model' => 'syllaby-ai/6e3e0e12-90b0-4fa2-9814-08760bd2b1d7:c31f251e376e13b32d2f49c69429988c20e70d30ffe3464e96fada193c569d07',
                        'trigger' => '6e3e0e12-90b0-4fa2-9814-08760bd2b1d7',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 19 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::DIGITAL_ART->value => [
                        'model' => 'syllaby-ai/f0f68169-6e0a-4e72-9bc9-80ef677a5cb8:7e91f3a7c2fc2b4f775f5a64a4ade328d45f0be4a34a313a989c3c527f2a9bcc',
                        'trigger' => 'f0f68169-6e0a-4e72-9bc9-80ef677a5cb8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::MECH->value => [
                        'model' => 'syllaby-ai/12bb27a7-5418-4f77-80d5-3af2338785f1:ce39d33ec3fd45cba0ec92386ace8c3f48fbd1cdcd74ba6aa00790cb8430ac7e',
                        'trigger' => '12bb27a7-5418-4f77-80d5-3af2338785f1',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::RETRO_ILLUSTRATION->value => [
                        'model' => 'syllaby-ai/e932804f-da0e-49cd-8318-328ec15b480e:b58bee15dd08e4aaad37ae7cd0563455191fb89b6ed191733b115e5bf017c8eb',
                        'trigger' => 'e932804f-da0e-49cd-8318-328ec15b480e',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::POP_ART->value => [
                        'model' => 'syllaby-ai/beb49fd9-b5ac-49d7-8a47-d00e73a6b802:2a98e10ec1ad64503700341756967efd82536ab8a56781bf02ead68dd6978398',
                        'trigger' => 'beb49fd9-b5ac-49d7-8a47-d00e73a6b802',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PSYCHEDELIC_ART->value => [
                        'model' => 'syllaby-ai/0be3cab5-6808-45dd-a730-55bdfdc40b57:849c5c890d57591a205ba4132f2fe2c13f9dde7fd61c3655f3fa62cba5fdf04e',
                        'trigger' => '0be3cab5-6808-45dd-a730-55bdfdc40b57',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::CYBERPUNK->value => [
                        'model' => 'syllaby-ai/373b16c8-f090-471e-aeee-33a74f1429e8:ea12a0c5c06e58a6a192403bd60356a8f6da3f0a86384adcb2196f6b64fe0354',
                        'trigger' => '373b16c8-f090-471e-aeee-33a74f1429e8',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::ANIMATED_FANTASY->value => [
                        'model' => 'syllaby-ai/a2b3b5c8-8255-46e4-91b5-cbd687355f4b:67aca274532c2fa63a227d167112254b9af259325038e6bbbe4747abe1800614',
                        'trigger' => 'a2b3b5c8-8255-46e4-91b5-cbd687355f4b',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::PAPER_CUT_ART->value => [
                        'model' => 'syllaby-ai/05580397-fb71-4ce3-9809-c1ec0df7b7f5:e32ff99bf6c2b4792a4b0613090184f2cc8460c6e6fa44d863528b00fdeaffda',
                        'trigger' => '05580397-fb71-4ce3-9809-c1ec0df7b7f5',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 16 years old',
                            'race' => 'asian',
                        ],
                    ],
                    StoryGenre::SELFIE->value => [
                        'model' => 'syllaby-ai/2e8418fb-8cfc-4fa8-9941-eb8cab1dc3dd:c8d8d1944453434915cb8bf96294cbf45b0b8b676b16a761d2ed6f236fe8dcc3',
                        'trigger' => '2e8418fb-8cfc-4fa8-9941-eb8cab1dc3dd',
                        'prompt' => null,
                        'details' => [
                            'age' => 'teenager, around 10 years old',
                            'race' => 'asian',
                        ],
                    ],
                ],
            ],
        };
    }

    /**
     * Get all the characters.
     */
    public static function all(): array
    {
        return array_map(fn ($character) => $character->details(), self::cases());
    }

    /**
     * Get the model for the given genre.
     */
    public function genre(StoryGenre $genre): ?array
    {
        return Arr::get($this->details(), "genres.{$genre->value}");
    }
}
