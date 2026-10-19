<?php

return [
    'driver' => env('IMAGE_SEARCH_DRIVER', 'auto'),
    'timeout' => (int) env('IMAGE_SEARCH_TIMEOUT', 25),
    'descarga_timeout' => (int) env('IMAGE_DOWNLOAD_TIMEOUT', 20),
    'resultados' => (int) env('IMAGE_SEARCH_RESULTADOS', 20),
    'max_bytes' => 5 * 1024 * 1024,
    'max_pixeles' => 12_000_000,
    'ancho_max' => 1600,
    'alto_max' => 1600,
    'ancho_fuente_max' => 8000,
    'alto_fuente_max' => 8000,
    'calidad_webp' => 82,
    'cache_minutos' => 10,
    'max_redirecciones' => 3,
    'user_agent' => env('IMAGE_SEARCH_USER_AGENT', 'M&A Computers/1.0'),

    'proveedores' => [
        'brave' => [
            'key' => env('BRAVE_SEARCH_KEY'),
            'url' => 'https://api.search.brave.com/res/v1/images/search',
        ],
        'openverse' => [
            'url' => 'https://api.openverse.org/v1/images/',
        ],
        'wikimedia' => [
            'url' => 'https://commons.wikimedia.org/w/api.php',
        ],
    ],
];
