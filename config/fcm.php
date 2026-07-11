<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (HTTP v1)
    |--------------------------------------------------------------------------
    |
    | Set FCM_PROJECT_ID and point FCM_SERVICE_ACCOUNT to a Firebase service
    | account JSON file (absolute path or relative to the project root).
    | When either is missing, push sending is skipped gracefully.
    |
    */
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account' => env('FCM_SERVICE_ACCOUNT', 'storage/app/firebase-service-account.json'),
];
