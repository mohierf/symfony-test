<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommandRepository")
 * @ApiResource()
 */
class Command
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
    private $command_name;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $command_line;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Host", mappedBy="check_command")
     */
    private $hosts;

    public function __construct()
    {
        $this->hosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommandName(): ?string
    {
        return $this->command_name;
    }

    public function setCommandName(string $command_name): self
    {
        $this->command_name = $command_name;

        return $this;
    }

    public function getCommandLine(): ?string
    {
        return $this->command_line;
    }

    public function setCommandLine(string $command_line): self
    {
        $this->command_line = $command_line;

        return $this;
    }

    /**
     * @return Collection|Host[]
     */
    public function getHosts(): Collection
    {
        return $this->hosts;
    }

    public function addHost(Host $host): self
    {
        if (!$this->hosts->contains($host)) {
            $this->hosts[] = $host;
            $host->setCheckCommand($this);
        }

        return $this;
    }

    public function removeHost(Host $host): self
    {
        if ($this->hosts->contains($host)) {
            $this->hosts->removeElement($host);
            // set the owning side to null (unless already changed)
            if ($host->getCheckCommand() === $this) {
                $host->setCheckCommand(null);
            }
        }

        return $this;
    }
}
