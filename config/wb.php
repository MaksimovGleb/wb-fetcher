<?php

return [
    // Base URL for the WB API, e.g. http://109.73.206.144:6969
    'base_url' => env('WB_API_BASE_URL', 'http://109.73.206.144:6969'),

    // API key passed as `key` query parameter
    'key' => env('WB_API_KEY', ''),

    // Default page size (API limit is 500)
    'limit' => env('WB_API_LIMIT', 500),
];