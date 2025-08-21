<?php

namespace App\Syllaby\Subscriptions\Listeners;

use App\Syllaby\Users\User;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Mail;
use App\Syllaby\Auth\Mails\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\Events\SubscriptionCreated;

class GenerateWelcomeEmail implements ShouldQueue
{
    public function __construct()
    {
    }

    public function handle(SubscriptionCreated $event): void
    {
        $user = $event->user;
        $this->sendFallbackEmail($user);

        //        try {
        //            $message = $this->format($user);
        //            $this->video->assertTemplateExists();
        //
        //            $this->video->createFromTemplate($message, $user->id);
        //        } catch (Exception $error) {
        //            Log::debug("Unable to send request for welcome video $user->id", [$error->getMessage()]);
        //            $this->sendFallbackEmail($user);
        //        }
    }

    private function format(User $user): string
    {
        if (!$industry = $this->industryFor($user)) {
            return str_replace(['NAME'], [$user->name], $this->simpleMessage());
        }

        return str_replace(['NAME', 'INDUSTRY'], [$user->name, $industry], $this->detailedMessage());
    }

    private function industryFor(User $user): ?string
    {
        return $user->answers()->whereHas('question', function ($query) {
            $query->where('slug', 'to_which_industry_you_belong_to');
        })->value('body');
    }

    private function simpleMessage(): string
    {
        return <<<EOT
            Hello, NAME. Welcome to Syllaby!
            My name is Austin - Syllaby's CEO. I am thrilled to have you on board as we embark on a transformative content
            creation journey together.
            Syllaby, our advanced AI-powered tool, is meticulously designed to elevate your content creation experience.
            With features ranging from content idea discovery to script generation, and AI-powered avatar videos, we're here
            to simplify and enhance your creative process. Imagine crafting engaging content and compelling scripts effortlessly,
            all within a singular, user-friendly interface.
            In the diverse landscape of modern industries, Syllaby proves to be an invaluable asset. Our tool enables
            professionals across various sectors to streamline the creation of informative and persuasive content, making
            intricate subject matter more accessible and comprehensible to your audience.
            The scheduling and direct publishing capabilities further ensure a consistent and timely dissemination of essential
            information to your target demographic.
            We believe so strongly in Syllaby that this video you're watching was generated within Syllaby!
            Thank you for entrusting us with your content creation needs! I am excited about the transformative impact our
            platform will have on your creative endeavors and, ultimately...on your success!
        EOT;
    }

    private function detailedMessage(): string
    {
        return <<<EOT
            Hello, NAME. Welcome to Syllaby. 
            My name is Austin - Syllaby's CEO. I am thrilled to have you on board as we embark on a transformative content creation journey together.
            Syllaby, our advanced AI-powered tool, is meticulously designed to elevate your content creation experience. With features ranging from 
            content idea discovery to script generation, and AI-powered avatar videos, we're here to simplify and enhance your creative process. Imagine 
            crafting engaging content and compelling scripts effortlessly, all within a singular, user-friendly interface. In the realm of the INDUSTRY 
            industry, Syllaby proves to be an invaluable asset. By leveraging our tool, INDUSTRY specialists can streamline the creation of informative 
            and persuasive content, making complex INDUSTRY concepts more accessible and comprehensible to your audience. The scheduling and direct 
            publishing capabilities further ensure a consistent and timely dissemination of essential INDUSTRY information. We believe so strongly in Syllaby
            that this video you're watching was generated within Syllaby! Thank you for entrusting us with your content creation needs! I am excited about
            the transformative impact our platform will have on your creative endeavors and, ultimately...on your success!
        EOT;
    }

    private function sendFallbackEmail(User $user): void
    {
        $mail = new WelcomeEmail($user);

        Mail::to($user->email)->send($mail->onQueue(QueueType::EMAIL->value));
    }
}
