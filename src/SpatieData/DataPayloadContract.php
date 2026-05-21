<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\SpatieData;

use Satheez\MessageContracts\Contracts\MessageContract;
use Spatie\LaravelData\Data;

abstract class DataPayloadContract extends MessageContract
{
    /**
     * The class name of the Spatie Data object.
     *
     * @return class-string<Data>
     */
    abstract public static function dataClass(): string;

    /**
     * Dynamically load rules from the Spatie Data class.
     */
    public static function rules(): array
    {
        $dataClass = static::dataClass();

        if (! class_exists($dataClass)) {
            return [];
        }

        if (method_exists($dataClass, 'getValidationRules')) {
            // Provide an empty array as payload since we just want the base schema rules
            return $dataClass::getValidationRules([]);
        }

        return [];
    }
}
