<?php

return [
    // Enable the one‑time bootstrap route that lets an authenticated user create the
    // very first admin when no admin exists. Set to false in production after the first
    // admin has been created.
    'allow_first_admin' => env('ALLOW_FIRST_ADMIN', false),
];
