<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Archive and Retention Defaults
    |--------------------------------------------------------------------------
    |
    | These values are only used by the optional console commands for
    | archiving journals and pruning posting source payloads. They do not
    | change runtime behavior unless you explicitly schedule or invoke the
    | commands.
    |
    */

    'archive' => [
        // Defaults used by core-accounting:archive-journals when --days is not provided.
        'journals_after_days' => 1825, // ~5 years
    ],

    'retention' => [
        // Defaults used by core-accounting:prune-posting-payloads when --days is not provided.
        'posting_source_payload_days' => 365, // 1 year
    ],
];

