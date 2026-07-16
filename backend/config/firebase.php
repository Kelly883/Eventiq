<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Admin SDK Configuration
    |--------------------------------------------------------------------------
    |
    | FIREBASE_CREDENTIALS should point to the service account JSON file
    | downloaded from Firebase Console > Project Settings > Service Accounts.
    | Store the actual file outside version control (e.g.
    | storage/app/firebase-credentials.json, which is gitignored) - never
    | commit it. In production, prefer setting FIREBASE_CREDENTIALS_JSON
    | directly as a secret env var instead of a file path, if your hosting
    | supports it.
    |
    */

    'credentials_path' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),

    'project_id' => env('FIREBASE_PROJECT_ID'),

];
