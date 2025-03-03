<?php

namespace App\Entity;

use App\Enum\ScheduleStatus;
use App\Repository\ScheduleWorkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleWorkRepository::class)]
class ScheduleWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(enumType: ScheduleStatus::class, options: ["default" => "Available"])]
    private ScheduleStatus $status = ScheduleStatus::AVAILABLE;

    #[ORM\ManyToOne(inversedBy: 'scheduleWorks')]
    private ?User $doctor = null;

    #[ORM\Column]
    private array $timeSlots = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ScheduleStatus
    {
        return $this->status;
    }

    public function setStatus(ScheduleStatus|string $status): self
    {
        $this->status = is_string($status) ? ScheduleStatus::from($status) : $status;
        return $this;
    }


    // public function setStatus(ScheduleStatus $status): self
    // {
    //     $this->status = $status;
    //     return $this;
    // }

    public function getDoctor(): ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getTimeSlots(): array
    {
        return $this->timeSlots;
    }

    public function setTimeSlots(array $timeSlots): static
    {
        $this->timeSlots = $timeSlots;

        return $this;
    }
}
