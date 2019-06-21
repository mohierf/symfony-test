<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimestampTrait;
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
    protected $name = '';

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
}
