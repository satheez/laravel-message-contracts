<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\AsyncApi;

use Satheez\MessageContracts\JsonSchema\JsonSchemaGenerator;

class AsyncApiGenerator
{
    public function __construct(private readonly JsonSchemaGenerator $schemaGenerator) {}

    public function generate(array $contracts): array
    {
        $asyncapi = [
            'asyncapi' => '2.6.0',
            'info' => [
                'title' => config('app.name', 'Laravel Application').' Message Contracts',
                'version' => '1.0.0',
                'description' => 'Automatically generated AsyncAPI documentation for message contracts.',
            ],
            'channels' => [],
            'components' => [
                'messages' => [],
                'schemas' => [],
            ],
        ];

        $channelMessages = [];

        foreach ($contracts as $contractClass) {
            $name = $contractClass::contract();
            $version = (string) $contractClass::version();
            $id = "{$name}.v{$version}";

            // Payload schema
            $schemaResult = $this->schemaGenerator->generate($contractClass, false);
            $asyncapi['components']['schemas'][$id] = $schemaResult['schema'];

            // Message definition
            $messageId = "{$id}.message";
            $message = [
                'name' => $name,
                'title' => method_exists($contractClass, 'title') && $contractClass::title() ? $contractClass::title() : "{$name}:v{$version}",
                'summary' => $contractClass::description() ?? "Message contract for {$name} version {$version}",
                'payload' => [
                    '$ref' => "#/components/schemas/{$id}",
                ],
                'contentType' => 'application/json',
            ];

            if ($contractClass::deprecated()) {
                $message['deprecated'] = true;
            }

            if (method_exists($contractClass, 'tags') && ! empty($contractClass::tags())) {
                $message['tags'] = array_map(fn ($t): array => ['name' => $t], $contractClass::tags());
            }

            $asyncapi['components']['messages'][$messageId] = $message;

            // Group messages by channel and direction
            $channelName = method_exists($contractClass, 'channel') && $contractClass::channel() ? $contractClass::channel() : $name;
            $direction = method_exists($contractClass, 'direction') ? $contractClass::direction() : 'both';

            if (! isset($channelMessages[$channelName])) {
                $channelMessages[$channelName] = [
                    'publish' => [],
                    'subscribe' => [],
                ];
            }

            if (in_array($direction, ['publish', 'both'], true)) {
                $channelMessages[$channelName]['publish'][] = $messageId;
            }
            if (in_array($direction, ['subscribe', 'both'], true)) {
                $channelMessages[$channelName]['subscribe'][] = $messageId;
            }
        }

        // Build channels structure
        foreach ($channelMessages as $channelName => $directions) {
            $channelDef = [];

            // In AsyncAPI, if we 'subscribe' to a message, it means we provide a 'publish' operation for the consumer.
            // If we 'publish' a message, it means we provide a 'subscribe' operation for the consumer.
            // So: "our publish" = "operation subscribe". "our subscribe" = "operation publish".
            // Let's use the standard semantics:
            // The document describes the API. If the API publishes, consumers subscribe.
            // So our "publish" -> operation: "subscribe"
            // Our "subscribe" -> operation: "publish"

            if ($directions['publish'] !== []) {
                // Application publishes, so consumers subscribe to it
                $channelDef['subscribe'] = $this->buildOperation($directions['publish']);
            }

            if ($directions['subscribe'] !== []) {
                // Application subscribes, so consumers publish to it
                $channelDef['publish'] = $this->buildOperation($directions['subscribe']);
            }

            if ($channelDef !== []) {
                $asyncapi['channels'][$channelName] = $channelDef;
            }
        }

        return $asyncapi;
    }

    private function buildOperation(array $messageIds): array
    {
        $operation = [];

        if (count($messageIds) === 1) {
            $operation['message'] = [
                '$ref' => "#/components/messages/{$messageIds[0]}",
            ];
        } else {
            $oneOf = array_map(fn ($id): array => ['$ref' => "#/components/messages/{$id}"], $messageIds);
            $operation['message'] = [
                'oneOf' => $oneOf,
            ];
        }

        return $operation;
    }
}
