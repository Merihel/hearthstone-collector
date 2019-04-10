<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\SerializedName as SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TradeRepository")
 */
class Trade
{

    public function __construct(User $userAsker, User $userAsked, Card $cardAsker, Card $cardAsked)
    {
        $this->setUserAsker($userAsker);
        $this->setUserAsked($userAsked);
        $this->setStatus("PENDING"); //3 status available : PENDING, OK and OUT
        $this->setCardAsker($cardAsker);
        $this->setCardAsked($cardAsked);
        $this->setIsAskerOk(true);
        $this->setIsAskedOk(false);
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @SerializedName("id")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @SerializedName("userAsker")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userAsker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @SerializedName("userAsked")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userAsked;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("status")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Card")
     * @SerializedName("cardAsker")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cardAsker; // LA CARTE DE CELUI QUI DEMANDE = CELLE QU'IL PROPOSE

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Card")
     * @SerializedName("cardAsked")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cardAsked; // LA CARTE VOULUE, CELLE DE L'AUTRE

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @SerializedName("isAskerOk")
     */
    private $isAskerOk;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @SerializedName("isAskedOk")
     */
    private $isAskedOk;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserAsker(): ?User
    {
        return $this->userAsker;
    }

    public function setUserAsker(?User $userAsker): self
    {
        $this->userAsker = $userAsker;

        return $this;
    }

    public function getUserAsked(): ?User
    {
        return $this->userAsked;
    }

    public function setUserAsked(?User $userAsked): self
    {
        $this->userAsked = $userAsked;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCardAsker(): ?Card
    {
        return $this->cardAsker;
    }

    public function setCardAsker(?Card $cardAsker): self
    {
        $this->cardAsker = $cardAsker;

        return $this;
    }

    public function getCardAsked(): ?Card
    {
        return $this->cardAsked;
    }

    public function setCardAsked(?Card $cardAsked): self
    {
        $this->cardAsked = $cardAsked;

        return $this;
    }

    public function getIsAskerOk(): ?bool
    {
        return $this->isAskerOk;
    }

    public function setIsAskerOk(?bool $isAskerOk): self
    {
        $this->isAskerOk = $isAskerOk;

        return $this;
    }

    public function getIsAskedOk(): ?bool
    {
        return $this->isAskedOk;
    }

    public function setIsAskedOk(?bool $isAskedOk): self
    {
        $this->isAskedOk = $isAskedOk;

        return $this;
    }
}
