<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\OneToMany(targetEntity="App\Entity\JsonField", mappedBy="jsonSchema", orphanRemoval=true, cascade={"persist"})
     */
    private $jsonFields;

    public function __construct()
    {
        $this->jsonFields = new ArrayCollection();
    }

    public function __toString() {
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
}
