<?php

namespace App\Services\Guard;

class GuardDecision
{
    /** @param list<string> $reasons */
    public function __construct(
        public readonly string $verdict,
        public readonly array $reasons,
        public readonly string $source,
    ) {}
}
