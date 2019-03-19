<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\SerializedName as SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FriendshipRepository")
 */
class Friendship
{
    public function __construct(User $user1, User $user2, bool $isAccepted, int $whoDemanding)
    {
        $this->setUser1($user1);
        $this->setUser2($user2);
        $this->setIsAccepted($isAccepted);
        $this->setWhoDemanding($whoDemanding);
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @MaxDepth(1)
     */
    private $user1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @MaxDepth(1)
     */
    private $user2;

    /**
     * @ORM\Column(type="boolean")
     * @SerializedName("isAccepted")
     */
    private $isAccepted;

    /**
     * @ORM\Column(type="integer")
     * @SerializedName("whoDemanding")
     */
    private $whoDemanding;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser1(): ?User
    {
        return $this->user1;
    }

    public function setUser1(?User $user1): self
    {
        $this->user1 = $user1;

        return $this;
    }

    public function getUser2(): ?User
    {
        return $this->user2;
    }

    public function setUser2(?User $user2): self
    {
        $this->user2 = $user2;

        return $this;
    }

    public function getIsAccepted(): ?bool
    {
        return $this->isAccepted;
    }

    public function setIsAccepted(bool $isAccepted): self
    {
        $this->isAccepted = $isAccepted;

        return $this;
    }

    public function getWhoDemanding(): ?int
    {
        return $this->whoDemanding;
    }

    public function setWhoDemanding(int $whoDemanding): self
    {
        $this->whoDemanding = $whoDemanding;

        return $this;
    }
}
