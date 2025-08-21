<?php

namespace App\Syllaby\Videos\Enums;

use App\System\Traits\HasEnumValues;

enum StoryGenre: string
{
    use HasEnumValues;

    case POINT_OF_VIEW = 'point-of-view';
    case SELFIE = 'selfie';
    case MIYAZAKI_INSPIRED = 'miyazaki-inspired';
    case AFRICAN_LEGENDS = 'african-legends';
    case ACTION_MOVIE = 'action-movie';
    case ANCIENT_FAIRYTALE = 'ancient-fairytale';
    case ANIMATED_CARTOON = 'animated-cartoon';
    case ANIMATED_FANTASY = 'animated-fantasy';
    case ART_DECO = 'art-deco';
    case OLD_CAMERA = 'old-camera';
    case BLACK_AND_WHITE = 'black-and-white';
    case CINEMATIC_REALISM = 'cinematic-realism';
    case COMIC_BOOK = 'comic-book';
    case RETRO_ILLUSTRATION = 'retro-illustration';
    case CRIME_MOVIE = 'crime-movie';
    case CYBERPUNK = 'cyberpunk';
    case DARK_FANTASY = 'dark-fantasy';
    case DIGITAL_ANIME = 'digital-anime';
    case DIGITAL_ART = 'digital-art';
    case REALISTIC_CARTOON = 'realistic-cartoon';
    case VAPORWAVE = 'vaporwave';
    case FUTURISTIC_SCI_FI = 'futuristic-sci-fi';
    case GREEK_MYTHOLOGY = 'greek-mythology';
    case HORROR_FANTASY = 'horror-fantasy';
    case HYPER_REALISM = 'hyper-realism';
    case ANCIENT_EGYPT = 'ancient-egypt';
    case PIXEL_ART = 'pixel-art';
    case PAPER_CUT_ART = 'paper-cut-art';
    case MECH = 'mech';
    case CARICATURE = 'caricature';
    case WESTERN_PUNK = 'western-punk';
    case WATERCOLOR = 'watercolor';
    case BIBLICAL_HISTORY = 'biblical-history';
    case ANCIENT_INDIA = 'ancient-india';
    case NATIVE_AMERICAN = 'native-american';
    case PSYCHEDELIC_ART = 'psychedelic-art';
    case PENCIL_DRAWING = 'pencil-drawing';
    case VIKING = 'viking';
    case STEAMPUNK = 'steampunk';
    case POP_ART = 'pop-art';
    case BLOCKIFY = 'blockify';
    case APOCALYPSE = 'apocalypse';
    case CLAYMATION = 'claymation';
    case DREAMSCAPE = 'dreamscape';
    case LOW_POLY = 'low-poly';
    case STAINED_GLASS = 'stained-glass';

    public static function all(): array
    {
        return [
            self::POINT_OF_VIEW->value => [
                'name' => 'Point of View',
                'character_consistency' => false,
            ],
            self::SELFIE->value => [
                'name' => 'Selfie',
                'character_consistency' => true,
            ],
            self::MIYAZAKI_INSPIRED->value => [
                'name' => 'Miyazaki Inspired',
                'character_consistency' => false,
            ],
            self::AFRICAN_LEGENDS->value => [
                'name' => 'African Legends',
                'character_consistency' => false,
            ],
            self::ACTION_MOVIE->value => [
                'name' => 'Action Movie',
                'character_consistency' => true,
            ],
            self::ANCIENT_FAIRYTALE->value => [
                'name' => 'Ancient Fairytale',
                'character_consistency' => true,
            ],
            self::ANIMATED_CARTOON->value => [
                'name' => 'Animated Cartoon',
                'character_consistency' => true,
            ],
            self::ANIMATED_FANTASY->value => [
                'name' => 'Animated Fantasy',
                'character_consistency' => true,
            ],
            self::ART_DECO->value => [
                'name' => 'Art Deco',
                'character_consistency' => false,
            ],
            self::OLD_CAMERA->value => [
                'name' => 'Old Camera',
                'character_consistency' => true,
            ],
            self::BLACK_AND_WHITE->value => [
                'name' => 'Black And White',
                'character_consistency' => true,
            ],
            self::CINEMATIC_REALISM->value => [
                'name' => 'Cinematic Realism',
                'character_consistency' => true,
            ],
            self::COMIC_BOOK->value => [
                'name' => 'Comic Book',
                'character_consistency' => true,
            ],
            self::RETRO_ILLUSTRATION->value => [
                'name' => 'Retro Illustration',
                'character_consistency' => true,
            ],
            self::CRIME_MOVIE->value => [
                'name' => 'Crime Movie',
                'character_consistency' => true,
            ],
            self::CYBERPUNK->value => [
                'name' => 'Cyberpunk',
                'character_consistency' => true,
            ],
            self::DARK_FANTASY->value => [
                'name' => 'Dark Fantasy',
                'character_consistency' => true,
            ],
            self::DIGITAL_ANIME->value => [
                'name' => 'Digital Anime',
                'character_consistency' => true,
            ],
            self::DIGITAL_ART->value => [
                'name' => 'Digital Art',
                'character_consistency' => true,
            ],
            self::REALISTIC_CARTOON->value => [
                'name' => 'Realistic Cartoon',
                'character_consistency' => true,
            ],
            self::VAPORWAVE->value => [
                'name' => 'Vaporwave',
                'character_consistency' => true,
            ],
            self::FUTURISTIC_SCI_FI->value => [
                'name' => 'Futuristic Sci-Fi',
                'character_consistency' => true,
            ],
            self::GREEK_MYTHOLOGY->value => [
                'name' => 'Greek Mythology',
                'character_consistency' => true,
            ],
            self::HORROR_FANTASY->value => [
                'name' => 'Horror Fantasy',
                'character_consistency' => true,
            ],
            self::HYPER_REALISM->value => [
                'name' => 'Hyper Realism',
                'character_consistency' => true,
            ],
            self::ANCIENT_EGYPT->value => [
                'name' => 'Ancient Egypt',
                'character_consistency' => true,
            ],
            self::PIXEL_ART->value => [
                'name' => 'Pixel Art',
                'character_consistency' => true,
            ],
            self::PAPER_CUT_ART->value => [
                'name' => 'Paper Cut Art',
                'character_consistency' => true,
            ],
            self::MECH->value => [
                'name' => 'Mech',
                'character_consistency' => true,
            ],
            self::CARICATURE->value => [
                'name' => 'Caricature',
                'character_consistency' => true,
            ],
            self::WESTERN_PUNK->value => [
                'name' => 'Western Punk',
                'character_consistency' => true,
            ],
            self::WATERCOLOR->value => [
                'name' => 'Watercolor',
                'character_consistency' => true,
            ],
            self::BIBLICAL_HISTORY->value => [
                'name' => 'Biblical History',
                'character_consistency' => true,
            ],
            self::ANCIENT_INDIA->value => [
                'name' => 'Ancient India',
                'character_consistency' => true,
            ],
            self::NATIVE_AMERICAN->value => [
                'name' => 'Native American',
                'character_consistency' => true,
            ],
            self::PSYCHEDELIC_ART->value => [
                'name' => 'Psychedelic Art',
                'character_consistency' => true,
            ],
            self::PENCIL_DRAWING->value => [
                'name' => 'Pencil Drawing',
                'character_consistency' => true,
            ],
            self::VIKING->value => [
                'name' => 'Viking',
                'character_consistency' => true,
            ],
            self::STEAMPUNK->value => [
                'name' => 'Steampunk',
                'character_consistency' => true,
            ],
            self::POP_ART->value => [
                'name' => 'Pop Art',
                'character_consistency' => true,
            ],
            self::BLOCKIFY->value => [
                'name' => 'Blockify',
                'character_consistency' => true,
            ],
            self::APOCALYPSE->value => [
                'name' => 'Apocalypse',
                'character_consistency' => true,
            ],
            self::CLAYMATION->value => [
                'name' => 'Claymation',
                'character_consistency' => true,
            ],
            self::DREAMSCAPE->value => [
                'name' => 'Dreamscape',
                'character_consistency' => true,
            ],
            self::LOW_POLY->value => [
                'name' => 'Low Poly',
                'character_consistency' => false,
            ],
            self::STAINED_GLASS->value => [
                'name' => 'Stained Glass',
                'character_consistency' => false,
            ],
        ];
    }

    public function details(): array
    {
        return match ($this) {
            self::POINT_OF_VIEW => [
                'quality' => '8k, hyper-realistic',
                'style' => 'immersive first-person perspective',
                'type' => 'Point of View (POV), eye-level view, two hands or two legs visible',
                'mood' => 'immersive, natural, tactile',
                'lighting' => 'natural positioning, depth-focused lighting',
            ],
            self::SELFIE => [
                'quality' => 'realistic, high-detail, photo-like clarity',
                'style' => 'modern selfie, natural appearance',
                'type' => 'first-person close-up with visible arm or hand',
                'mood' => 'casual, expressive, candid',
                'lighting' => 'soft daylight, ambient natural light',
            ],
            self::ACTION_MOVIE => [
                'quality' => '8k, Hyper-realistic, intense',
                'style' => 'action movie',
                'type' => 'dynamic poster',
                'mood' => 'intense, fast-paced, thrilling',
                'lighting' => 'dramatic, high-contrast, neon glows',
            ],
            self::ANIMATED_CARTOON => [
                'quality' => 'classic',
                'style' => ' 2D retro animation, hand-drawn, aesthetic',
                'type' => 'cartoon illustration, bold outlines',
                'mood' => 'whimsical, exaggerated expressions, playful',
                'lighting' => 'flat shading, cel animation look, strong contrast',
            ],
            self::HYPER_REALISM => [
                'quality' => 'Ultra-Realistic, 8k',
                'style' => 'Photo',
                'type' => 'photorealistic',
                'mood' => 'high-end commercial quality',
                'lighting' => 'professional lighting, high-end clarity',
            ],
            self::DREAMSCAPE => [
                'quality' => 'smooth digital',
                'style' => '3D Digital render',
                'type' => 'pixar style animated illustration',
                'mood' => 'playful, exaggerated character design, expressive, rich dimensional depth',
                'lighting' => 'soft, luminous textures',
            ],
            self::BIBLICAL_HISTORY => [
                'quality' => 'authentic, hyper-realistic, 8k',
                'style' => 'biblical history',
                'type' => 'historical illustration',
                'mood' => 'epic, divine, serious',
                'lighting' => 'dramatic, soft golden rays, divine glow',
            ],
            self::COMIC_BOOK => [
                'quality' => 'vivid',
                'style' => 'comic book',
                'type' => 'classic comic illustration',
                'mood' => 'dynamic, expressive',
                'lighting' => 'bold contrasts, deep shadows',
            ],
            self::REALISTIC_CARTOON => [
                'quality' => 'realistic 3D cartoon',
                'style' => 'disney style, soft, expressive features',
                'type' => '3D cartoon',
                'mood' => 'dreamy, magical',
                'lighting' => 'warm twilight glow, gentle highlights',
            ],
            self::CINEMATIC_REALISM => [
                'quality' => '8k, ultra-realistic',
                'style' => 'cinematic photorealistic',
                'type' => 'octane render, artistic photography',
                'mood' => 'award-winning, masterpiece',
                'lighting' => 'soft, natural, volumetric, perfect composition light',
            ],
            self::VAPORWAVE => [
                'quality' => '8k, ultra-realistic',
                'style' => 'vaporwave synth',
                'type' => 'retro-futuristic aesthetic',
                'mood' => 'surreal, dreamy, nostalgic',
                'lighting' => 'bold neon glow, deep purples and electric blues, ambient mist',
            ],
            self::ANCIENT_EGYPT => [
                'quality' => 'sharp, 8k, hyper-realistic',
                'style' => 'ancient egypt',
                'type' => 'historical representation',
                'mood' => 'authentic, rich, cultural',
                'lighting' => 'natural soft light, diffused sunlight, dynamic shadows',
            ],
            self::OLD_CAMERA => [
                'quality' => 'aged, sepia-toned, analog film grain',
                'style' => 'vintage photography',
                'type' => 'retro portrait, archival photograph',
                'mood' => 'nostalgic, intimate, timeless',
                'lighting' => 'natural or early artificial light, soft glow, deep shadows',
            ],
            self::CRIME_MOVIE => [
                'quality' => 'gritty, monochrome, film grain texture',
                'style' => 'black and white neo-noir',
                'type' => 'dramatic crime scene, stylized realism',
                'mood' => 'tense, moody, suspenseful',
                'lighting' => 'harsh shadows, soft ambient glow, high-contrast noir lighting',
            ],
            self::DARK_FANTASY => [
                'quality' => 'high detail, gritty, cinematic',
                'style' => 'dark fantasy gothic',
                'type' => 'mythical world, atmospheric realism',
                'mood' => 'ominous, mystical, haunting',
                'lighting' => 'moonlit shadows, torchlight glow, foggy ambiance, glowing embers',
            ],
            self::HORROR_FANTASY => [
                'quality' => 'grim, grotesque, surreal detail',
                'style' => 'dark fantasy horror',
                'type' => 'eerie dreamscape, disturbing realism',
                'mood' => 'unsettling, nightmarish, oppressive',
                'lighting' => 'low light, harsh shadows, cold ambient glow, flickering torchlight, moonlit effects',
            ],
            self::GREEK_MYTHOLOGY => [
                'quality' => 'high-detail, lifelike, textured realism',
                'style' => 'modern greek mythology',
                'type' => 'mythic scene, sculptural composition',
                'mood' => 'divine, majestic, timeless',
                'lighting' => 'soft diffused glow, golden hour shadows, divine highlights',
            ],
            self::FUTURISTIC_SCI_FI => [
                'quality' => 'hyper-realistic, cinematic, surreal detail',
                'style' => 'futuristic sci-fi',
                'type' => 'alien world, advanced technology landscape',
                'mood' => 'epic, otherworldly, awe-inspiring',
                'lighting' => 'planetshine, bioluminescent glow, volumetric rays, soft ambient hues',
            ],
            self::PIXEL_ART => [
                'quality' => 'low-res, crisp pixel detail, blocky texture',
                'style' => 'retro 16-bit pixel art',
                'type' => 'grid-based scene, nostalgic game aesthetic',
                'mood' => 'playful, retro, stylized',
                'lighting' => 'high-contrast pixel lighting, bold limited palette',
            ],
            self::CARICATURE => [
                'quality' => 'high-quality, smooth, cartoonish exaggeration',
                'style' => '3D caricature, playful realism',
                'type' => 'travel portrait, stylized environment',
                'mood' => 'joyful, friendly, adventurous',
                'lighting' => 'bright daylight, soft shadows, saturated highlights',
            ],
            self::WESTERN_PUNK => [
                'quality' => 'hyper-realistic, gritty, textured detail',
                'style' => 'westernpunk, retro-futuristic frontier',
                'type' => 'anachronistic wild west scene, sci-fi fantasy fusion',
                'mood' => 'rugged, mysterious, stylized',
                'lighting' => 'dramatic contrasts, warm desert hues, eerie sci-fi glows',
            ],
            self::WATERCOLOR => [
                'quality' => 'realistic, hand-painted, fluid pigment flow',
                'style' => 'traditional watercolor painting, wet-on-wet technique',
                'type' => 'fine art scene with expressive brushwork and organic blending',
                'mood' => 'artistic, soft, emotionally textured',
                'lighting' => 'natural diffused light, soft tonal gradients, watercolor bleeds',
            ],
            self::ANCIENT_INDIA => [
                'quality' => 'ultra-detailed, culturally rich, sacred photorealism',
                'style' => 'mythological and divine',
                'type' => 'Indian epic scene, spiritual presence, traditional attire',
                'mood' => 'holy, majestic, meditative',
                'lighting' => 'divine glow, ambient mist, soft light',
            ],
            self::NATIVE_AMERICAN => [
                'quality' => 'high-detail, naturalistic, textured realism',
                'style' => 'native american illustration, cultural storytelling',
                'type' => 'land-based scene with wildlife and tradition',
                'mood' => 'reverent, grounded, harmonious',
                'lighting' => 'soft golden hour light, warm natural tones, ambient haze',
            ],
            self::VIKING => [
                'quality' => 'gritty, high-detail, cinematic realism',
                'style' => 'norse historical fantasy',
                'type' => 'viking scene with authentic attire and setting',
                'mood' => 'stoic, rugged, mythic',
                'lighting' => 'natural diffused light, dramatic backlighting, atmospheric fog',
            ],
            self::STEAMPUNK => [
                'quality' => 'high-detail, metallic realism, textured surfaces',
                'style' => 'steampunk, victorian-industrial fantasy',
                'type' => 'mechanical scene with brass elements and steam effects',
                'mood' => 'inventive, moody, vintage-futuristic',
                'lighting' => 'warm ambient glow, soft shadows, gaslamp highlights with subtle lens flares',
            ],
            self::BLOCKIFY => [
                'quality' => '8k, clean plastic surfaces, toy-grade rendering',
                'style' => 'building block toy style, plastic bricks, modular design',
                'type' => 'scene constructed entirely from interlocking toy pieces',
                'mood' => 'playful, modular, imaginative',
                'lighting' => 'toy-like glow, hard plastic shine, edge reflections on glossy blocks',
            ],
            self::CLAYMATION => [
                'quality' => 'matte, handcrafted, fingerprint-textured realism',
                'style' => 'claymation, plasticine modeling, stop-motion aesthetic',
                'type' => 'scene with handmolded clay figures',
                'mood' => 'playful, quirky, handmade',
                'lighting' => 'warm studio lighting, soft shadows, physical miniature highlights',
            ],
            self::PENCIL_DRAWING => [
                'quality' => 'realistic pencil rendering, fine graphite lines, visible pencil texture',
                'style' => 'high-fidelity pencil drawing, traditional hand-drawn art',
                'type' => 'detailed graphite illustration with realistic proportions and natural shading',
                'mood' => 'artistic, technical, observational',
                'lighting' => 'soft directional light with subtle tonal gradation, true-to-pencil shading',
            ],
            self::APOCALYPSE => [
                'quality' => 'highly detailed, textured, industrial realism',
                'style' => 'dieselpunk, retrofuturistic sci-fi',
                'type' => 'mechanical dystopian scene with exposed industrial components',
                'mood' => 'bleak, haunting, weathered',
                'lighting' => 'volumetric fog, ambient occlusion, rim lighting in muted tones',
            ],
            self::ANCIENT_FAIRYTALE => [
                'quality' => 'photorealistic, surreal, organic-crystal texture',
                'style' => 'ancient fantasy realism',
                'type' => 'mythical environment with magical elements',
                'mood' => 'enchanted, mysterious, ethereal',
                'lighting' => 'bioluminescent glow, soft ambient mist, shifting tones',
            ],
            self::BLACK_AND_WHITE => [
                'quality' => 'ultra-detailed, rich grayscale textures, photorealistic',
                'style' => 'black and white only, deep contrast, no color',
                'type' => 'monochrome scene with tonal precision',
                'mood' => 'classic, dramatic, timeless',
                'lighting' => 'high contrast, soft light, strong chiaroscuro, grayscale depth',
            ],
            self::DIGITAL_ANIME => [
                'quality' => 'best quality, recent, aesthetic, ultra-clean, high-resolution',
                'style' => 'digital anime illustration',
                'type' => 'expressive character or scene with stylized composition',
                'mood' => 'cinematic, emotive, polished',
                'lighting' => 'anime glow, soft rim light, ambient depth',
            ],
            self::DIGITAL_ART => [
                'quality' => 'hand-painted texture, visible brush marks, impasto detail',
                'style' => 'traditional oil painting on canvas',
                'type' => 'realistic subject with expressive surface work',
                'mood' => 'authentic, emotional, tactile',
                'lighting' => 'natural studio light, shadowed texture, pigment-based contrast',
            ],
            self::MECH => [
                'quality' => 'cinematic, high-detail, practical-effect realism',
                'style' => 'tokusatsu, retro sci-fi, miniature-effect aesthetic',
                'type' => 'urban environmental destruction',
                'mood' => 'heroic, destructive, high-energy',
                'lighting' => 'dramatic backlight, hard shadows, reflective armor glow',
            ],
            self::RETRO_ILLUSTRATION => [
                'quality' => 'clean lines, vintage print texture, flat finish',
                'style' => '1950s retro flat illustration, nostalgic and graphic',
                'type' => 'mid-century scene with single-point perspective',
                'mood' => 'inviting, wholesome, optimistic',
                'lighting' => 'warm sunlight, soft canopy shadows, pastel glow',
            ],
            self::POP_ART => [
                'quality' => 'high-contrast, crisp inking, bold shading',
                'style' => '1960s pop art comic, retro print aesthetic',
                'type' => 'character portrait or expressive figure',
                'mood' => 'dramatic, expressive, vintage Americana',
                'lighting' => 'flat color fills, print-style halftone shadows, comic-style highlights',
            ],
            self::PSYCHEDELIC_ART => [
                'quality' => 'ultra-vivid, intricate, dreamlike precision',
                'style' => 'psychedelic surrealism, fantasy vision, vibrant color fusion',
                'type' => 'surreal symbolic scene or figure',
                'mood' => 'trippy, otherworldly, intense',
                'lighting' => 'neon glow, dynamic color gradients, high contrast highlights',
            ],
            self::CYBERPUNK->value => [
                'quality' => 'hyper-detailed, cinematic, photoreal polish',
                'style' => 'cyberpunk, neo-noir dystopia',
                'type' => 'urban tech-drenched scene or portrait with rain and holograms',
                'mood' => 'gritty, tense, futuristic',
                'lighting' => 'neon glow, reflective wet streets, pink-blue contrast, volumetric fog',
            ],
            self::ANIMATED_FANTASY => [
                'quality' => 'hand-drawn, bold colors, painterly finish',
                'style' => '2D animated fantasy, whimsical and charming',
                'type' => 'magical setting with fantasy elements',
                'mood' => 'dreamy, adventurous, lighthearted',
                'lighting' => 'soft sunlight, dreamy highlights, gentle ambient shadows',
            ],
            self::PAPER_CUT_ART => [
                'quality' => 'flat matte, handcrafted, layered depth',
                'style' => 'paper cut illustration, simple shapes, clean edges',
                'type' => 'hand-assembled scene from layered paper elements',
                'mood' => 'playful, imaginative, whimsical',
                'lighting' => 'soft top-light casting gentle shadow between layers',
            ],
            default => [],
        };
    }

    public static function prompt(self $genre, string $prompt, ?string $aspectRatio = null): array
    {
        return match ($genre) {
            self::POINT_OF_VIEW => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Immersive first-person perspective, limbs in the foreground, natural positioning, eye-level view, realistic textures, touched, depth-focused environment, balanced mix of hand and leg visibility.",
                    // 'seed' => 60279,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::SELFIE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}, taking Realistic selfie, close-up first-person view, natural facial expression, hand or arm holding camera visible, authentic attire, immersive background, natural lighting, clear focus on face, high detail.",
                    // 'seed' => 123456789,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::MIYAZAKI_INSPIRED => [
                'model' => 'colinmcdonnell22/ghiblify-3:407b7fd425e00eedefe7db3041662a36a126f1e4988e6fbadfc49b157159f015',
                'input' => [
                    'prompt' => "{$prompt}, in ghibli style.",
                    // 'seed' => 123456789,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 16,
                ],
            ],
            self::AFRICAN_LEGENDS => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: African American characters, cinematic, hyperrealistic, expressive features, diverse themes, natural lighting, rich textures",
                    // 'seed' => 23951,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ACTION_MOVIE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Action movie, high-energy, fast-paced, dynamic composition, dramatic lighting, intense action, close-ups, dramatic angles. Lighting: Dramatic, high-contrast, deep shadows, spotlights, neon glows. Additional: 4K resolution, sharp focus.",
                    // 'seed' => 49101,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ANCIENT_FAIRYTALE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Surrealist photorealism, blending organic and crystal structures, fantasy elements. Lighting: Ethereal glow, bioluminescence, mysterious ambient light, shifting colors. Effects: Fluid mist, detailed cloth physics, magical particles, tilt-shift for depth.",
                    // 'seed' => 45946,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ANIMATED_CARTOON => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Classic 2D retro animation, hand-drawn aesthetic, bold outlines, exaggerated expressions. Lighting: Flat shading, cel animation look, strong contrast. Effects: Limited motion, vintage color palette, whimsical atmosphere.",
                    // 'seed' => 20942,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ANIMATED_FANTASY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Hand-drawn 2D animation, bold colors, whimsical fantasy elements. Lighting: Soft, diffused sunlight, dreamy highlights, gentle shadows. Effects: Flowing breeze, glowing particles, shimmering water, animated magic.",
                    // 'seed' => 1586,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ART_DECO => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Hand-drawn, flowing brush strokes, sleek Art Deco lines, intricate motifs. Lighting: Soft, golden ambient glow, nostalgic and romantic atmosphere. Effects: Polished reflections, vintage chandelier glow, silver and gold accents.",
                    // 'seed' => 60279,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::OLD_CAMERA => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Vintage photography, black and white or sepia, soft focus. Lighting: Natural or early artificial, strong contrasts, deep shadows. Effects: Film grain, vignetting, light leaks, subtle scratches.",
                    // 'seed' => 63023,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::BLACK_AND_WHITE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Black and white, ultra-detailed, deep contrasts, rich textures. Lighting: Soft, natural volumetric, striking chiaroscuro effects. Effects: Intricate details, timeless artistry, photorealistic composition.",
                    // 'seed' => 41989,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::CINEMATIC_REALISM => [
                'model' => 'playgroundai/playground-v2.5-1024px-aesthetic:a45f82a1382bed5c7aeb861dac7c7d191b0fdf74d8d57c4a0e6ed7d4d0bf7d24',
                'input' => [
                    'prompt' => "{$prompt}, perfect composition, beautiful detailed intricate insanely detailed octane render trending on artstation, 8 k artistic photography, photorealistic concept art, soft natural volumetric cinematic perfect light, chiaroscuro, award - winning photograph, masterpiece, oil on canvas, raphael, caravaggio, greg rutkowski, beeple, beksinski, giger",
                    // 'seed' => 21315319,
                    'num_inference_steps' => 35,
                    'scheduler' => 'DPMSolver++',
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'prompt_strength' => 0.86,
                    'guidance_scale' => 3,
                    'output_format' => 'jpg',
                    'aspect_ratio' => 'custom',
                    'num_outputs' => 1,
                    'negative_prompt' => 'painting, drawing, illustration, ugly, deformed, noisy, blurry, distorted, out of focus, out of frame, 2 faces, eyes disfigured, stretched out, cropped images, deformed hands, malformed heads, malformed eyes, disfigured, extra fingers, mutated hands, mutated face, mutated eyes, grainy, low-res, poorly drawn face, morbid, gross proportions, bad anatomy, extra limbs, poorly drawn hands, poorly drawn eyes, poorly drawn legs, missing fingers, nudity, nude, NSFW, no watermark',
                ],
            ],
            self::COMIC_BOOK => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Comic book, thick outlines, halftone shading, vivid colors, original world. Lighting: Bold contrasts, deep shadows, expressive comic book inking. Effects: Action panels, speed lines, comic halftone dots, original costume design.",
                    // 'seed' => 5146,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::RETRO_ILLUSTRATION => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "A 1950's style flat illustration of {$prompt}.Style: 1950s retro flat illustration, nostalgic tone, single-point perspective, classic line art. Lighting: Warm sun, soft canopy shadows, inviting feel. Effects: Pastel palette, grainy textures, soft vintage shading.",
                    // 'seed' => 33512,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::CRIME_MOVIE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Neo-noir, monochrome tone, gritty realism, classic noir influence. Lighting: Soft ambient glow, harsh directional shadows, moody depth. Effects: Tension-filled atmosphere, cinematic texture.",
                    // 'seed' => 27286,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::CYBERPUNK => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Cyberpunk, neo-noir, photorealistic, cinematic, hyper-detailed. Lighting: Neon glows, pink-blue contrast, wet surfaces, moody shadows. Effects: Fog, rain, lens flare, holograms, glitch textures.",
                    // 'seed' => 61715,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::DARK_FANTASY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Dark fantasy, gritty realism, gothic aesthetic, high detail, cinematic tone. Lighting: Moonlit shadows, glowing embers, moody fog, torchlight glow. Effects: Ash particles, decayed textures, runes, mystical energy, atmospheric depth.",
                    // 'seed' => 630,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::DIGITAL_ANIME => [
                'model' => 'cjwbw/animagine-xl-3.1:6afe2e6b27dad2d6f480b59195c221884b6acc589ff4d05ff0e5fc058690fbb9',
                'input' => [
                    'prompt' => "best quality, recent, aesthetic, {$prompt}",
                    // 'seed' => 31225,
                    'num_inference_steps' => 28,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'guidance_scale' => 7,
                    'style_selector' => 'Anime',
                    'quality_selector' => 'Standard v3.1',
                    'negative_prompt' => 'nsfw, lowres, (bad), text, error, fewer, extra, missing, worst quality, jpeg artifacts, low quality, watermark, unfinished, displeasing, oldest, early, chromatic aberration, signature, extra digits, artistic error, username, scan, [abstract], no nsfw, no nudity.',
                ],
            ],
            self::DIGITAL_ART => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "Painting of {$prompt}. Style: Hand drawn art, Artist, expressionist, heavily textured brushstrokes, non-realistic color palette. Additional Effects: Paint splatters, smudges, drips, impasto technique",
                    // 'seed' => 10861,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::REALISTIC_CARTOON => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Realistic 3D cartoon style with soft, expressive features and dreamy magical elements. Lighting: Warm twilight glow, gentle highlights, soft ambient backlighting. Effects: Glimmering sparkles, rising steam, glowing edges, smooth surfaces, subtle whimsy.",
                    // 'seed' => 43787,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::VAPORWAVE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Vaporwave synth aesthetic, retro-futuristic design with digital wireframes and surreal dreamscape elements. Lighting: Bold neon glow, deep purples and electric blues, ambient mist and horizon haze. Effects: Perspective grid, glowing retro sun, chromatic aberration, soft bloom, lo-fi nostalgic tone.",
                    // 'seed' => 41575,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::FUTURISTIC_SCI_FI => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Hyper-realistic sci-fi, cinematic atmosphere, epic and alien world-building with surreal tech landscapes. Lighting: Planetshine, bioluminescent glow, volumetric rays, soft ambient hues. Effects: Holograms, lens flares, reflective surfaces, floating particles, hazy atmosphere.",
                    // 'seed' => 29710,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::GREEK_MYTHOLOGY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Modern digital realism inspired by Greek mythology, rich textures, sculptural details, mythic ambiance. Lighting: Soft diffused glow, golden hour shadows, divine highlights. Effects: Stone textures, flowing drapery, atmospheric depth, lifelike structure and color.",
                    // 'seed' => 18057,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::HORROR_FANTASY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Dark fantasy horror, grim and surreal, unsettling yet artistic with eerie atmospheres and grotesque detail. Lighting: Low light with harsh shadows, cold ambient glows, flickering torchlight or moonlit effects. Effects: Fog, grime, dripping textures, atmospheric distortion, disturbing visual storytelling.",
                    // 'seed' => 20510,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::HYPER_REALISM => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Photojournalistic style, professional lighting, ultra-realistic details, high-end commercial quality, 4K clarity. Avoid: artificial, stylized, digital art, filters.",
                    // 'seed' => 20510,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ANCIENT_EGYPT => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Hyper-realistic Ancient Egypt, rich in authentic architecture, garments, and cultural symbols. Lighting: Natural, diffused sunlight casting dynamic shadows and warm ambient tones. Effects: 4K clarity, lifelike textures, atmospheric dust, realistic depth of field and perspective.",
                    // 'seed' => 1829,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::PIXEL_ART => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "A pixelated {$prompt}. Style: Retro 16-bit pixel art, blocky design, nostalgic video game aesthetic. Lighting: High-contrast pixel lighting with bold limited palette. Effects: Dithered shading, chunky textures, grid-aligned elements, pixel-level detail only.",
                    // 'seed' => 54519,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::PAPER_CUT_ART => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Paper cut art featuring layered elements with sharp, defined edges, giving a handcrafted and artistic look. Lighting: Soft, diffused lighting that casts gentle shadows between the layers, enhancing the depth and texture of the paper. Additional Effects: Delicate paper mist, clouds, or silhouettes of birds, with intricate cut-outs in each layer to give a rich, tactile quality. modest clothing, fully clothed, avoid photorealistic textures, avoid digital effects, avoid 3D renders, avoid complex patterns",
                    // 'seed' => 3643,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::MECH => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Tokusatsu. Lighting: Dramatic lighting, with harsh shadows and bright highlights reflecting off the metallic surfaces. modest clothing, fully clothed, Additional Effects: Smoke trails, sparks, and glowing energy beams across the scene",
                    // 'seed' => 17778,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::CARICATURE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Exaggerated caricature, comically distorted proportions, bold linework. Lighting: Vibrant, unrealistic color schemes, dramatic shadows. modest clothing, fully clothed, Additional Effects: Exaggerated expressions, warped perspectives, comical details",
                    // 'seed' => 26773,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::WESTERN_PUNK => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Westernpunk, blending Old West with fantasy/sci-fi elements. Lighting: Dramatic contrasts, warm desert hues, eerie glows. modest clothing, fully clothed, Additional Effects: Weathered textures, surreal elements, anachronistic details",
                    // 'seed' => 54058,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::WATERCOLOR => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Watercolor painting, fluid and expressive, blend of realism and abstract elements, Soft, feathered edges where colors meet. modest clothing, fully clothed, Additional effects: Water blooms and color bleeds, Wet-on-wet technique for blended colors",
                    // 'seed' => 23666,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::BIBLICAL_HISTORY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Biblical History. Lighting: Dramatic, soft golden rays breaking through clouds, creating a divine glow. Additional Effects: Flowing water, distant storm clouds, glowing figures, epic landscapes. modest clothing, fully clothed, avoid modern technology, avoid contemporary clothing, avoid bright neon colors, avoid abstract elements",
                    // 'seed' => 38902,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::ANCIENT_INDIA => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Indian Mythology, intricate details, traditional divine attire, vibrant colors, spiritual setting. modest clothing, fully clothed, Additional Effects: Ethereal aura, misty background, glowing symbols, subtle god rays",
                    // 'seed' => 21389,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::NATIVE_AMERICAN => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Native American illustration, inspired by traditional elements like tipis, horses, and natural landscapes- Lighting: Soft golden hour lighting with warm, natural tones. modest clothing, fully clothed, Additional Effects: Slight mist or dust in the air, dynamic movement of wildlife, detailed textures on clothing and landscapes",
                    // 'seed' => 23951,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::PSYCHEDELIC_ART => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Psychedelic Art with a focus on surrealism and vivid fantasy. modest clothing, fully clothed, Lighting: Bright and dynamic lighting with dramatic contrasts, highlighting intricate details and enhancing the sense of depth",
                    // 'seed' => 12659,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::PENCIL_DRAWING => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Pencil Drawing, Detailed, Fine lines, Letters, Vivid, Illustrative. modest clothing, fully clothed, Additional Effects: Subtle shading and texture for depth",
                    // 'seed' => 6499,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::VIKING => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Norse historical, fantasy cinematic, authentic Viking elements. Lighting: Natural diffused light with dramatic backlighting. modest clothing, fully clothed, Additional Effects: Atmospheric fog, detailed textures for fur and braids",
                    // 'seed' => 9532,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::STEAMPUNK => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: Steampunk. Lighting: Warm and ambient, with soft shadows and highlights to emphasize textures and metallic surfaces. modest clothing, fully clothed, Additional Effects: Intricate details featuring gears, steam effects, and brass textures; incorporate subtle lens flares to mimic the glow of gas lamps",
                    // 'seed' => 60686,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::POP_ART => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. 1960s pop art style, Andy Warhol inspired, Roy Lichtenstein comic style, Ben-Day dots, screen printing effect, bold black outlines, ultra high contrast, commercial printing aesthetic, offset color blocks, dramatic halftone pattern, retro advertising style, modest clothing, fully clothed",
                    // 'seed' => 4596,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::BLOCKIFY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Style: building block toy, plastic bricks, modular pieces. modest clothing, fully clothed, Light: plastic shine, toy-like glow. Render: glossy texture, clean edges, 8k",
                    // 'seed' => 38366,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::APOCALYPSE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "Topic: {$prompt}, Style: dieselpunk, industrial sci-fi, retrofuturistic, Artist: Simon Stålenhag, Derek Stenning, Webpage: artstation, behance, Sharpness: highly detailed, intricate mechanical parts, Extra: steam vents, glowing orange indicators, exposed pipes, Shade: muted teal and rust orange color palette, Lighting: volumetric fog, ambient occlusion, rim lighting, Negative: no modern tech, no clean surfaces, no bright colors",
                    // 'seed' => 16664,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::CLAYMATION => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Handmolded clay figures, matte plasticine texture, fingerprint details, imperfect clay surface, stop motion aesthetic, slightly rough edges, warm studio lighting, physical miniature scale, Aardman animation style.",
                    // 'seed' => 51484,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::DREAMSCAPE => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Craft a 3D-rendered illustration with smooth digital sculpting, exaggerated character design, and luminous textures capturing animated storytelling through playful, expressive characters and rich dimensional depth.",
                    // 'seed' => 61540,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::LOW_POLY => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Low poly 3D render with geometric angular surfaces, minimal textures, and clean edges. Use a muted pastel color palette. Subject should appear faceted like an unfolded origami, with triangular and polygonal faces creating a modern, crystalline aesthetic. Soft ambient lighting to highlight the geometric planes.",
                    // 'seed' => 64323,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ],
            self::STAINED_GLASS => [
                'model' => 'bytedance/hyper-flux-16step:382cf8959fb0f0d665b26e7e80b8d6dc3faaef1510f14ce017e8c732bb3d1eb7',
                'input' => [
                    'prompt' => "{$prompt}. Create a stained glass artwork with a 3D, glass-like appearance. Use vibrant, translucent colors in intricate, geometric patterns. Incorporate strong lighting effects to accentuate the depth, reflections, and beveled edges of the glass material.",
                    // 'seed' => 36580,
                    'width' => Dimension::fromAspectRatio($aspectRatio)->get('width'),
                    'height' => Dimension::fromAspectRatio($aspectRatio)->get('height'),
                    'num_outputs' => 1,
                    'aspect_ratio' => 'custom',
                    'output_format' => 'jpg',
                    'guidance_scale' => 3.5,
                    'output_quality' => 80,
                    'num_inference_steps' => 20,
                ],
            ]
        };
    }

    public function genrePrompt(): ?string
    {
        return match ($this) {
            self::CYBERPUNK => 'Front view, Standing on neon rooftop, arms at sides. Style: Photorealistic cyberpunk. Lighting: Pink-blue neon. Effects: Holograms, glitch. Clothing: Futuristic armor. Framing: Close-up portrait.',
            self::DARK_FANTASY => 'Front-facing, Standing solemnly, hands folded. Style: Gritty gothic realism. Lighting: Moonlit shadows, glowing embers. Effects: Ash particles, decayed textures. Clothing: Tattered cloak, dark robes. Framing: Close-up portrait.',
            self::DIGITAL_ANIME => 'Front-facing, Digital anime style, vibrant colors, clean line art, expressive character faces, dynamic poses. Soft cel shading with bright highlights. Detailed backgrounds inspired by modern Japanese settings. Emphasize large, emotive eyes and flowing hair. Energetic, cinematic composition with subtle glow effects. Framing: Close-up portrait.',
            self::DIGITAL_ART => 'Front view, Painting dissolving into swirls of color Style: Hand drawn art, Artist, expressionist, heavily textured brushstrokes, non-realistic color palette Additional Effects: Paint splatters, smudges, drips, impasto technique Framing: Close-up portrait.',
            self::REALISTIC_CARTOON => 'Front view, Smiling gently, arms crossed. Style: Pixar-Dreamworks hybrid character. Lighting: Soft dreamy light. Clothing: Explorer vest, shorts. Environment: Animated forest with talking creatures. Framing: Close-up portrait.',
            self::VAPORWAVE => 'Front view, Standing in neon-lit grid landscape. Style: Vaporwave retro aesthetic. Lighting: Pink and blue glows. Effects: Holograms, scanlines, lens flares. Clothing: Futuristic casual. Framing: Close-up portrait.',
            self::FUTURISTIC_SCI_FI => 'Front-facing, Standing on moon surface in relaxed stance. Style: Sci-fi cinematic realism. Lighting: Soft volumetric lights, planetary glow. Effects: Holographic HUD elements. Clothing: Tech exo-suit. Framing: Close-up portrait.',
            self::HORROR_FANTASY => 'Front-facing, Standing still, staring ahead intensely. Style: Dark fantasy, eerie detail. Lighting: Moody ambient, brighter face illumination. Effects: Fog, embers, subtle glows. Clothing: Black hooded cloak. Framing: Close-up portrait.',
            self::HYPER_REALISM => 'Front-facing, Standing calmly, hands relaxed at sides. Photojournalistic style, professional lighting, ultra-realistic details, high-end commercial quality, 4K clarity. Framing: Close-up portrait.',
            self::ANCIENT_EGYPT => 'Front-facing, Standing, arms relaxed. Style: Ancient Egyptian hyper-realistic. Lighting: Warm diffused sunlight. Effects: Dust motes, sandstone backdrop. Clothing: Wearing linen tunic or pharaoh robes. Framing: Close-up portrait.',
            self::PIXEL_ART => 'Front-facing, A front view pixel art sprite. Style: Retro 16-bit pixel art, blocky design, nostalgic video game aesthetic. Lighting: High-contrast pixel lighting with bold limited palette. Effects: Dithered shading, chunky textures, grid-aligned elements, pixel-level detail only. Framing: Close-up portrait.',
            self::MECH => 'Front view, Standing ready, one hand clenched. Style: Tokusatsu realism. Lighting: Metallic highlights. Effects: Sparks, smoke. Clothing: Mecha suit. Framing: Close-up portrait.',
            self::WESTERN_PUNK => 'Front-facing, Standing in dusty street, one hand on belt. Style: Westernpunk fusion. Lighting: Warm desert glow. Effects: Dust clouds, faint sparks. Clothing: Duster coat, leather accessories. Framing: Close-up portrait.',
            self::WATERCOLOR => 'Facing directly forward in a close-up portrait, gentle smile, one hand lightly touching the collarbone, the other arm relaxed just below the frame, head slightly tilted, expressive eyes gazing softly at the viewer, hair softly framing the face. Style:  Watercolor painting, fluid and expressive, blend of realism and abstract elements, Soft, feathered edges where colors meet Additional effects: Water blooms and color bleeds, Wet-on-wet technique for blended colors Framing: Close-up portrait.',
            self::BIBLICAL_HISTORY => 'Front-facing, Kneeling in prayer under divine light beams. Wearing flowing robes or tunic with golden sashes. Style: Biblical scenes. Lighting: Divine highlights. Effects: Ancient landscapes, soft paint depth. Framing: Close-up portrait.',
            self::PSYCHEDELIC_ART => 'Front view, close-up portrait. Style: Psychedelic surreal illustration, 2D vector art, bold outlines, highly stylized. Lighting: bright and dynamic lighting with dramatic contrasts. Background: swirling colorful patterns and cosmic elements. Texture: smooth gradients, no photographic realism. Clothing: modern, simple shirt in complementary neon tones. Framing: Close-up portrait.',
            self::PENCIL_DRAWING => 'Front-facing, sketched in dramatic black ink and rough brush strokes. Style: High-contrast monochrome ink drawing. Lighting: Sharp highlights and deep shadows to emphasize texture. Effects: Dynamic pose, expressive line work. Framing: Close-up portrait.',
            self::VIKING => 'Front-facing, Standing proud, hands at sides. Style: Norse realism. Lighting: Dramatic natural light. Effects: Mist, fur detail. Clothing: Leather armor. Framing: Close-up portrait.',
            self::STEAMPUNK => 'Front-facing, Standing upright, one hand resting on belt. Style: Victorian steampunk. Lighting: Warm brass glow. Effects: Gears, smoke. Clothing: Corset vest, goggles. Framing: Close-up portrait.',
            self::POP_ART => 'Front view, Smiling brightly, hand on hip. Style: 1960s Pop Art, Roy Lichtenstein-inspired, thick inking, strong halftone dots, bold comic-style coloring. Background: Abstract or comic-style background with halftone gradients and simple props (e.g. posters or awards). Clothing: Modern casual shirt or outfit, simplified into bold blocks of color with stylized shading. Framing: Close-up portrait.',
            self::BLOCKIFY => 'Front-facing, Jumping in the air, smiling. Style: Lego block style. Lighting: Clean bright light. Effects: Plastic shine. Clothing: Simplified toy outfit. Framing: Close-up portrait.',
            self::APOCALYPSE => 'Front-facing, Running through a ruined city with smoke behind. Wearing rugged jacket, utility gear. Style: Dieselpunk realism. Lighting: Orange ambient haze. Effects: Dust, debris. Framing: Close-up portrait.',
            self::CLAYMATION => 'Front-facing, Mid-run with arms flailing. Wearing molded clay tunic or outfit. Style: Clay stop-motion. Lighting: Soft warm studio. Effects: Fingerprint texture. Framing: Close-up portrait.',
            self::DREAMSCAPE => 'A whimsical animated scene in the style of a Pixar movie directed by John Lasseter, featuring vibrant colors, expressive characters, and cinematic lighting. The environment should feel dreamlike and immersive, such as a lush jungle, icy arctic, or a colorful floating city. Characters should be stylized in 3D animation with exaggerated expressions and detailed textures. Use soft shadows, depth of field, and atmospheric perspective to enhance visual storytelling. Art style should combine painterly charm with polished 3D rendering. Scene composition should feel like a still from an award-winning animated feature.',
            self::ACTION_MOVIE => 'Front-facing, Standing in a dynamic action pose. Style: Action movie, cinematic realism, high-energy. Lighting: Intense spotlights, dramatic contrast, neon reflections. Effects: Motion blur, debris, dynamic camera angle. Framing: Close-up portrait.',
            self::ANCIENT_FAIRYTALE => 'Front-facing, Standing calmly in glowing forest. Style: Surrealist photorealism, blending organic and crystal structures, fantasy elements. Lighting: Ethereal glow, bioluminescence, mysterious ambient light, shifting colors. Effects: Fluid mist, detailed cloth physics, magical particles, tilt-shift for depth. Framing: Close-up portrait.',
            self::ANIMATED_CARTOON => 'Front-facing, Smiling wide, arms spread in excitement. Style: Animated cartoon, bold outlines. Lighting: Flat, bright highlights. Clothing: Simple overalls, bright shoes. Environment: Cartoon town square. Framing: Close-up portrait.',
            self::ANIMATED_FANTASY => 'Front view, Standing with heroic pose, joyful expression. Style: Hand-drawn 2D animation, bold outlines, flat shading, cartoon colors. Lighting: Simple cel shading. Effects: Cartoon sparkles, energy lines. Clothing: Wizard robe. Framing: Close-up portrait.',
            self::OLD_CAMERA => 'Front-facing, Standing with relaxed posture, hands in pockets. Style: Vintage photography, black and white or sepia, soft focus. Lighting: Natural or early artificial, strong contrasts, deep shadows. Effects: Film grain, vignetting, light leaks, subtle scratches. Framing: Close-up portrait.',
            self::BLACK_AND_WHITE => 'Front view, standing still with arms crossed. Style: Monochrome high-contrast black and white portrait. Lighting: Even soft light on the face, bright exposure, gentle shadows to define features, no harsh darkness. Effects: Fine film grain. Clothing: Classic trench coat. Framing: Close-up portrait.',
            self::CINEMATIC_REALISM => 'Front-facing, Standing confidently, arms crossed. Style: Photorealistic cinematic scene. Lighting: Golden hour backlight, lens flares. Effects: Subtle particles, shallow depth of field. Clothing: Modern casual clothing. Framing: Close-up portrait.',
            self::COMIC_BOOK => 'Front-facing, Heroic stance, fists on hips. Style: Comic book, bold lines, vibrant colors. Lighting: Spotlight highlights expression. Environment: Explosive comic backdrop. Framing: Close-up portrait.',
            self::RETRO_ILLUSTRATION => 'Front view, sitting in a classic diner booth, hands folded on the table. Style: Retro mid-century illustration, 1950s magazine ad style, flat colors, bold outlines, simplified shading, no photorealism. Lighting: Even soft light, warm nostalgic color palette. Effects: Subtle halftone texture. Clothing: Casual vintage attire (1950s). Framing: Close-up portrait.',
            self::CRIME_MOVIE => 'Front view,Standing in a city alley, clear soft lighting. Style: Monochrome noir style. Clothing:  = nullTrench coat, shirt. Framing: Close-up portrait.',
            self::SELFIE => 'Generate a realistic, close-up selfie from a first-person perspective. Show the person with one arm partially visible. Focus on the face with clear, natural expressions and eye contact. Lighting should be realistic and flattering, matching the subject\'s environment. Ensure the background appears immersive and coherent with a casual, everyday setting like a park, street, room, or café. Keep the clothing authentic to what the person would realistically wear. High detail on facial features, textures, and natural shadows. Maintain realism and clarity',
            self::PAPER_CUT_ART => null,
            self::ART_DECO => null,
            self::NATIVE_AMERICAN => null,
            self::ANCIENT_INDIA => null,
            self::GREEK_MYTHOLOGY => null,
            self::CARICATURE => null,
            self::AFRICAN_LEGENDS => null,
            self::MIYAZAKI_INSPIRED => null,
            self::POINT_OF_VIEW => null,
            self::LOW_POLY => null,
            self::STAINED_GLASS => null,
        };
    }
}
