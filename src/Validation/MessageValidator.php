<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\DTO\MessageValidationResult;
use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\SpatieData\DataPayloadContract;

/**
 * Validates payload data against a MessageContract's rules().
 *
 * Used internally by MessageContract::message(), MessageContract::validate(),
 * and Message::validate() / Message::validateOrFail().
 */
final class MessageValidator
{
    /**
     * Validate a payload array against the contract's rules.
     * Never throws — returns a result object.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     */
    public function validate(string $contractClass, array $payload): MessageValidationResult
    {
        // Spatie Laravel Data Integration
        if (is_subclass_of($contractClass, DataPayloadContract::class)) {
            $dataClass = $contractClass::dataClass();

            if (class_exists($dataClass) && method_exists($dataClass, 'validateAndCreate')) {
                try {
                    $dataClass::validateAndCreate($payload);

                    return MessageValidationResult::pass();
                } catch (ValidationException $e) {
                    return MessageValidationResult::fail($e->errors());
                }
            }
        }

        $rules = $contractClass::rules();

        // Strict mode: reject keys not present in the rules definition.
        if (config('message-contracts.strict', true)) {
            $allowedKeys = array_keys($rules);
            $extraKeys = array_diff(array_keys($payload), $allowedKeys);

            if ($extraKeys !== []) {
                $errors = [];
                foreach ($extraKeys as $key) {
                    $errors[$key] = ["The {$key} field is not allowed by contract {$contractClass::contract()} v{$contractClass::version()}."];
                }

                return MessageValidationResult::fail($errors);
            }
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return MessageValidationResult::fail(
                $validator->errors()->toArray(),
            );
        }

        return MessageValidationResult::pass();
    }

    /**
     * Validate a payload array, throwing on failure.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     *
     * @throws MessageValidationException
     */
    public function validateOrFail(string $contractClass, array $payload): void
    {
        $result = $this->validate($contractClass, $payload);

        if ($result->failed()) {
            throw MessageValidationException::forContract(
                $contractClass::contract(),
                $contractClass::version(),
                $contractClass,
                $result->errors(),
            );
        }
    }

    /**
     * Validate and, if valid, build a Message DTO (producer-side factory).
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     *
     * @throws MessageValidationException
     */
    public function createMessage(string $contractClass, array $payload, array $meta = []): Message
    {
        if (config('message-contracts.validate_outgoing', true)) {
            $this->validateOrFail($contractClass, $payload);
        }

        $resolvedMeta = $this->buildMeta($meta);

        return new Message(
            contract: $contractClass::contract(),
            version: $contractClass::version(),
            payload: $payload,
            meta: $resolvedMeta,
        );
    }

    // ──────────────────────────────────────────────
    // Meta generation
    // ──────────────────────────────────────────────

    /**
     * Merge auto-generated meta fields with caller-supplied meta.
     *
     * @param  array<string, mixed>  $userMeta
     * @return array<string, mixed>
     */
    private function buildMeta(array $userMeta): array
    {
        $metaConfig = config('message-contracts.meta', []);
        $auto = [];

        if ($metaConfig['include_message_id'] ?? true) {
            $strategy = $metaConfig['message_id_strategy'] ?? 'ulid';
            $auto['message_id'] = $strategy === 'uuid'
                ? (string) Str::uuid()
                : (string) Str::ulid();
        }

        if ($metaConfig['include_created_at'] ?? true) {
            $auto['created_at'] = now()->toISOString();
        }

        if (($metaConfig['include_source'] ?? false) && isset($metaConfig['source'])) {
            $auto['source'] = $metaConfig['source'];
        }

        // User-supplied meta overrides auto-generated values.
        return array_merge($auto, $userMeta);
    }
}
