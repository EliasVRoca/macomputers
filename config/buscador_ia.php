<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Buscador con IA
    |--------------------------------------------------------------------------
    |
    | Configuración del Modo IA del buscador (RF20 · CU19). Las preguntas en
    | lenguaje natural se responden con DeepSeek V4.1 Flash a través de la API
    | de OpenCode Go (formato compatible con OpenAI Chat Completions).
    |
    */

    // false oculta el Modo IA en el buscador aunque exista una clave.
    'habilitado' => (bool) env('BUSCADOR_IA_HABILITADO', true),

    'clave' => env('OPENCODEGO_API_KEY'),

    'url' => env('BUSCADOR_IA_URL', 'https://opencode.ai/inference/go/openai/v1/chat/completions'),

    'modelo' => env('BUSCADOR_IA_MODELO', 'deepseek-v4.1-flash'),

    'tiempo_limite' => (int) env('BUSCADOR_IA_TIMEOUT', 30),

    'max_tokens' => (int) env('BUSCADOR_IA_MAX_TOKENS', 2000),

    'temperatura' => (float) env('BUSCADOR_IA_TEMPERATURA', 0),

    'user_agent' => env('BUSCADOR_IA_USER_AGENT', 'M&A Computers/1.0'),

    // Máximo de productos del catálogo que se envían al modelo por consulta.
    'max_productos' => (int) env('BUSCADOR_IA_MAX_PRODUCTOS', 2000),

    // Búsqueda en dos fases: una llamada chica clasifica las categorías
    // relevantes para la pregunta y la consulta principal recibe solo los
    // productos de esas categorías. false desactiva la fase de clasificación
    // y se envía el catálogo completo, como fallback.
    'clasificacion' => (bool) env('BUSCADOR_IA_CLASIFICAR', true),

    // Máximo de categorías que el clasificador puede elegir por pregunta.
    'max_categorias' => (int) env('BUSCADOR_IA_MAX_CATEGORIAS', 3),

    // Timeout más corto para la llamada de clasificación de categorías.
    'tiempo_limite_clasificacion' => (int) env('BUSCADOR_IA_TIMEOUT_CLASIFICACION', 10),

    // Límites de IDs que el modelo puede pedir en su respuesta.
    'max_productos_respuesta' => (int) env('BUSCADOR_IA_MAX_RESPUESTA', 6),
    'max_alternativas_respuesta' => (int) env('BUSCADOR_IA_MAX_ALTERNATIVAS', 3),

];
