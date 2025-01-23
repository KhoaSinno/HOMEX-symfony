<?php

namespace App\Entity;

use App\Repository\SpecialtyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SpecialtyRepository::class)]
class Specialty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Tên không được để trống.')]
    #[Assert\Length(max: 255, maxMessage: 'Tên không được vượt quá 255 ký tự.')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Số lượng phòng không được để trống.')]
    #[Assert\Positive(message: 'Số lượng phòng phải là số dương.')]
    private ?string $clinicNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getClinicNumber(): ?string
    {
        return $this->clinicNumber;
    }

    public function setClinicNumber(?string $clinicNumber): static
    {
        $this->clinicNumber = $clinicNumber;

        return $this;
    }
}
