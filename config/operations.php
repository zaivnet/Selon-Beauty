<?php

return [
    'missing_checkout_critical_after_minutes' => (int) env('OPERATIONS_MISSING_CHECKOUT_CRITICAL_MINUTES', 120),
    'backup_daily_overdue_hours' => (int) env('OPERATIONS_BACKUP_DAILY_OVERDUE_HOURS', 36),
    'backup_weekly_overdue_hours' => (int) env('OPERATIONS_BACKUP_WEEKLY_OVERDUE_HOURS', 192),
    'review_lookback_days' => (int) env('OPERATIONS_REVIEW_LOOKBACK_DAYS', 31),
];
