<?php

namespace App\Services\Guard;

class HarborDeskFaq
{
    public static function knowledgeBase(): string
    {
        return <<<'FAQ'
Refunds: You can request a refund within 30 days of purchase. Approved refunds return to the original payment method; allow 5–10 business days.
Shipping: Standard shipping takes 3–5 business days. Orders over $50 ship free.
Support hours: Monday–Friday, 9:00 AM–5:00 PM Eastern Time.
Contact: Email help@harbordesk.example. Never share passwords or full payment details by email.
FAQ;
    }

    public static function answer(string $question): string
    {
        $normalized = mb_strtolower($question);

        if (str_contains($normalized, 'refund') || str_contains($normalized, 'return')) {
            return 'You can request a refund within 30 days of purchase. Approved refunds go back to the original payment method and usually take 5–10 business days.';
        }

        if (str_contains($normalized, 'ship') || str_contains($normalized, 'delivery')) {
            return 'Standard shipping takes 3–5 business days. Orders over $50 ship free.';
        }

        if (str_contains($normalized, 'hour') || str_contains($normalized, 'open')) {
            return 'Harbor Desk support is available Monday–Friday, 9:00 AM–5:00 PM Eastern Time.';
        }

        if (str_contains($normalized, 'contact') || str_contains($normalized, 'email') || str_contains($normalized, 'help')) {
            return 'You can reach Harbor Desk at help@harbordesk.example. Never share passwords or full payment details by email.';
        }

        return 'I do not have that information in the Harbor Desk FAQ. Please contact help@harbordesk.example and the support team can help.';
    }
}
