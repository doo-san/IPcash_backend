<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChatRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\FaqEntryResource;
use App\Models\Account;
use App\Models\AppSetting;
use App\Models\ChatbotAnswer;
use App\Models\FaqEntry;
use App\Services\AiSupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Implémente le tag `support` de openapi.yaml. Réponse par une vraie IA
// (Claude, voir AiSupportChatService) quand une clé Anthropic est
// configurée ; repli automatique sur l'ancienne réponse par mot-clé
// (`chatbot_answers`, voir ChatbotAnswerResource côté admin) sinon —
// jamais d'erreur exposée au client faute de clé/facturation active.
class SupportController extends Controller
{
    public function __construct(private readonly AiSupportChatService $ai) {}

    // Contenu éditable dans l'admin (AppSettingResource, FaqEntryResource)
    // consommé par l'écran d'aide (`support_screen.dart`) — jusqu'ici tous
    // deux codés en dur côté app (numéro de téléphone, 6 questions dans
    // app_fr.arb/app_en.arb) sans aucun effet réel malgré l'existence de
    // ces deux écrans d'admin. Le numéro garde un défaut en dur ici (même
    // valeur qu'avant) pour ne rien casser tant que la ligne `AppSetting`
    // n'a pas été créée par un opérateur.
    public function config(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();

        $faq = FaqEntry::where('locale', $account->preferred_locale)
            ->where('is_published', true)
            ->orderBy('position')
            ->get();

        return response()->json([
            'supportPhoneNumber' => AppSetting::get('support_phone_number', '+221338000000'),
            'faq' => FaqEntryResource::collection($faq)->resolve(),
        ]);
    }

    public function chat(ChatMessageRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $message = $request->validated('message');

        $account->chatMessages()->create(['role' => ChatRole::User, 'text' => $message]);

        $answer = $this->ai->reply($account, $message) ?? $this->findAnswer($account->preferred_locale, $message);

        $reply = $account->chatMessages()->create(['role' => ChatRole::Assistant, 'text' => $answer]);

        // `new ChatMessageResource($reply)` renvoyé directement forcerait
        // un 201 (Laravel détecte `wasRecentlyCreated` sur le modèle) —
        // le contrat attend explicitement 200 ici.
        return response()->json((new ChatMessageResource($reply))->resolve());
    }

    private function findAnswer(string $locale, string $message): string
    {
        $normalized = Str::lower($message);

        $candidates = ChatbotAnswer::where('locale', $locale)->where('is_active', true)->get();

        foreach ($candidates as $candidate) {
            if ($candidate->is_fallback) {
                continue;
            }
            foreach ($candidate->keywords as $keyword) {
                if (Str::contains($normalized, Str::lower($keyword))) {
                    return $candidate->answer;
                }
            }
        }

        $fallback = $candidates->firstWhere('is_fallback', true);

        return $fallback?->answer ?? "Je n'ai pas de réponse toute prête pour cette question. Le mieux est d'appeler l'assistance depuis la page Aide pour parler directement à un conseiller.";
    }
}
