<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\SerializedName as SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Expose
     */
    private $pseudo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Expose
     * @SerializedName("firstName")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Expose
     * @SerializedName("lastName")
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Expose
     */
    private $mail;

    /**
     * The below length depends on the "algorithm" you use for encoding
     * the password, but this works well with bcrypt.
     *
     * @ORM\Column(type="string", length=64)
     * @Serializer\Expose
     */
    private $password;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     */
    private $coins;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Card")
     * @ORM\JoinTable(name="users_cards",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="card_id", referencedColumnName="id")}
     *      )
     */
    private $cards;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Deck")
     * @ORM\JoinTable(name="users_decks",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="deck_id", referencedColumnName="id")}
     *      )
     */
    private $decks;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Card")
     * @ORM\JoinTable(name="wishlist",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="card_wanted_id", referencedColumnName="id")}
     *      )
     */
    private $wishlist;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Expose
     * @SerializedName("facebookId")
     */
    private $facebookId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Expose
     * @SerializedName("googleId")
     */
    private $googleId;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->wishlist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCoins(): ?int
    {
        return $this->coins;
    }

    public function setCoins(int $coins): self
    {
        $this->coins = $coins;

        return $this;
    }

    /**
     * @return Collection|Card[]
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): self
    {
        if (!$this->cards->contains($card)) {
            $this->cards[] = $card;
        }

        return $this;
    }

    public function removeCard(Card $card): self
    {
        if ($this->cards->contains($card)) {
            $this->cards->removeElement($card);
        }

        return $this;
    }

    /**
     * @return Collection|Deck[]
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    public function addDeck(Deck $deck): self
    {
        if (!$this->decks->contains($deck)) {
            $this->decks[] = $deck;
        }

        return $this;
    }

    public function removeDeck(Deck $deck): self
    {
        if ($this->decks->contains($deck)) {
            $this->decks->removeElement($deck);
        }

        return $this;
    }

    /**
     * @return Collection|Card[]
     */
    public function getWishlist(): Collection
    {
        return $this->wishlist;
    }

    public function addWishlist(Card $wishlist): self
    {
        if (!$this->wishlist->contains($wishlist)) {
            $this->wishlist[] = $wishlist;
        }

        return $this;
    }

    public function removeWishlist(Card $wishlist): self
    {
        if ($this->wishlist->contains($wishlist)) {
            $this->wishlist->removeElement($wishlist);
        }

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): self
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function toString(): ?string
    {
        return "id: ".$this->id.",\n
        pseudo: ".$this->pseudo.",\n
        mail: ".$this->mail.",\n
        coins: ".$this->coins.",\n
        googleId: ".$this->googleId.",\n
        facebookId: ".$this->facebookId;
    }
}
