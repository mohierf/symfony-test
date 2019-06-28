<?php

namespace App\Services;

use App\Entity\JsonField;
use App\Entity\JsonSchema;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonSchema\Exception\InvalidSchemaException;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class JsonSchemaService.
 */
class JsonSchemaService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * JsonSchemaService constructor.
     *
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $em
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Validate a JSON string against a given JSON schema.
     *
     * Based on: https://github.com/justinrainbow/json-schema
     *
     * @param string     $json                 JSON string to validate
     * @param JsonSchema $schema
     * @param bool       $schemaValidationMode
     *
     * @return bool
     */
    public function validate($json, JsonSchema $schema, bool $schemaValidationMode = false): bool
    {
        $objectToValidate = json_decode($json);

        $validator = new Validator();
        if ($schemaValidationMode) {
            try {
                // Validate with a schema constraint
                $this->logger->info('Validating a Json schema...');
                $validator->validate($objectToValidate,
                    json_decode($schema->getContent()), Constraint::CHECK_MODE_VALIDATE_SCHEMA);
                $this->logger->info('The schema is valid.');
            } catch (Exception $exp) {
                /*
                 * The current version of the validation library raises a Warning exception:
                 * - Warning: array_key_exists() expects parameter 2 to be array, boolean given []
                 * This exception happens when validating the PaymentSchedule schema only!
                 *
                 * Note that this has no impact on the schema validity. thus, catching and logging
                 * the exception avoids to break the current process.
                 *
                 * todo: more investigation to understand why the initial schema provokes this error!
                 */
                $this->logger->warning('Schema validation exception: '.$exp->getMessage());
                foreach ($validator->getErrors() as $error) {
                    $errorMessage = ' '.sprintf('[%s] %s', $error['property'], $error['message']);
                    $this->logger->warning('Schema validation error: '.$errorMessage);
                }
            }
        } else {
            $validator->validate($objectToValidate, json_decode($schema->getContent()));
        }

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errorMessage = ' '.sprintf('[%s] %s', $error['property'], $error['message']);
                $this->logger->warning('Schema validation error: '.$errorMessage);
                $errors[] = $errorMessage;
            }

            throw new InvalidSchemaException(implode(', ', $errors), JsonResponse::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * @param JsonSchema $jsonSchema
     *
     * @return Collection
     */
    public function getFieldsFromSchema(JsonSchema $jsonSchema): Collection
    {
        $this->em->getRepository(JsonSchema::class);

        $fields = $jsonSchema->getJsonFields();

        $this->logger->info('getFieldsFromSchema - Get the Json fields ('.count($fields).') from: '.$jsonSchema->getName());
        $this->_initializeFields($jsonSchema, $jsonSchema->getContent(), $fields);
        $this->logger->info('getFieldsFromSchema - Got: '.count($fields).' fields.');

        $this->em->flush();

        return $fields;
    }

    /**
     * Extract fields from a Json Schema.
     *
     * @param JsonSchema $jsonSchema
     * @param            $object
     * @param Collection $fields
     * @param int        $level
     * @param JsonField  $parent
     * @param string     $prefix
     *
     * @return array
     */
    private function _initializeFields($jsonSchema, $object, $fields, $level = 0, $parent = null, string $prefix = ''): array
    {
        $object = $object instanceof stdClass ? $object : json_decode($object);

        $flat = [];
        $separator = '_';
        $requiredFields = [];
        if (isset($object->required)) {
            $requiredFields = $object->required;
        }

        if (!isset($object->properties)) {
            $this->logger->info("Missing 'properties' attribute.");

            return $flat;
        }
        foreach ($object->properties as $key => $value) {
            $jsField = new JsonField();
            $jsField->setJsonSchema($jsonSchema);
            $jsField->setName($key);
            if ($prefix) {
                $jsField->setName($prefix.$separator.$key);
            }

            // Search if the Json field is already existing
            $matches = $fields->filter(function ($name) use ($jsField) {
                return $jsField->getName() == $name;
            });
            // Use the existing field
            if ($stillExisting = (count($matches) > 0)) {
                $jsField = $matches->first();
            }

            $jsField->setRequired(in_array($key, $requiredFields));

            if (isset($value->format)) {
                $jsField->setFormat($value->format);
            }
            if (isset($value->pattern)) {
                $jsField->setPattern($value->pattern);
            }
            $jsField->setLevel($level);
            if ($parent) {
                $parent->addJsonField($jsField);
            }
            $this->logger->info("[$level] ".$jsField->getName()." <-'{$parent}', exists: {$stillExisting}, prefix: {$prefix}");

            if (isset($value->type)) {
                $type = $value->type;
                $this->logger->info(" $key is of type: $type");
                $jsField->setType($type);

                if ('array' === $type) {
                    // Complex type: array
                    $this->_initializeFields($jsonSchema, $value->items, $fields, $level + 1, $jsField, $jsField->getName());
                } elseif ('object' === $type) {
                    // Complex type: object
                    $this->_initializeFields($jsonSchema, $value, $fields, $level + 1, $jsField, $jsField->getName());
                }
            } elseif (isset($value->oneOf)) {
                $this->logger->info($key.' is of type oneOf, '.serialize($value->oneOf));
                $jsField->setNullable(true);
                if (count($value->oneOf) > 1) {
                    if (isset($value->oneOf[0]->type)) {
                        $jsField->setType($value->oneOf[0]->type);
                    }
                    if (isset($value->oneOf[0]->format)) {
                        $jsField->setFormat($value->oneOf[0]->format);
                    }
                }
            }
            if (!$stillExisting) {
                $this->logger->info(" creating $jsField...");
                $jsonSchema->addJsonField($jsField);
                $this->em->persist($jsField);
            } else {
                $this->em->merge($jsField);
            }
            $fields[$jsField->getName()] = $jsField;
        }
        $this->logger->info("Level $level, got: ".count($fields).' fields.');

        return $flat;
    }
//
//    /**
//     * @param JsonField[] $jsonFields
//     * @param string      $description
//     *
//     * @return array
//     */
//    public function getJsonFromFields($jsonFields, $description = ''): array
//    {
//        $this->logger->info('Build the Json schema for '.count($jsonFields).' fields');
//
//        $required = [];
//
//        $jsonContent = [
//            'description' => $description,
//            'type' => 'object',
//            'required' => [],
//            'properties' => $this->_jsonFromFields($jsonFields, $required),
//        ];
//        $jsonContent['required'] = $required;
//
//        return $jsonContent;
//    }
//
//    private function _jsonFromFields($jsonFields, &$required, $level = 0): array
//    {
//        $jsonObject = [];
//
//        // Create a string array to configure the JsTree
//        foreach ($jsonFields as $field) {
//            if ($field->getLevel() != $level) {
//                continue;
//            }
//            $this->logger->info("[$level] ->: {$field->getName()}, {$field->getShortName()}, required: '{$field->getRequired()}'");
//
//            if ($field->isRequired()) {
//                $required[] = $field->getShortName();
//            }
//
//            if ('object' === $field->getType()) {
//                $fieldContent = [];
//                $fieldContent['type'] = $field->getType();
//                $required2 = [];
//                $fieldContent['properties'] = $this->_jsonFromFields($field->getJsonFields(), $required2, $level + 1);
//                $fieldContent['required'] = $required2;
//
//                $jsonObject[$field->getShortName()] = $fieldContent;
//            } elseif ('array' === $field->getType()) {
//                $fieldContent = [];
//                $fieldContent['type'] = $field->getType();
//                $required2 = [];
//                $fieldContent['items'] = [
//                    'type' => 'object',
//                    'properties' => $this->_jsonFromFields($field->getJsonFields(), $required2, $level + 1),
//                ];
//                $fieldContent['items']['required'] = $required2;
//
//                $jsonObject[$field->getShortName()] = $fieldContent;
//            } else {
//                $fieldContent = [];
//                $fieldContent['type'] = $field->getType();
//
//                if ($field->getFormat()) {
//                    $fieldContent['format'] = $field->getFormat();
//                }
//                if ($field->getPattern()) {
//                    $fieldContent['pattern'] = $field->getPattern();
//                }
//
//                // Specific case for oneOf fields
//                if ($field->isNullable()) {
//                    $fieldDefinition = $fieldContent;
//
//                    $fieldContent = [];
//                    $fieldContent['oneOf'][] = $fieldDefinition;
//                    $fieldContent['oneOf'][] = ['type' => 'null'];
//                }
//
//                $jsonObject[$field->getShortName()] = $fieldContent;
//            }
//        }
//        $this->logger->info('[$level] parsed '.count($jsonObject).' properties, including '.count($required).' required fields.');
//
//        return $jsonObject;
//    }

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
