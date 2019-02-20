<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeckRepository")
 */
class Deck
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
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $userId;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Card")
     * @ORM\JoinTable(name="decks_cards",
     *      joinColumns={@ORM\JoinColumn(name="deck_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="card_id", referencedColumnName="id")}
     *      )
     */
    private $cardsList;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }


    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|Card[]
     */
    public function getCardsList(): Collection
    {
        return $this->cardsList;
    }

    public function addCardsList(Card $cardsList): self
    {
        if (!$this->cardsList->contains($cardsList)) {
            $this->cardsList[] = $cardsList;
        }

        return $this;
    }

    public function removeCardsList(Card $cardsList): self
    {
        if ($this->cardsList->contains($cardsList)) {
            $this->cardsList->removeElement($cardsList);
        }

        return $this;
    }
}
