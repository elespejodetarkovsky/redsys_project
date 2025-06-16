<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private ?string $pan = null;

    #[ORM\Column(length: 5)]
    private ?string $expDate = null;

    #[ORM\Column(length: 3)]
    private ?string $cvv = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column( length: 255, nullable: true )]
    private ?string $exceptSCA = null;

    /**
     * Informa si la tarjeta está enrolada en PSD2 (Payment Service Directive 2), y con una «Y» como respuesta, quiere decir que sí.
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $cardPSD2 = 'Y';

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany( targetEntity: Transaction::class, mappedBy: 'card' )]
    private Collection $transactions;



    public function __construct()
    {
        $this->createdAt    = new \DateTimeImmutable();
        //$this->order        = strtotime('now');
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPan(): ?string
    {
        return $this->pan;
    }

    public function setPan(string $pan): static
    {
        $this->pan = $pan;

        return $this;
    }

    public function getExpDate(): ?string
    {
        return $this->expDate;
    }

    public function setExpDate( string $expDate ): static
    {
        $this->expDate = $expDate;

        return $this;
    }

    public function getCvv(): ?string
    {
        return $this->cvv;
    }

    public function setCvv(string $cvv): static
    {
        $this->cvv = $cvv;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExceptSCA(): ?string
    {
        return $this->exceptSCA;
    }

    public function setExceptSCA(string $exceptSCA): static
    {
        $this->exceptSCA = $exceptSCA;

        return $this;
    }

    public function getCardPSD2(): ?string
    {
        return $this->cardPSD2;
    }

    public function setCardPSD2(string $cardPSD2): static
    {
        $this->cardPSD2 = $cardPSD2;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCard($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCard() === $this) {
                $transaction->setCard(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->pan;
    }
}
