<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Require company name on save
    |--------------------------------------------------------------------------
    |
    | When true, PUT company settings rejects an empty legal entity name.
    | Set COMPANY_SETTINGS_REQUIRE_NAME=false in .env for staged go-live if needed.
    |
    */

    'require_company_name' => filter_var(
        env('COMPANY_SETTINGS_REQUIRE_NAME', true),
        FILTER_VALIDATE_BOOL
    ),

];
