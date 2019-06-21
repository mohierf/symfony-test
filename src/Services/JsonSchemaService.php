<?php

namespace App\Services;

use App\Entity\JsonSchema;
use JsonSchema\Exception\InvalidSchemaException;
use JsonSchema\Validator;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class JsonSchemaService.
 */
class JsonSchemaService
{
    /**
     * Validate a JSON string against a given JSON schema.
     *
     * Based on: https://github.com/justinrainbow/json-schema
     *
     * @param string     $json   JSON string to validate
     * @param JsonSchema $schema
     *
     * @return bool
     */
    public function validate($json, JsonSchema $schema): bool
    {
        $objectToValidate = json_decode($json);

        $validator = new Validator();
        $validator->validate($objectToValidate, json_decode($schema->getContent()));

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = ' '.sprintf('[%s] %s', $error['property'], $error['message']);
            }

            throw new InvalidSchemaException(implode(', ', $errors), JsonResponse::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * @param JsonSchema $jsonSchema
     *
     * @return array
     */
    public function getPlaceholdersFromSchema(JsonSchema $jsonSchema): array
    {
        $placeholders = [];
        $this->_initializePlaceholders($jsonSchema->getContent(), $placeholders);

        return $placeholders;
    }

    /**
     * Extract placeholders from a Json Schema.
     *
     * @param        $object
     * @param        $placeholders
     * @param string $parent
     * @param string $prefix
     *
     * @return array
     */
    private function _initializePlaceholders($object, &$placeholders, $parent = '', string $prefix = ''): array
    {
        $object = $object instanceof stdClass ? $object : json_decode($object);
        $flat = [];
        $separator = '_';

        foreach ($object->properties as $key => $value) {
            if (isset($value->type)) {
                $type = $value->type;
                if ('array' === $type) {
                    $this->_initializePlaceholders($value->items, $placeholders, $parent, $key);
                } elseif ('object' === $type) {
                    if ('' === $parent) {
                        $prefix .= '_'.$key;
                        $this->_initializePlaceholders($value, $placeholders, $prefix, $prefix);
                    } else {
                        $prefix = $parent.'_'.$key;
                        $this->_initializePlaceholders($value, $placeholders, $key, $prefix);
                    }
                    $prefix = '';
                } else {
                    $key = ltrim($prefix.$separator.$key, $separator);
                    $placeholders[$key] = $key;
                }
            } elseif (isset($value->oneOf)) {
                $key = ltrim($prefix.$separator.$key, $separator);
                $placeholders[$key] = $key;
            }
        }

        return $flat;
    }

    /**
     * Get names of iterables elements (ie array elements) from Json Schema.
     *
     * @param $object
     *
     * @return array
     */
    public function getIterableElements($object): array
    {
        $object = $object instanceof stdClass ? $object : json_decode($object);

        $iterables = [];

        foreach ($object->properties as $key => $property) {
            if (isset($property->type)) {
                $type = $property->type;

                if ('array' === $type) {
                    $iterables[$key] = $this->getIterableElements($property->items);
                }
            }
        }

        return $iterables;
    }
}
