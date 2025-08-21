<?php

namespace App\Syllaby\Generators\Prompts;

class ThumbnailPrompt
{
    public static function build(string $context, int $amount, ?string $text = null, ?string $color = null): string
    {
        return str_replace(
            array_keys(self::bindings($context, $amount, $text, $color)),
            array_values(self::bindings($context, $amount, $text, $color)),
            blank($text) && blank($color) ? self::simple() : self::advanced()
        );
    }

    private static function bindings(string $context, int $amount, ?string $text = null, ?string $color = null): array
    {
        return [
            ':context' => $context,
            ':amount' => $amount,
            ':text' => $text,
            ':color' => $color,
        ];
    }

    private static function simple(): string
    {
        return <<<'EOT'
           Instructions:
            1. Write :amount prompt(s) to be used in a text-to-image model to generate a YouTube thumbnail.
            2. The prompt should cover the following characteristics and use the given user context to adjust the characteristics.
            3. Limit the main focal points to 3 or fewer.
            4. Design for clarity and impact when viewed at small sizes.
            5. Prompt should not have characteristics subheadings, only text.
            6. Each prompt should have negative prompts like: avoid blurry, avoid deformity, etc.


            Creative Guidelines:
            Central Focus: A person or object in action, positioned slightly off-center
            Emotional Appeal: Express excitement or surprise through facial expressions and body language
            Color Scheme: Use vibrant, contrasting colors with emphasis on red, yellow, and blue
            Text: Include a short, bold text (maximum 12 characters) that complements the visual
            Curiosity Gap: Imply an unexpected outcome or revelation
            Scale: If applicable, include elements that provide a sense of scale
            Brand Elements: Subtly incorporate channel logo or recognizable brand colors
            Background: Simple, slightly blurred background that doesn't distract from the main subject
            Thumbnail Type: Utilize the "Focus" type, drawing attention to the main subject
            Movement/Action: Convey a sense of motion or action through blur effects or action lines

            Examples:
            1) "Majestic lion close-up portrait, dramatic lighting, golden hour, ultra detailed fur texture, intense eyes, centered composition, avoid blurry, avoid deformity, photorealistic style"
            2) "Deep space nebula with swirling colors, stars twinkling in background, cosmic dust clouds, high contrast, ultra detailed, centered composition, avoid blurry, avoid deformity"
            3) "Enchanted forest scene with glowing mushrooms, mystical fog, ray of light breaking through trees, fantasy style, ultra detailed, centered composition, avoid blurry, avoid deformity"
            4) "Underwater coral reef teeming with colorful fish, rays of sunlight penetrating water surface, photorealistic style, ultra detailed, centered composition, avoid blurry, avoid deformity"

            Context:
            :context

            Output:
            Structure output as follows:
            {
                "prompts": [
                    "text-to-image model prompt 1",
                    "text-to-image model prompt 2",
                    "text-to-image model prompt 3",
                    ... and so on
                ]
            }

            The output can not include any type of formatting, descriptive or explanatory text. 
            Just output a valid text string. 
        EOT;
    }

    private static function advanced(): string
    {
        return <<<'EOT'
            Instructions:
            1. Write :amount prompt(s) to be used in a text-to-image model to generate a YouTube thumbnail.
            2. The prompt should cover the following characteristics and use the given user context to adjust the characteristics.
            3. Limit the main focal points to 3 or fewer.
            4. Design for clarity and impact when viewed at small sizes.
            5. Prompt should not have characteristics subheadings, only text.
            6. Each prompt should have negative prompts like: avoid blurry, avoid deformity, etc.
            7. CRITICAL: The output MUST include the exact text ":text" written precisely as provided.
            8. CRITICAL: The output MUST use the exact color ":color" for the text as specified.
            9. DO NOT alter, paraphrase, or substitute the provided text or color under any circumstances.
        
            Creative Guidelines:
            Professional YouTuber Style: Create thumbnails that resemble popular YouTuber style with these key elements:
                - Person positioned on right or left side (preferably shoulders up, showing facial expression)
                - Person should have a clear emotional reaction (surprise, excitement, curiosity)
                - Person should appear to be interacting with or reacting to the text/graphic elements
            
            Text Styling: The exact text ":text" in exact color ":color" must be prominently featured with these characteristics:
                - Large, bold text with strong contrast against the background
                - Text should be arranged in stacked/layered blocks for visual impact
                - Add geometric shapes behind text (rectangles, strips, banners) in contrasting colors
                - Text can overlap person slightly for integrated composition
                - Incorporate 3D effects, glow, or drop shadows to make text stand out
                - Text should occupy 30-50% of the thumbnail area
            
            Color Scheme: 
                - Use bold, saturated background colors (orange, red, blue, yellow)
                - Create color gradients or grid patterns for backgrounds
                - Use contrasting colors for text blocks and background
                - Implement color psychology (red for urgency, yellow for attention)
            
            Additional Elements:
                - Add numbered points (1,2,3,4) in circular badges
                - Include relevant icons or logos (like app icons, currency symbols, or brand elements)
                - Use visual metaphors related to content (money, growth, success indicators)
                - Implement subtle grid patterns or geometric shapes in background
                - Create depth with shadows and layering effects
        
            Examples:
                1) "A YouTube thumbnail image with an orange and purple gradient background, a photo of a person talking into a microphone in a circular mask on the left side, an audio icon to go with it below the circular image, and a big bold caption on the right side that reads ':text', the thumbnail is graphically designed and of high quality."
                2) "A YouTube thumbnail image of a graphic designer sitting at a desk with three computer screens in the background working on an image design, with the title ':text' written in large strokes on the right side of the background layer, with a premium color scheme and simple details, creative video cover design, high quality."
                3) "A YouTube thumbnail image, the background is clear 3D anime scenes and characters, with a Keystone Portrait of a female presenter with her right hand outstretched on the right, a large bold orange caption on the left ':text'. Two light orange rectangular boxes below the title, one says 'AI Creation', the other says 'Character Consistency', thumbnail graphic design, quality details."
                4) "Thumbnail of a YouTube video with a light yellow tear-off paper half in the background on the left, the title ':text' written on the tear-off paper, something else related to the video written in medium black text below the title, and 'Subscribe' written in small letters on the red button below it. On the right side of the background is a travel photo of a person, graphic design, high quality."
                5) "Thumbnail of a YouTube video with a portrait of a female travel blogger taken at the pyramids of Egypt in the background. On the left side of the background photo layer, there is a white airplane icon and its dotted flight path in an irregular dotted box, in the box is written the title ':text' in white large font, outside the dotted box is written something else related to the video in a red irregular rectangular frame, the picture is decorated with a few minimalist star icons, details are rich and high quality."
                6) "YouTube thumbnail of a daily sharing video with a four-panel photo puzzle in the background showing the daily life of a 20 year old girl, with a portrait of her smiling for the camera, a plate of avocado bread, a Polaroid camera, three cups of coffee close together in an overhead shot, with a white wavy line border around the background, and the title written in white, large, artistic font in the center of the background layer. ':text', under the title in medium size font, minimalist, high quality color scheme, HD."
                7) "Thumbnail of a YouTube video with a puzzle of two daily photos of a girl in the background, a messy desk on one side and a cozy bedroom with low saturated colors on the other side, in the middle of the background layer is a sticker photo of the character of a girl with curly hair and white tank top and jeans, the caption in a big bold font across the layer of the sticker reads ':text', around the font there is a white flower icon embellishment, flat design, minimalist, premium color scheme, HD."
                8) "A YouTube video thumbnail of a live beauty tutorial on a light purple background with Halloween themed elements, with a girl in clown makeup holding a makeup brush in her left hand and a small mirror in her right, and ':text' written in large black strokes in a light white mask on the right side above the background layer."
            
            
            Context:
            :context
        
            Output:
            Structure output as follows:
            {
                "prompts": [
                    "text-to-image model prompt 1",
                    "text-to-image model prompt 2",
                    "text-to-image model prompt 3",
                    ... and so on
                ]
            }
        
            The output must be a valid JSON string only, with no additional formatting, descriptions or explanations.
            Each prompt MUST preserve the exact text ':text' and exact color ':color' as specified by the user.
        EOT;
    }
}
