<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Contracts Path
    |--------------------------------------------------------------------------
    |
    | The default directory where generated message contract classes are saved.
    |
    */
    'contracts_path' => app_path('MessageContracts'),

    /*
    |--------------------------------------------------------------------------
    | Contracts Namespace
    |--------------------------------------------------------------------------
    |
    | The default namespace used when generating message contract classes.
    |
    */
    'contracts_namespace' => 'App\\MessageContracts',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, incoming messages must contain only fields declared in the
    | contract rules. Unknown fields will cause validation to fail.
    |
    */
    'strict' => true,

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Controls whether outgoing and incoming payloads are validated.
    |
    */
    'validate_outgoing' => true,

    'validate_incoming' => true,

    /*
    |--------------------------------------------------------------------------
    | Message Keys
    |--------------------------------------------------------------------------
    |
    | The top-level key names used in the serialized message envelope.
    | You may customise these if you need to integrate with an existing system
    | that uses different naming conventions.
    |
    */
    'message_keys' => [
        'contract' => 'contract',
        'version' => 'version',
        'payload' => 'payload',
        'meta' => 'meta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Configuration
    |--------------------------------------------------------------------------
    |
    | Controls automatic population of the `meta` envelope field.
    |
    */
    'meta' => [
        'include_message_id' => true,
        'include_created_at' => true,
        'message_id_strategy' => 'ulid',   // 'ulid' or 'uuid'
        'include_source' => false,
        'source' => env('APP_NAME', 'laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registered Contracts
    |--------------------------------------------------------------------------
    |
    | List your application message contract classes here. The registry will
    | resolve the correct contract class from an incoming message's `contract`
    | and `version` fields.
    |
    | Example:
    |   \App\MessageContracts\UserRegisteredV1Message::class,
    |
    */
    'contracts' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Schema Export
    |--------------------------------------------------------------------------
    |
    | Configuration for exporting contracts as JSON Schemas.
    |
    */
    'json_schema' => [
        'enabled' => true,
        'output_path' => base_path('docs/schemas'),
        'draft' => '2020-12',
        'pretty' => true,
        'additional_properties' => false,
        'include_examples' => true,
        'fail_on_unsupported_rules' => false,
        'id_base_url' => null,
    ],

];
