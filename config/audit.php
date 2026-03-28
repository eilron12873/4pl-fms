<?php

return [

    'export' => [
        'max_rows' => (int) env('AUDIT_EXPORT_MAX_ROWS', 10_000),
        'max_date_span_days' => (int) env('AUDIT_EXPORT_MAX_DATE_SPAN_DAYS', 366),
        'require_date_range' => filter_var(env('AUDIT_EXPORT_REQUIRE_DATE_RANGE', true), FILTER_VALIDATE_BOOL),
    ],

    'prune' => [
        'enabled' => filter_var(env('AUDIT_PRUNE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'default_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 2555),
    ],

];
