<?php

namespace App\Services\Guard;

use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\Log;

class PolicyGuard
{
    /** @var array<string, string> */
    private const RULES = [
        'instruction_override' => '/\b(?:ignore|disregard|forget|override)\b.{0,60}\b(?:all\s+)?(?:previous|prior|earlier|above)\b.{0,30}\b(?:instructions?|directions?|rules?)\b/i',
        'system_prompt_disclosure' => '/\b(?:(?:reveal|show|print|leak|disclose|repeat|expose)\b.{0,50}\b(?:the\s+)?(?:hidden\s+|system\s+|internal\s+)?prompt\b|(?:hidden|system|internal)\s+prompt\s+leak(?:age)?\b)/i',
        'secret_exfiltration' => '/\b(?:exfiltrate|steal|dump|leak|reveal|send|print|show)\b.{0,50}\b(?:secrets?|api\s*keys?|passwords?|credentials?|tokens?)\b/i',
        'jailbreak' => '/\b(?:DAN\s+mode|do\s+anything\s+now|developer\s+mode|jailbreak|bypass\s+(?:the\s+)?safety|disable\s+(?:your\s+)?(?:safety|guardrails?))\b/i',
    ];

    public function __construct(private readonly LlmClient $llm) {}

    public function inspect(string $prompt): GuardDecision
    {
        $reasons = [];

        foreach (self::RULES as $rule => $pattern) {
            if (preg_match($pattern, $prompt) === 1) {
                $reasons[] = match ($rule) {
                    'instruction_override' => 'Attempts to override previous instructions.',
                    'system_prompt_disclosure' => 'Requests hidden system or internal prompt content.',
                    'secret_exfiltration' => 'Attempts to expose secrets or credentials.',
                    'jailbreak' => 'Uses a known jailbreak or safety-bypass phrase.',
                };
            }
        }

        if ($reasons !== []) {
            return new GuardDecision('block', $reasons, 'policy_rules');
        }

        try {
            $classification = $this->llm->classifyPrompt($prompt);
        } catch (\Throwable $exception) {
            Log::warning('Prompt classification unavailable; deterministic rules applied.', [
                'exception' => $exception::class,
            ]);

            $classification = null;
        }

        if (($classification['verdict'] ?? null) === 'block') {
            $classifierReasons = array_values(array_filter(
                $classification['reasons'],
                'is_string',
            ));

            return new GuardDecision(
                'block',
                $classifierReasons !== [] ? $classifierReasons : ['The safety classifier flagged this prompt.'],
                'llm_classifier',
            );
        }

        return new GuardDecision('allow', [], $classification === null ? 'rules_only' : 'llm_classifier');
    }
}
