<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LoggerInterface;

/**
 * JsonSchema.
 *
 * @ORM\Entity(repositoryClass="App\Repository\JsonSchemaRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class JsonSchema
{
    use IdTrait;
    use TimestampTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="json")
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name = 'New';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\JsonField", mappedBy="jsonSchema", orphanRemoval=true)
     */
    private $jsonFields;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->jsonFields = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     *
     * @return JsonSchema
     */
    public function setContent($content): JsonSchema
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return JsonSchema
     */
    public function setName(string $name): JsonSchema
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|JsonField[]
     */
    public function getJsonFields(): Collection
    {
        return $this->jsonFields;
    }

    public function addJsonField(JsonField $jsonField): self
    {
        if (!$this->jsonFields->contains($jsonField)) {
            $this->jsonFields[] = $jsonField;
            $jsonField->setJsonSchema($this);
        }

        return $this;
    }

    public function removeJsonField(JsonField $jsonField): self
    {
        if ($this->jsonFields->contains($jsonField)) {
            $this->jsonFields->removeElement($jsonField);
            // set the owning side to null (unless already changed)
            if ($jsonField->getJsonSchema() === $this) {
                $jsonField->setJsonSchema(null);
            }
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getJsonFromFields(): array
    {
        $this->logger->info('Build the Json schema for '. $this->getName() .' fields');

        $required = [];

        $jsonContent = [
            'description' => $this->getName(),
            'type' => 'object',
            'required' => [],
            'properties' => $this->_jsonFromFields($required),
        ];
        $jsonContent['required'] = $required;

        return $jsonContent;
    }

    private function _jsonFromFields(&$required, $level = 0): array
    {
        $jsonObject = [];

        // Create a string array to configure the JsTree
        foreach ($this->getJsonFields() as $field) {
            if ($field->getLevel() != $level) {
                continue;
            }
            $this->logger->info("[$level] ->: {$field->getName()}, {$field->getShortName()}, required: '{$field->getRequired()}'");

            if ($field->isRequired()) {
                $required[] = $field->getShortName();
            }

            if ('object' === $field->getType()) {
                $fieldContent = [];
                $fieldContent['type'] = $field->getType();
                $required2 = [];
                $fieldContent['properties'] = $this->_jsonFromFields($field->getJsonFields(), $required2, $level + 1);
                $fieldContent['required'] = $required2;

                $jsonObject[$field->getShortName()] = $fieldContent;
            } elseif ('array' === $field->getType()) {
                $fieldContent = [];
                $fieldContent['type'] = $field->getType();
                $required2 = [];
                $fieldContent['items'] = [
                    'type' => 'object',
                    'properties' => $this->_jsonFromFields($field->getJsonFields(), $required2, $level + 1),
                ];
                $fieldContent['items']['required'] = $required2;

                $jsonObject[$field->getShortName()] = $fieldContent;
            } else {
                $fieldContent = [];
                $fieldContent['type'] = $field->getType();

                if ($field->getFormat()) {
                    $fieldContent['format'] = $field->getFormat();
                }
                if ($field->getPattern()) {
                    $fieldContent['pattern'] = $field->getPattern();
                }

                // Specific case for oneOf fields
                if ($field->isNullable()) {
                    $fieldDefinition = $fieldContent;

                    $fieldContent = [];
                    $fieldContent['oneOf'][] = $fieldDefinition;
                    $fieldContent['oneOf'][] = ['type' => 'null'];
                }

                $jsonObject[$field->getShortName()] = $fieldContent;
            }
        }
        $this->logger->info('[$level] parsed '.count($jsonObject).' properties, including '.count($required).' required fields.');

        return $jsonObject;
    }
}
