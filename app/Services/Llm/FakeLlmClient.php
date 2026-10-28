<?php

namespace App\Services\Llm;

use App\Services\Guard\HarborDeskFaq;

class FakeLlmClient implements LlmClient
{
    /** @var list<string> */
    public array $classifiedPrompts = [];

    /** @var list<string> */
    public array $answeredQuestions = [];

    /** @var array{verdict: string, reasons: list<string>}|null */
    public ?array $classification = null;

    public ?string $answer = null;

    public function classifyPrompt(string $prompt): ?array
    {
        $this->classifiedPrompts[] = $prompt;

        return $this->classification;
    }

    public function answerQuestion(string $question): string
    {
        $this->answeredQuestions[] = $question;

        return $this->answer ?? HarborDeskFaq::answer($question);
    }
}
