<?php

namespace App\Support\Dashboard;

use App\Support\History\HistoryCycleView;

final class DashboardWantPresenter
{
    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     status: string,
     *     stage: string,
     *     summary: string,
     *     focus_label: string,
     *     focus_text: string,
     *     plan_text: ?string,
     *     grounded_summary: ?string,
     *     validation_summary: ?string,
     *     action_status: ?string,
     *     open_reason: ?string,
     *     outcome: ?string
     * }
     */
    public function present(HistoryCycleView $cycle): array
    {
        $planText = $this->trimmed($cycle->planRevision?->plan_text);
        $groundedSummary = $this->trimmed($cycle->planRevision?->grounded_summary);
        $validationSummary = $this->trimmed($cycle->validationRun?->summary);
        $outcome = $this->trimmed($cycle->outcomeLog?->outcome);

        $focusText = $planText
            ?? $groundedSummary
            ?? $validationSummary
            ?? $outcome
            ?? $cycle->want->raw_text;

        $summary = $groundedSummary
            ?? $validationSummary
            ?? $cycle->openReason
            ?? $outcome
            ?? $cycle->want->raw_text;

        return [
            'id' => $cycle->want->id,
            'title' => $cycle->want->title,
            'status' => $cycle->want->status,
            'stage' => DashboardWantStage::derive($cycle),
            'summary' => $summary,
            'focus_label' => $this->focusLabel($planText, $groundedSummary, $validationSummary, $outcome),
            'focus_text' => $focusText,
            'plan_text' => $planText,
            'grounded_summary' => $groundedSummary,
            'validation_summary' => $validationSummary,
            'action_status' => $cycle->actionRun?->status,
            'open_reason' => $cycle->openReason,
            'outcome' => $outcome,
        ];
    }

    private function focusLabel(?string $planText, ?string $groundedSummary, ?string $validationSummary, ?string $outcome): string
    {
        return match (true) {
            $planText !== null => 'Latest written plan',
            $groundedSummary !== null => 'Grounded summary',
            $validationSummary !== null => 'Validation summary',
            $outcome !== null => 'Latest outcome',
            default => 'Raw want',
        };
    }

    private function trimmed(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
