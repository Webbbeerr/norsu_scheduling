<?php

namespace App\Entity;

use App\Repository\AcademicYearRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AcademicYearRepository::class)]
#[ORM\Table(name: 'academic_years')]
#[UniqueEntity(fields: ['year'], message: 'This academic year already exists')]
class AcademicYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Academic year is required')]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{4}$/',
        message: 'Academic year must be in format YYYY-YYYY (e.g., 2024-2025)'
    )]
    private ?string $year = null;

    #[ORM\Column(name: 'start_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'is_current', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isCurrent = false;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->isCurrent = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isCurrent(): ?bool
    {
        return $this->isCurrent;
    }

    public function setIsCurrent(?bool $isCurrent): static
    {
        $this->isCurrent = $isCurrent;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * Get display name with status badges
     */
    public function getDisplayName(): string
    {
        $name = $this->year;
        if ($this->isCurrent) {
            $name .= ' (Current)';
        }
        return $name;
    }

    /**
     * Check if year is in the past
     */
    public function isPast(): bool
    {
        if (!$this->endDate) {
            return false;
        }
        return $this->endDate < new \DateTime();
    }

    /**
     * Check if year is in the future
     */
    public function isFuture(): bool
    {
        if (!$this->startDate) {
            return false;
        }
        return $this->startDate > new \DateTime();
    }

    /**
     * Check if year is currently active (between start and end date)
     */
    public function isCurrentlyActive(): bool
    {
        $now = new \DateTime();
        
        if (!$this->startDate || !$this->endDate) {
            return false;
        }
        
        return $now >= $this->startDate && $now <= $this->endDate;
    }

    public function __toString(): string
    {
        return $this->year ?? '';
    }
}
