<?php

return [
    'paths' => ['api/*', 'oauth/*', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],  

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => true,
];