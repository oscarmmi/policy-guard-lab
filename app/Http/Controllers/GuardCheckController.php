<?php

namespace App\Http\Controllers;

use App\Models\GuardCheck;
use App\Services\Guard\HarborDeskFaq;
use App\Services\Guard\PolicyGuard;
use App\Services\Llm\LlmClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GuardCheckController extends Controller
{
    public function store(Request $request, PolicyGuard $guard, LlmClient $llm): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
        ]);

        $prompt = $validated['prompt'];
        $decision = $guard->inspect($prompt);
        $answer = null;

        if ($decision->verdict === 'allow') {
            try {
                $answer = $llm->answerQuestion($prompt);
            } catch (\Throwable $exception) {
                Log::warning('Answer provider unavailable; local FAQ answer used.', [
                    'exception' => $exception::class,
                ]);
                $answer = HarborDeskFaq::answer($prompt);
            }
        }

        $check = GuardCheck::query()->create([
            'prompt' => $prompt,
            'verdict' => $decision->verdict,
            'reasons' => $decision->reasons,
            'rewritten_prompt' => null,
            'answer' => $answer,
            'is_example' => false,
        ]);

        return response()->json([
            'check' => [
                'id' => $check->id,
                'prompt' => $check->prompt,
                'verdict' => $check->verdict,
                'reasons' => $check->reasons,
                'answer' => $check->answer,
                'source' => $decision->source,
                'created_at' => $check->created_at?->toIso8601String(),
            ],
        ]);
    }
}
