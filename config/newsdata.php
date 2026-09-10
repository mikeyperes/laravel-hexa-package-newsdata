<?php
return [
    'version' => '2.0.6',

    /*
    |--------------------------------------------------------------------------
    | Default country filter
    |--------------------------------------------------------------------------
    |
    | Comma separated ISO 3166-1 alpha-2 codes sent as NewsData's `country`
    | parameter. Leaving this empty returns worldwide coverage, which swamps a
    | country-focused publication with unrelated markets. Empty preserves the
    | previous worldwide behaviour.
    |
    */

    'default_country' => env('NEWSDATA_DEFAULT_COUNTRY', 'us,gb,ca,au'),

];
