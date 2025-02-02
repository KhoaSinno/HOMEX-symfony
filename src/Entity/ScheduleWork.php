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

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $timeStart = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $timeEnd = null;

    #[ORM\Column]
    private ?int $maxPatient = null;

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

    public function getTimeStart(): ?\DateTimeInterface
    {
        return $this->timeStart;
    }

    public function setTimeStart(\DateTimeInterface $timeStart): static
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getTimeEnd(): ?\DateTimeInterface
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(\DateTimeInterface $timeEnd): static
    {
        $this->timeEnd = $timeEnd;

        return $this;
    }

    public function getMaxPatient(): ?int
    {
        return $this->maxPatient;
    }

    public function setMaxPatient(int $maxPatient): static
    {
        $this->maxPatient = $maxPatient;

        return $this;
    }

    public function getStatus(): ScheduleStatus
    {
        return $this->status;
    }

    public function setStatus(ScheduleStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

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
