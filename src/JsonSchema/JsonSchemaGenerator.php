<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\JsonSchema;

use Satheez\MessageContracts\Contracts\MessageContract;

class JsonSchemaGenerator
{
    public function __construct(private readonly LaravelRuleMapper $mapper) {}

    /**
     * @param  class-string<MessageContract>  $contractClass
     */
    public function generate(string $contractClass, bool $includeEnvelope = false): array
    {
        $warnings = [];
        $baseSchema = null;

        if (method_exists($contractClass, 'schema')) {
            $baseSchema = $contractClass::schema();
        }

        if ($baseSchema === null) {
            $mapped = $this->mapper->map($contractClass::rules());
            $baseSchema = $mapped['schema'];
            $warnings = $mapped['warnings'];
        }

        $contractName = $contractClass::contract();
        $version = $contractClass::version();
        $draft = config('message-contracts.json_schema.draft', '2020-12');
        $idBaseUrl = config('message-contracts.json_schema.id_base_url');

        $baseId = "{$contractName}.v{$version}";

        $id = $idBaseUrl ? rtrim((string) $idBaseUrl, '/')."/{$baseId}.schema.json" : $baseId;

        $schema = [
            '$schema' => "https://json-schema.org/draft/{$draft}/schema",
            '$id' => $id,
            'title' => "{$contractName}:v{$version}",
            'description' => "Payload schema for {$contractName} version {$version}.",
        ] + $baseSchema;

        if (config('message-contracts.json_schema.include_examples', true) && method_exists($contractClass, 'example')) {
            $example = $contractClass::example();
            if (! empty($example)) {
                $schema['examples'] = [$example];
            }
        }

        if (! $includeEnvelope) {
            return [
                'schema' => $schema,
                'warnings' => $warnings,
            ];
        }

        $messageId = $idBaseUrl ? rtrim((string) $idBaseUrl, '/')."/{$baseId}.message.schema.json" : "{$baseId}.message";

        $messageSchema = [
            '$schema' => "https://json-schema.org/draft/{$draft}/schema",
            '$id' => $messageId,
            'title' => "{$contractName}:v{$version} message",
            'type' => 'object',
            'required' => ['contract', 'version', 'payload'],
            'properties' => [
                'contract' => [
                    'type' => 'string',
                    'const' => $contractName,
                ],
                'version' => [
                    'type' => 'integer',
                    'const' => $version,
                ],
                'payload' => [
                    '$ref' => "{$baseId}.schema.json",
                ],
                'meta' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => [
                        'message_id' => [
                            'type' => 'string',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                        ],
                    ],
                ],
            ],
            'additionalProperties' => false,
        ];

        return [
            'schema' => $messageSchema,
            'payload_schema' => $schema,
            'warnings' => $warnings,
        ];
    }
}
