<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
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
     * @SerializedName("id")
     * @Serializer\Expose
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
    
    /**
     * @ORM\Column(type="string", length=255)
     * @SerializedName("name")
     */
    private $name;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("cardSet")
     */
    private $cardSet;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("type")
     */
    private $type;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("faction")
     */
    private $faction;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("rarity")
     */
    private $rarity;
    
    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     * @SerializedName("text")
     */
    private $text;
    
    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     * @SerializedName("flavor")
     */
    private $flavor;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("img")
     */
    private $img;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SerializedName("imgGold")
     */
    private $imgGold;
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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
    
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    
    public function getCardSet(): ?string
    {
        return $this->cardSet;
    }

    public function setCardSet(string $cardSet): self
    {
        $this->cardSet = $cardSet;

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
    
    public function getFaction(): ?string
    {
        return $this->faction;
    }

    public function setFaction(string $faction): self
    {
        $this->faction = $faction;

        return $this;
    }
    
    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): self
    {
        $this->rarity = $rarity;

        return $this;
    }
    
    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
    
    public function getFlavor(): ?string
    {
        return $this->flavor;
    }

    public function setFlavor(string $flavor): self
    {
        $this->flavor = $flavor;

        return $this;
    }
    
    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): self
    {
        $this->img = $img;

        return $this;
    }
    
    public function getImgGold(): ?string
    {
        return $this->imgGold;
    }

    public function setImgGold(string $imgGold): self
    {
        $this->imgGold = $imgGold;

        return $this;
    }
}
