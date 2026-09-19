<?php

namespace App\Support;

/**
 * The questionnaire the platform provides for site visits (M25.1). Volunteers
 * ask these during the visit; the answers, with their photos, become the
 * verification story. The platform only supplies the questions — the
 * volunteer is the verifier.
 */
final class VerificationQuestionnaire
{
    /** @var array<string, string> question id => question text */
    public const QUESTIONS = [
        'seller_identity' => "Did the person present match the seller's name?",
        'address' => 'Is the site at the address the seller listed?',
        'goods' => 'Do the listed goods exist and match the description?',
        'condition' => 'Were the goods and premises in good condition?',
        'pricing' => 'Did the seller confirm the listed prices?',
        'photos' => 'Did you take clear photos of the site and goods?',
    ];

    /**
     * @return array<int, array{id: string, question: string}>
     */
    public static function all(): array
    {
        return collect(self::QUESTIONS)
            ->map(fn (string $question, string $id) => ['id' => $id, 'question' => $question])
            ->values()
            ->all();
    }

    public static function label(string $id): string
    {
        return self::QUESTIONS[$id] ?? $id;
    }

    /**
     * Human-readable answer lines from a stored checklist.
     *
     * @param  array<string, mixed>|null  $checklist
     * @return array<int, string>
     */
    public static function summarise(?array $checklist): array
    {
        if ($checklist === null || $checklist === []) {
            return [];
        }

        $lines = [];

        foreach (self::QUESTIONS as $id => $question) {
            if (! array_key_exists($id, $checklist)) {
                continue;
            }

            $answer = $checklist[$id];
            $value = is_bool($answer)
                ? ($answer ? 'Yes' : 'No')
                : (string) $answer;

            $lines[] = $question.' — '.$value;
        }

        return $lines;
    }
}
