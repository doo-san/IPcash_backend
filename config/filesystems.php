<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Pièces KYC (pièce d'identité, selfie) — disque dédié et séparé du
        // `local` générique pour qu'une règle de sécurité (rétention,
        // chiffrement du volume, etc.) puisse un jour cibler ce disque
        // précisément. Jamais public : `visibility: private` + `serve: true`
        // fait servir les fichiers via une route Laravel dédiée
        // (`storage.kyc`, signature + expiration obligatoires, voir
        // `ServeFile`), jamais par un chemin direct. `url` doit être distinct
        // de celui du disque `local` (sinon Laravel refuse de démarrer :
        // deux disques servis ne peuvent pas partager le même préfixe
        // d'URL). Les liens affichés dans l'admin sont générés à la demande
        // par `ImageEntry::visibility('private')` (voir
        // `KycDocumentResource`), valables 5 minutes.
        'kyc' => [
            'driver' => 'local',
            'root' => storage_path('app/private/kyc'),
            'url' => '/kyc-storage',
            'serve' => true,
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        // Attestations d'assurance émises (PDF). Jamais servi par une URL :
        // le client les télécharge via `GET /insurance/policies/{id}/
        // attestation` (authentifié, limité à ses propres contrats) et
        // l'admin depuis `InsurancePolicyResource`.
        'insurance' => [
            'driver' => 'local',
            'root' => storage_path('app/private/insurance'),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
