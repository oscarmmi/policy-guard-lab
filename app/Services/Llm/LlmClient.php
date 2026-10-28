<?php

namespace App\Services\Llm;

interface LlmClient
{
    /** @return array{verdict: string, reasons: list<string>}|null */
    public function classifyPrompt(string $prompt): ?array;

    public function answerQuestion(string $question): string;
}
