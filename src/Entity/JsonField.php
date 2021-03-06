<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="json_field",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="field_idx", columns={"json_schema_id","name"})
 *     }))
 * @ORM\Entity(repositoryClass="App\Repository\JsonFieldRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class JsonField
{
    use IdTrait;
    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $level = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $required = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $nullable = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $format = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pattern = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\JsonField", inversedBy="jsonFields",cascade={"persist"})
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\JsonField", mappedBy="parent",cascade={"remove"})
     */
    private $jsonFields;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\JsonSchema", inversedBy="jsonFields",cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $jsonSchema;

    public $group = '';

    public function __construct()
    {
        $this->jsonFields = new ArrayCollection();
    }

    /**
     * When dumped as a string, returns a string formed with: schema name and field name.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function isNullable(): ?bool
    {
        return $this->nullable;
    }

    public function getNullable(): ?bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * This method removes the parent name part in the unique name of the field
     * A field PostCode included in a clientAddress has a name: clientAddress_PostCode
     * This method will return: PostCode.
     *
     * @return string|null
     */
    public function getShortName(): ?string
    {
        if ($this->getParent()) {
            return str_replace($this->getParent()->getName().'_', '', $this->name);
        }

        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    public function setPattern(?string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getJsonFields(): Collection
    {
        return $this->jsonFields;
    }

    public function addJsonField(self $jsonField): self
    {
        if (!$this->jsonFields->contains($jsonField)) {
            $this->jsonFields[] = $jsonField;
            $jsonField->setParent($this);
        }

        return $this;
    }

    public function removeJsonField(self $jsonField): self
    {
        if ($this->jsonFields->contains($jsonField)) {
            $this->jsonFields->removeElement($jsonField);
            // set the owning side to null (unless already changed)
            if ($jsonField->getParent() === $this) {
                $jsonField->setParent(null);
            }
        }

        return $this;
    }

    public function getJsonSchema(): ?JsonSchema
    {
        return $this->jsonSchema;
    }

    public function setJsonSchema(?JsonSchema $jsonSchema): self
    {
        $this->jsonSchema = $jsonSchema;

        return $this;
    }

    public function getGroup(): ?string
    {
        if (count($this->getJsonFields()) > 0) {
            // I am a group leader for my children
            return $this->getName();
        } elseif ($this->getParent()) {
            // Else it is my parent that is leading me
            return $this->getParent()->getName();
        }

        return $this->group;
    }
}
