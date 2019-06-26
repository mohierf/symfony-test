<?php

namespace App\Services;

use App\Entity\JsonField;
use App\Entity\JsonSchema;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param LoggerInterface $logger
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
     * @param string     $json   JSON string to validate
     * @param JsonSchema $schema
     *
     * @return bool
     */
    public function validate($json, JsonSchema $schema): bool
    {
        $objectToValidate = json_decode($json);

        try {
            $validator = new Validator();


            $validator->validate(
                $objectToValidate,
                (object) ['$ref' => 'http://json-schema.org/draft-06/schema#'],
                Constraint::CHECK_MODE_VALIDATE_SCHEMA);
//
//            $validator->validate($objectToValidate,
//                json_decode($schema->getContent()), Constraint::CHECK_MODE_VALIDATE_SCHEMA);
        } catch (\ErrorException $exp) {
            throw new InvalidSchemaException(sprintf('Using the schema [%s], exception: %s', $schema->getName(), $exp->getMessage()), JsonResponse::HTTP_BAD_REQUEST);
        }

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
    public function getFieldsFromSchema(JsonSchema $jsonSchema): array
    {
        $fields = [];
        $this->logger->info("getFieldsFromSchema - Get the Json fields from: " . $jsonSchema->getName());
        $this->_initializeFields($jsonSchema, $jsonSchema->getContent(), $fields);
        $this->logger->info("getFieldsFromSchema - Got: " . count($fields) . " fields.");

        return $fields;
    }

    /**
     * Extract fields from a Json Schema.
     *
     * @param JsonSchema $jsonSchema
     * @param            $object
     * @param            $fields
     * @param int        $level
     * @param JsonField  $parent
     * @param string     $prefix
     *
     * @return array
     */
    private function _initializeFields($jsonSchema, $object, &$fields, $level = 0, $parent = null, string $prefix = ''): array
    {
        $this->logger->info("_initializeFields: ". gettype($object));
        $object = $object instanceof stdClass ? $object : json_decode($object);
        $this->logger->info("_initializeFields: ". gettype($object));

        $jsonFieldRepository = $this->em->getRepository(JsonField::class);

        $flat = [];
        $separator = '_';

        foreach ($object->properties as $key => $value) {
            $jsField = new JsonField();
            $jsField->setJsonSchema($jsonSchema);
            $jsField->setName($key);
            if ($prefix) {
                $jsField->setName($prefix . $separator . $key);
            }
            $stillExisting = false;
            if($currentField = $jsonFieldRepository->findOneBy(['name' => $jsField->getName(), 'jsonSchema' => $jsonSchema->getId()])) {
                $stillExisting = true;
            }
            if (isset($value->format)) {
                $jsField->setFormat($value->format);
            }
            if (isset($value->pattern)) {
                $jsField->setPattern($value->pattern);
            }
            $jsField->setLevel($level);
            if ($parent) {
                $this->logger->info("- set parent as: " . $parent);
                $parent->addJsonField($jsField);
            }
            $this->logger->info($jsField->getName() . "/{$level} <-'{$parent}', exists: {$stillExisting}, {$currentField}, prefix: {$prefix}");

            if (isset($value->type)) {
                $type = $value->type;
                $jsField->setType($type);

                if ('array' === $type) {
                    $this->logger->info(" is of type: " . $value->type);
                    if (! $stillExisting) {
                        $this->em->persist($jsField);
                    } else {
                        $this->em->flush();
                    }

                    // Complex type: array
                    $this->_initializeFields($jsonSchema, $value->items, $fields, $level + 1, $jsField, $jsField->getName());
                } elseif ('object' === $type) {
                    $this->logger->info(" is of type: " . $value->type);
                    if (! $stillExisting) {
                        $this->em->persist($jsField);
                    } else {
                        $this->em->flush();
                    }

                    if ('#' === $parent) {
                        // A root element
                        $this->_initializeFields($jsonSchema, $value, $fields, $level + 1, $jsField, $jsField->getName());
                    } else {
                        $this->_initializeFields($jsonSchema, $value, $fields, $level + 1, $jsField, $jsField->getName());
                    }
                    $prefix = '';
                }
            } elseif (isset($value->oneOf)) {
                $this->logger->info($key . " is of type oneOf, " . serialize($value->oneOf));
                $jsField->setNullable(true);
                if (count($value->oneOf) > 1) {
                    if (isset($value->oneOf[0]->type)) {
                        $jsField->setType($value->oneOf[0]->type);
                    }
                    if (isset($value->oneOf[0]->format)) {
                        $jsField->setType($value->oneOf[0]->format);
                    }
                    $this->logger->info($key . ", type: oneOf, " . serialize($jsField));
                }
            }

            if (! $stillExisting) {
                $this->logger->info(" creating $jsField...");
                $this->em->persist($jsField);
            } else {
                $this->logger->info(" updating $jsField...");
                $this->em->flush();
            }
            $fields[$jsField->getName()] = $jsField;
        }
        $this->logger->info("Level $level, got: " . count($fields) . " fields.");
        $this->em->flush();

        return $flat;
    }

    /**
     * @param array $jsonFields
     *
     * @param string $description
     * @return array
     */
    public function getJsonFromFields(array $jsonFields, string $description = ""): array
    {
        $jsonContent = [];
        $this->logger->warning("Build the schema from ". count($jsonFields) . " fields");

        $jsonContent["description"] = $description;
        $jsonContent["type"] = "object";
        $jsonContent["required"] = [];
        $required = [];
        $jsonContent["properties"] = $this->_jsonFromFields($jsonFields, $required);
        $jsonContent["required"] = $required;

        $this->logger->warning("Got: " . count($jsonContent) . " rows.");
        $this->logger->warning("Got: " . serialize($required) . " required.");

        return $jsonContent;
    }

    private function _jsonFromFields($jsonFields, &$required, $level = 0): array
    {
        $flat = [];
//        $this->logger->warning("*$level* ->: " . count($jsonFields));

        // Create a string array to configure the JsTree
        foreach ($jsonFields as $field) {
            if ($field->getLevel() != $level) {
                continue;
            }
            $this->logger->warning("*$level* ->: {$field->getName()}, {$field->getShortName()}, '{$field->getRequired()}''");

            if ($field->isRequired()) {
                $required[] = $field->getShortName();
            }

            $newField = [];
            if ($field->getType() === 'object') {
                $fieldContent = [];
                $fieldContent["type"] = $field->getType();
                $fieldContent["properties"] = $this->_jsonFromFields($field->getJsonFields(), $fieldContent["required"], $level + 1);

                $newField[$field->getShortName()] = $fieldContent;
            } elseif ($field->getType() === 'array') {
                $fieldContent = [];
                $fieldContent["type"] = $field->getType();
                $fieldContent["items"] = [
                    "type" => "object",
                    "properties" => $this->_jsonFromFields($field->getJsonFields(), $required2, $level + 1)
                ];
//                $fieldContent["items"] = $this->_jsonFromFields(
//                    $field->getJsonFields(),
//                    $required2, $level + 1);

                $newField[$field->getShortName()] = $fieldContent;
            } else {
                $fieldContent = [];
                $fieldContent["type"] = $field->getType();

                if ($field->getFormat()) {
                    $fieldContent["format"] = $field->getFormat();
                }
                if ($field->getPattern()) {
                    $fieldContent["pattern"] = $field->getPattern();
                }

                // Specific case for oneOf fields
                if ($field->isNullable()) {
                    $fieldDefinition = $fieldContent;

                    $fieldContent = [];
                    $fieldContent["oneOf"][] = [ "type" => "null" ];
                    $fieldContent["oneOf"][] = $fieldDefinition;
                }

                $newField[$field->getShortName()] = $fieldContent;
            }

            $jsonContent[] = $newField;
            $flat[] = $newField;

//            $this->logger->warning("*** ->: " . json_encode($newField));
//            $this->logger->warning("*** ->: " . json_encode($flat));
        }

        $this->logger->warning("Got: " . count($flat) . " fields.");
        $this->logger->warning("Required: " . count($required) . " required fields.");

        return $flat;
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
