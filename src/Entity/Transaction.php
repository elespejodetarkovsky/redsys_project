<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $transOrder = null;

    #[ORM\Column(length: 255)]
    private ?string $transType = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

    #[ORM\Column(length: 255)]
    private ?string $amount = null;
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Emv3DS $emv3DS = null;

    #[ORM\ManyToOne( cascade: ['persist'], inversedBy: 'transactions') ]
    private ?Card $card = null;

    #[ORM\Column]
    private ?bool $isPaid = false;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): Transaction
    {
        $this->amount = $amount;
        return $this;
    }

    public function getTransOrder(): ?string
    {
        return $this->transOrder;
    }

    public function setTransOrder(string $transOrder): static
    {
        $this->transOrder = $transOrder;

        return $this;
    }

    public function getTransType(): ?string
    {
        return $this->transType;
    }

    public function setTransType(string $transType): static
    {
        $this->transType = $transType;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getEmv3DS(): ?Emv3DS
    {
        return $this->emv3DS;
    }

    public function setEmv3DS(?Emv3DS $emv3DS): static
    {
        $this->emv3DS = $emv3DS;

        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public static function findAndNoPaisCriteria( string $order, bool $isPaid ): Criteria
    {

        //dd(Criteria::expr()->eq( 'transOrder', 'sflksdñflk' ), Criteria::expr()->eq( 'isPaid', true ));

        $criteria = Criteria::create()->where( Criteria::expr()->eq( 'transOrder', $order ))
            ->andWhere( Criteria::expr()->eq( 'isPaid', $isPaid ));

        return $criteria;
    }
    
}
