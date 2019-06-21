<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Sfk\StandardBundle\Entity\Traits\CreationTrait;
use Sfk\StandardBundle\Entity\Traits\LastUpdateTrait;

/**
 * Trait TimestampTrait.
 */
trait TimestampTrait
{
    /**
     * Creation Datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="creation", type="datetime")
     */
    private $creation;

    /**
     * LastUpdate Datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="last_update", type="datetime")
     */
    private $lastUpdate;

    /**
     * Set creation datetime.
     *
     * @param \DateTime $creation
     *
     * @return static
     */
    public function setCreation(\DateTime $creation)
    {
        $this->creation = $creation;

        return $this;
    }

    /**
     * Get creation datetime.
     *
     * @return \DateTime
     */
    public function getCreation()
    {
        return $this->creation;
    }

    /**
     * Set LastUpdate datetime.
     *
     * @param \DateTime $lastUpdate
     *
     * @return static
     */
    public function setLastUpdate(\DateTime $lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * Get LastUpdate datetime.
     *
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function _setLastUpdateDate(): void
    {
        $this->lastUpdate = new \DateTime();
    }

    /**
     * @ORM\PrePersist()
     */
    public function _setCreationDate(): void
    {
        $this->creation = new \DateTime();
    }
}
