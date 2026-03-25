<?php

namespace App\Support\Dashboard;

use App\Support\History\HistoryCycleView;

final class DashboardWantStage
{
    private const BLOCKED_ACTION_STATUSES = [
        'failed',
        'abandoned',
        'completed_with_defect_discovery',
        'superseded',
    ];

    public static function derive(HistoryCycleView $cycle): string
    {
        if (self::isCompleted($cycle)) {
            return 'completed';
        }

        if (self::isBlocked($cycle)) {
            return 'blocked';
        }

        if ($cycle->actionRun !== null) {
            return 'acting';
        }

        if ($cycle->planRevision !== null) {
            return 'planned';
        }

        if ($cycle->validationRun !== null) {
            return 'validated';
        }

        if ($cycle->constraintSnapshot !== null) {
            return 'constrained';
        }

        return 'captured';
    }

    private static function isCompleted(HistoryCycleView $cycle): bool
    {
        return $cycle->actionRun?->status === 'completed'
            && $cycle->outcomeLog !== null;
    }

    private static function isBlocked(HistoryCycleView $cycle): bool
    {
        if (in_array($cycle->actionRun?->status, self::BLOCKED_ACTION_STATUSES, true)) {
            return true;
        }

        return self::containsBlockedSignal($cycle->outcomeLog?->outcome)
            || self::containsBlockedSignal($cycle->outcomeLog?->reflection);
    }

    private static function containsBlockedSignal(?string $text): bool
    {
        if (! is_string($text) || trim($text) === '') {
            return false;
        }

        $normalized = strtolower($text);

        return str_contains($normalized, 'blocked')
            || str_contains($normalized, 'blocker')
            || str_contains($normalized, 'defect')
            || str_contains($normalized, 'failed');
    }
}
