<?php

namespace App\Dto;

use App\Entity\Card;
use App\Entity\Emv3DS;

class TransactionDto
{

    //TODO colocar los assert
    private ?string $transOrder = null;
    private ?string $transType = null;
    private ?string $currency = null;
    private ?string $amount = null;
    private ?Emv3DS $emv3DS = null;
    private ?Card $card = null;


    //GETTERS AND SETTERS

    public function getTransOrder(): ?string
    {
        return $this->transOrder;
    }

    public function setTransOrder(?string $transOrder): TransactionDto
    {
        $this->transOrder = $transOrder;
        return $this;
    }

    public function getTransType(): ?string
    {
        return $this->transType;
    }

    public function setTransType(?string $transType): TransactionDto
    {
        $this->transType = $transType;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): TransactionDto
    {
        $this->currency = $currency;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): TransactionDto
    {
        $this->amount = $amount;
        return $this;
    }

    public function getEmv3DS(): ?Emv3DS
    {
        return $this->emv3DS;
    }

    public function setEmv3DS(?Emv3DS $emv3DS): TransactionDto
    {
        $this->emv3DS = $emv3DS;
        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): TransactionDto
    {
        $this->card = $card;
        return $this;
    }

    public function getIsPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(?bool $isPaid): TransactionDto
    {
        $this->isPaid = $isPaid;
        return $this;
    }



}