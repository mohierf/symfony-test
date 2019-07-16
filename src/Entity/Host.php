<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HostRepository")
 * @ApiResource()
 */
class Host
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
    private $host_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $display_name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $register = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address = '';

    /**
     * @ORM\Column(type="integer")
     */
    private $check_interval = 1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Command", inversedBy="hosts")
     */
    private $check_command;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHostName(): ?string
    {
        return $this->host_name;
    }

    public function setHostName(string $host_name): self
    {
        $this->host_name = $host_name;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->display_name;
    }

    public function setDisplayName(string $display_name): self
    {
        $this->display_name = $display_name;

        return $this;
    }

    public function getRegister(): ?bool
    {
        return $this->register;
    }

    public function setRegister(bool $register): self
    {
        $this->register = $register;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCheckInterval(): ?int
    {
        return $this->check_interval;
    }

    public function setCheckInterval(int $check_interval): self
    {
        $this->check_interval = $check_interval;

        return $this;
    }

    public function getCheckCommand(): ?Command
    {
        return $this->check_command;
    }

    public function setCheckCommand(?Command $check_command): self
    {
        $this->check_command = $check_command;

        return $this;
    }
}
