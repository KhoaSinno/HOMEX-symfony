<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Email không được để trống')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    // #[ORM\Column]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Họ tên không được để trống')]
    private ?string $fullname = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Specialty $specialty = null;

    /**
     * @var Collection<int, ScheduleWork>
     */
    #[ORM\OneToMany(targetEntity: ScheduleWork::class, mappedBy: 'doctor')]
    private Collection $scheduleWorks;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Số điện thoại không được để trống')]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(nullable: true)]
    private ?float $consultationFee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'patient')]
    private Collection $appointments;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'doctor')]
    private Collection $doctorAppointments; // Các cuộc hẹn mà User là bác sĩ


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qualification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    /**
     * @var Collection<int, Momo>
     */
    #[ORM\OneToMany(targetEntity: Momo::class, mappedBy: 'customer')]
    private Collection $momoTransactions;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Review::class, orphanRemoval: true)]
    private $reviews;

    private ?float $averageRating = null;

    public function __construct()
    {
        $this->scheduleWorks = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->doctorAppointments = new ArrayCollection();
        $this->momoTransactions = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
        // return null;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): static
    {
        $this->specialty = $specialty;

        return $this;
    }

    /**
     * @return Collection<int, ScheduleWork>
     */
    public function getScheduleWorks(): Collection
    {
        return $this->scheduleWorks;
    }

    public function addScheduleWork(ScheduleWork $scheduleWork): static
    {
        if (!$this->scheduleWorks->contains($scheduleWork)) {
            $this->scheduleWorks->add($scheduleWork);
            $scheduleWork->setDoctor($this);
        }

        return $this;
    }

    public function removeScheduleWork(ScheduleWork $scheduleWork): static
    {
        if ($this->scheduleWorks->removeElement($scheduleWork)) {
            // set the owning side to null (unless already changed)
            if ($scheduleWork->getDoctor() === $this) {
                $scheduleWork->setDoctor(null);
            }
        }

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getConsultationFee(): ?float
    {
        return $this->consultationFee;
    }

    public function setConsultationFee(?float $consultationFee): static
    {
        $this->consultationFee = $consultationFee;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setPatient($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getPatient() === $this) {
                $appointment->setPatient(null);
            }
        }

        return $this;
    }

    public function getDoctorAppointments(): Collection
    {
        return $this->doctorAppointments;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function isDel(): ?bool
    {
        return $this->isDel;
    }
    public function getDel(): bool
    {
        return $this->isDel ?? false;
    }
    public function setDel(?bool $isDel): static
    {
        $this->isDel = $isDel;

        return $this;
    }

    public function getQualification(): ?string
    {
        return $this->qualification;
    }

    public function setQualification(?string $qualification): static
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getSalt()
    {
        return null;
    }

    /**
     * @return Collection<int, Momo>
     */
    public function getMomoTransactions(): Collection
    {
        return $this->momoTransactions;
    }

    public function addMomoTransaction(Momo $momoTransaction): static
    {
        if (!$this->momoTransactions->contains($momoTransaction)) {
            $this->momoTransactions->add($momoTransaction);
            $momoTransaction->setCustomer($this);
        }

        return $this;
    }

    public function removeMomoTransaction(Momo $momoTransaction): static
    {
        if ($this->momoTransactions->removeElement($momoTransaction)) {
            // set the owning side to null (unless already changed)
            if ($momoTransaction->getCustomer() === $this) {
                $momoTransaction->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
            $review->setDoctor($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getDoctor() === $this) {
                $review->setDoctor(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): self
    {
        $this->averageRating = $averageRating;

        return $this;
    }
}
