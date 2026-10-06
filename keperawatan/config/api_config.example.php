<?php
// Set OPENAI_API_KEY in the server environment.
return [
    'provider' => 'openai',
    'api_key' => getenv('OPENAI_API_KEY') ?: '',
    'model' => 'gpt-4o-mini',
];