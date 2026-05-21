<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\JsonSchema;

class LaravelRuleMapper
{
    private array $warnings = [];

    public function map(array $rules): array
    {
        $this->warnings = [];
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
            'additionalProperties' => config('message-contracts.json_schema.additional_properties', false),
        ];

        // Normalise rules to arrays
        $normalizedRules = [];
        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }
            $normalizedRules[$field] = $fieldRules;
        }

        // Build the tree
        foreach ($normalizedRules as $field => $fieldRules) {
            $this->applyRulesToSchema($schema, explode('.', (string) $field), $fieldRules);
        }

        // Clean up empty required arrays
        $this->cleanupSchema($schema);

        return [
            'schema' => $schema,
            'warnings' => $this->warnings,
        ];
    }

    private function applyRulesToSchema(array &$currentLevel, array $path, array $rules): void
    {
        $part = array_shift($path);

        if ($part === '*') {
            // Array items
            if (! isset($currentLevel['items'])) {
                $currentLevel['items'] = [
                    'type' => 'object',
                    'properties' => [],
                    'additionalProperties' => config('message-contracts.json_schema.additional_properties', false),
                ];
            }
            if ($path === []) {
                // Primitive array item
                $this->applyPropertyRules($currentLevel['items'], $rules, '*');
            } else {
                // Object array item
                $this->applyRulesToSchema($currentLevel['items'], $path, $rules);
            }

            return;
        }

        if (! isset($currentLevel['properties'])) {
            $currentLevel['properties'] = [];
        }

        if (! isset($currentLevel['properties'][$part])) {
            $currentLevel['properties'][$part] = [];
        }

        if ($path === []) {
            // Leaf node
            if (in_array('required', $rules, true)) {
                if (! isset($currentLevel['required'])) {
                    $currentLevel['required'] = [];
                }
                if (! in_array($part, $currentLevel['required'], true)) {
                    $currentLevel['required'][] = $part;
                }
            }

            $this->applyPropertyRules($currentLevel['properties'][$part], $rules, $part);
        } else {
            // Nested object
            if (! isset($currentLevel['properties'][$part]['type'])) {
                $currentLevel['properties'][$part]['type'] = 'object';
                $currentLevel['properties'][$part]['additionalProperties'] = config('message-contracts.json_schema.additional_properties', false);
            }
            if (in_array('required', $rules, true)) {
                if (! isset($currentLevel['required'])) {
                    $currentLevel['required'] = [];
                }
                if (! in_array($part, $currentLevel['required'], true)) {
                    $currentLevel['required'][] = $part;
                }
            }

            $this->applyRulesToSchema($currentLevel['properties'][$part], $path, $rules);
        }
    }

    private function applyPropertyRules(array &$property, array $rules, string $fieldName): void
    {
        $isNullable = in_array('nullable', $rules, true);
        $types = [];
        $enum = [];
        $format = null;
        $minimum = null;
        $maximum = null;
        $minLength = null;
        $maxLength = null;

        foreach ($rules as $rule) {
            if (is_object($rule)) {
                $ruleClass = $rule::class;
                if (method_exists($rule, '__toString')) {
                    $rule = (string) $rule;
                } else {
                    $this->addWarning($fieldName, "Unsupported rule object: {$ruleClass}");

                    continue;
                }
            }

            if (! is_string($rule)) {
                continue;
            }

            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

            switch ($ruleName) {
                case 'string':
                    $types[] = 'string';
                    break;
                case 'integer':
                case 'int':
                    $types[] = 'integer';
                    break;
                case 'numeric':
                    $types[] = 'number';
                    break;
                case 'boolean':
                case 'bool':
                    $types[] = 'boolean';
                    break;
                case 'array':
                    $types[] = 'array';
                    break;
                case 'date':
                    $types[] = 'string';
                    $format = 'date-time';
                    break;
                case 'email':
                    $types[] = 'string';
                    $format = 'email';
                    break;
                case 'uuid':
                    $types[] = 'string';
                    $format = 'uuid';
                    break;
                case 'url':
                    $types[] = 'string';
                    $format = 'uri';
                    break;
                case 'json':
                    $types[] = 'string';
                    $property['contentMediaType'] = 'application/json';
                    break;
                case 'in':
                    $enum = $parameters;
                    break;
                case 'min':
                    if (in_array('integer', $rules, true) || in_array('int', $rules, true) || in_array('numeric', $rules, true)) {
                        $minimum = (float) $parameters[0];
                    } elseif (in_array('string', $rules, true)) {
                        $minLength = (int) $parameters[0];
                    } elseif (in_array('array', $rules, true)) {
                        $property['minItems'] = (int) $parameters[0];
                    }
                    break;
                case 'max':
                    if (in_array('integer', $rules, true) || in_array('int', $rules, true) || in_array('numeric', $rules, true)) {
                        $maximum = (float) $parameters[0];
                    } elseif (in_array('string', $rules, true)) {
                        $maxLength = (int) $parameters[0];
                    } elseif (in_array('array', $rules, true)) {
                        $property['maxItems'] = (int) $parameters[0];
                    }
                    break;
                case 'size':
                    if (in_array('string', $rules, true)) {
                        $minLength = (int) $parameters[0];
                        $maxLength = (int) $parameters[0];
                    }
                    break;
                case 'required':
                case 'nullable':
                    break;
                case 'exists':
                case 'unique':
                case 'confirmed':
                case 'current_password':
                case 'prohibited_if':
                case 'required_if':
                case 'required_unless':
                case 'exclude_if':
                    $this->addWarning($fieldName, "Unsupported rule: {$ruleName}");
                    break;
            }
        }

        if ($types !== []) {
            $types = array_unique($types);
            if ($isNullable) {
                $types[] = 'null';
            }
            $property['type'] = count($types) === 1 ? $types[0] : array_values($types);
        }

        if ($format !== null) {
            $property['format'] = $format;
        }
        if ($enum !== []) {
            $property['enum'] = $enum;
        }
        if ($minimum !== null) {
            $property['minimum'] = $minimum;
        }
        if ($maximum !== null) {
            $property['maximum'] = $maximum;
        }
        if ($minLength !== null) {
            $property['minLength'] = $minLength;
        }
        if ($maxLength !== null) {
            $property['maxLength'] = $maxLength;
        }
    }

    private function cleanupSchema(array &$schema): void
    {
        if (isset($schema['properties']) && ! empty($schema['properties'])) {
            if (isset($schema['type'])) {
                if ($schema['type'] === 'array') {
                    $schema['type'] = 'object';
                } elseif (is_array($schema['type']) && in_array('array', $schema['type'], true)) {
                    $schema['type'] = array_map(fn ($t): mixed => $t === 'array' ? 'object' : $t, $schema['type']);
                }
            }
            foreach ($schema['properties'] as &$prop) {
                $this->cleanupSchema($prop);
            }
        }
        if (isset($schema['required']) && empty($schema['required'])) {
            unset($schema['required']);
        }
        if (isset($schema['items'])) {
            $this->cleanupSchema($schema['items']);
        }
    }

    private function addWarning(string $field, string $message): void
    {
        $this->warnings[] = [
            'field' => $field,
            'message' => $message,
        ];
    }
}
