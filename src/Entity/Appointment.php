<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?user $patient = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?user $doctor = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $appointmentDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $appointmentTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $forWho = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patientFullname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $patientDateOfBirth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patientPhoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patientAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patientGender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patientEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?user
    {
        return $this->patient;
    }

    public function setPatient(?user $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getDoctor(): ?user
    {
        return $this->doctor;
    }

    public function setDoctor(?user $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getAppointmentTime(): ?string
    {
        return $this->appointmentTime;
    }

    public function setAppointmentTime(?string $appointmentTime): static
    {
        $this->appointmentTime = $appointmentTime;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getForWho(): ?string
    {
        return $this->forWho;
    }

    public function setForWho(?string $forWho): static
    {
        $this->forWho = $forWho;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getPatientFullname(): ?string
    {
        return $this->patientFullname;
    }

    public function setPatientFullname(?string $patientFullname): static
    {
        $this->patientFullname = $patientFullname;

        return $this;
    }

    public function getPatientDateOfBirth(): ?\DateTimeInterface
    {
        return $this->patientDateOfBirth;
    }

    public function setPatientDateOfBirth(?\DateTimeInterface $patientDateOfBirth): static
    {
        $this->patientDateOfBirth = $patientDateOfBirth;

        return $this;
    }

    public function getPatientPhoneNumber(): ?string
    {
        return $this->patientPhoneNumber;
    }

    public function setPatientPhoneNumber(?string $patientPhoneNumber): static
    {
        $this->patientPhoneNumber = $patientPhoneNumber;

        return $this;
    }

    public function getPatientAddress(): ?string
    {
        return $this->patientAddress;
    }

    public function setPatientAddress(?string $patientAddress): static
    {
        $this->patientAddress = $patientAddress;

        return $this;
    }

    public function getPatientGender(): ?string
    {
        return $this->patientGender;
    }

    public function setPatientGender(?string $patientGender): static
    {
        $this->patientGender = $patientGender;

        return $this;
    }

    public function getPatientEmail(): ?string
    {
        return $this->patientEmail;
    }

    public function setPatientEmail(?string $patientEmail): static
    {
        $this->patientEmail = $patientEmail;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }
}
