<?php

namespace App\Entity;

use App\Util\Util;
use App\Utils;
use App\Repository\Emv3DSRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Emv3DSRepository::class)]
class Emv3DS
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $protocolVersion = null;

    #[ORM\Column(length: 255)]
    private ?string $threeDServerTransID = null;

    #[ORM\Column(length: 255)]
    private ?string $threeDSInfo = null;

    #[ORM\Column( length: 255, nullable: true )]
    private ?string $threeDSMethodURL = null;

    /**
     * se creará antes de enviar el parámetro y se utilizará para
     * validar que se haya hecho el post antes de los 10 s. de no ser
     * así se enviará el parámetro theeDSCompInd N
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $threeDSCompInd = 'N';
    


    public function getThreeDSMethodData( string $notificationUrl ): string
    {

        return Util::base64url_encode( json_encode( array( "threeDSServerTransID" => $this->getThreeDServerTransID(),
            "threeDSMethodNotificationURL" => $notificationUrl ), JSON_UNESCAPED_SLASHES ) );

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProtocolVersion(): ?string
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(string $protocolVersion): static
    {
        $this->protocolVersion = $protocolVersion;

        return $this;
    }

    public function getThreeDServerTransID(): ?string
    {
        return $this->threeDServerTransID;
    }

    public function setThreeDServerTransID(string $threeDServerTransID): static
    {
        $this->threeDServerTransID = $threeDServerTransID;

        return $this;
    }

    public function getThreeDSInfo(): ?string
    {
        return $this->threeDSInfo;
    }

    public function setThreeDSInfo(string $threeDSInfo): static
    {
        $this->threeDSInfo = $threeDSInfo;

        return $this;
    }

    public function getThreeDSMethodURL(): ?string
    {
        return $this->threeDSMethodURL;
    }

    public function setThreeDSMethodURL(string $threeDSMethodURL): static
    {
        $this->threeDSMethodURL = $threeDSMethodURL;

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

    public function getThreeDSCompInd(): ?string
    {
        return $this->threeDSCompInd;
    }

    public function setThreeDSCompInd(string $threeDSCompInd): static
    {
        $this->threeDSCompInd = $threeDSCompInd;

        return $this;
    }
}
