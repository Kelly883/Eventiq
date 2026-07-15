<?php

return [

    'enabled' => env('MJML_ENABLED', false),

    // Node.js binary - MJML is a Node package with no PHP port, so this
    // server needs Node installed for MJML rendering to work at all.
    'node_binary' => env('MJML_NODE_BINARY', 'node'),

    'render_script_path' => env(
        'MJML_RENDER_SCRIPT_PATH',
        base_path('node-tools/mjml/render.js')
    ),

];
