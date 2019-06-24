<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LoggerInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\JsonFieldRepository")
 */
class JsonField
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\JsonField", inversedBy="jsonFields")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\JsonField", mappedBy="parent")
     */
    private $jsonFields;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\JsonSchema", inversedBy="jsonFields")
     * @ORM\JoinColumn(nullable=false)
     */
    private $jsonSchema;

    public function __construct()
    {
        $this->jsonFields = new ArrayCollection();
    }

    public function __toString() {
        return $this->name . "[" . $this->id . "]";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
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

    public function getForTemplate()
    {
        $my_clone = clone $this;

        foreach (get_object_vars($my_clone) as $prop => $value) {
            if (in_array($prop, ['parent'])) {
                if ($this->getParent()) {
                    $parent = $this->getParent()->getId();
                } else {
                    $parent = '#';
                }
                $my_clone->parent = $parent;
            } elseif (in_array($prop, ['jsonFields', 'jsonSchema'])) {
                $my_clone->jsonSchema = '';
                $my_clone->jsonFields = '';
            }
        }

        return $my_clone;
    }

}
