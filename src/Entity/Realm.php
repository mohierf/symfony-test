<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RealmRepository")
 * @ApiResource()
 */
class Realm
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
    private $realm_name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Realm", inversedBy="realms")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Realm", mappedBy="parent")
     */
    private $realms;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $alias;

    public function __construct()
    {
        $this->realms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealmName(): ?string
    {
        return $this->realm_name;
    }

    public function setRealmName(string $realm_name): self
    {
        $this->realm_name = $realm_name;

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
    public function getRealms(): Collection
    {
        return $this->realms;
    }

    public function addRealm(self $realm): self
    {
        if (!$this->realms->contains($realm)) {
            $this->realms[] = $realm;
            $realm->setParent($this);
        }

        return $this;
    }

    public function removeRealm(self $realm): self
    {
        if ($this->realms->contains($realm)) {
            $this->realms->removeElement($realm);
            // set the owning side to null (unless already changed)
            if ($realm->getParent() === $this) {
                $realm->setParent(null);
            }
        }

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}
