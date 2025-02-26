<?php

namespace App\Entity;

use App\Repository\MomoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MomoRepository::class)]
class Momo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $customerId = null;

    #[ORM\Column(nullable: true)]
    private ?int $momoStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $link_data = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getMomoStatus(): ?int
    {
        return $this->momoStatus;
    }

    public function setMomoStatus(?int $momoStatus): static
    {
        $this->momoStatus = $momoStatus;

        return $this;
    }

    public function getLinkData(): ?string
    {
        return $this->link_data;
    }

    public function setLinkData(?string $link_data): static
    {
        $this->link_data = $link_data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
