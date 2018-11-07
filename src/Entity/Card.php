<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\SerializedName as SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CardRepository")
 */
class Card
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @SerializedName("hsId")
     */
    private $hsId;

    /**
     * @ORM\Column(type="integer")
     */
    private $cost;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHsId(): ?string
    {
        return $this->hsId;
    }

    public function setHsId(string $hsId): self
    {
        $this->hsId = $hsId;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): self
    {
        $this->cost = $cost;

        return $this;
    }
}
