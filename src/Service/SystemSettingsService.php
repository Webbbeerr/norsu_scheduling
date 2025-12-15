<?php

namespace App\Service;

use App\Entity\AcademicYear;
use App\Repository\AcademicYearRepository;
use Doctrine\ORM\EntityManagerInterface;

class SystemSettingsService
{
    private const VALID_SEMESTERS = ['1st', '2nd', 'Summer'];

    public function __construct(
        private AcademicYearRepository $academicYearRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get the active academic year (the one marked as is_current = true)
     */
    public function getActiveAcademicYear(): ?AcademicYear
    {
        return $this->academicYearRepository->findCurrent();
    }

    /**
     * Get the active semester from the current academic year
     */
    public function getActiveSemester(): ?string
    {
        $activeYear = $this->getActiveAcademicYear();
        return $activeYear?->getCurrentSemester();
    }

    /**
     * Get full display name of active semester (e.g., "2025-2026 | 1st Semester")
     */
    public function getActiveSemesterDisplay(): string
    {
        $activeYear = $this->getActiveAcademicYear();
        
        if (!$activeYear) {
            return 'No active semester set';
        }

        return $activeYear->getFullDisplayName();
    }

    /**
     * Check if a specific year and semester combination is currently active
     */
    public function isActiveSemester(int $academicYearId, string $semester): bool
    {
        $activeYear = $this->getActiveAcademicYear();
        
        if (!$activeYear) {
            return false;
        }

        return $activeYear->getId() === $academicYearId 
            && $activeYear->getCurrentSemester() === $semester;
    }

    /**
     * Set the active academic year and semester
     * This will mark the specified year as current and set its semester
     */
    public function setActiveSemester(int $academicYearId, string $semester): AcademicYear
    {
        // Validate semester value
        if (!in_array($semester, self::VALID_SEMESTERS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid semester "%s". Must be one of: %s', 
                    $semester, 
                    implode(', ', self::VALID_SEMESTERS)
                )
            );
        }

        // Get the academic year
        $academicYear = $this->academicYearRepository->find($academicYearId);
        if (!$academicYear) {
            throw new \InvalidArgumentException('Academic year not found');
        }

        // Check if academic year is active
        if (!$academicYear->isActive()) {
            throw new \InvalidArgumentException('Cannot set inactive academic year as current');
        }

        // Unset current flag from all other years (but not this one)
        $this->unsetAllCurrentYears($academicYearId);

        // Set this as current with the specified semester
        $academicYear->setIsCurrent(true);
        $academicYear->setCurrentSemester($semester);
        $academicYear->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $academicYear;
    }

    /**
     * Change only the semester of the current active year
     * Useful for transitioning from 1st to 2nd semester within the same year
     */
    public function changeActiveSemester(string $newSemester): AcademicYear
    {
        // Validate semester value
        if (!in_array($newSemester, self::VALID_SEMESTERS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid semester "%s". Must be one of: %s', 
                    $newSemester, 
                    implode(', ', self::VALID_SEMESTERS)
                )
            );
        }

        $activeYear = $this->getActiveAcademicYear();
        if (!$activeYear) {
            throw new \RuntimeException('No active academic year found. Please set one first.');
        }

        // Don't allow setting the same semester
        if ($activeYear->getCurrentSemester() === $newSemester) {
            throw new \InvalidArgumentException(
                sprintf('Semester "%s" is already the active semester', $newSemester)
            );
        }

        $activeYear->setCurrentSemester($newSemester);
        $activeYear->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $activeYear;
    }

    /**
     * Get validation errors for semester transition
     * Returns an array of warnings/errors before changing semester
     */
    public function validateSemesterTransition(int $academicYearId, string $semester): array
    {
        $warnings = [];

        // Check if semester is valid
        if (!in_array($semester, self::VALID_SEMESTERS, true)) {
            $warnings[] = sprintf('Invalid semester "%s". Must be one of: %s', 
                $semester, 
                implode(', ', self::VALID_SEMESTERS)
            );
            return $warnings;
        }

        // Check if academic year exists and is active
        $academicYear = $this->academicYearRepository->find($academicYearId);
        if (!$academicYear) {
            $warnings[] = 'Academic year not found';
            return $warnings;
        }

        if (!$academicYear->isActive()) {
            $warnings[] = 'Academic year is not active';
        }

        // Check if this is already the active semester
        if ($this->isActiveSemester($academicYearId, $semester)) {
            $warnings[] = sprintf('"%s - %s Semester" is already the active semester', 
                $academicYear->getYear(), 
                $semester
            );
        }

        return $warnings;
    }

    /**
     * Get available semesters
     */
    public function getAvailableSemesters(): array
    {
        return self::VALID_SEMESTERS;
    }

    /**
     * Check if system has an active semester configured
     */
    public function hasActiveSemester(): bool
    {
        $activeYear = $this->getActiveAcademicYear();
        return $activeYear !== null && $activeYear->getCurrentSemester() !== null;
    }

    /**
     * Get semester transition information
     * Useful for displaying what will happen when semester changes
     */
    public function getSemesterTransitionInfo(): array
    {
        $activeYear = $this->getActiveAcademicYear();
        
        if (!$activeYear) {
            return [
                'has_active' => false,
                'current_year' => null,
                'current_semester' => null,
                'message' => 'No active semester configured'
            ];
        }

        return [
            'has_active' => true,
            'current_year' => $activeYear->getYear(),
            'current_semester' => $activeYear->getCurrentSemester(),
            'current_year_id' => $activeYear->getId(),
            'display_name' => $activeYear->getFullDisplayName(),
            'message' => 'Active semester found'
        ];
    }

    /**
     * Unset current flag from all academic years
     */
    private function unsetAllCurrentYears(?int $exceptId = null): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(AcademicYear::class, 'ay')
           ->set('ay.isCurrent', ':false')
           ->set('ay.currentSemester', ':null')
           ->setParameter('false', false)
           ->setParameter('null', null)
           ->where('ay.deletedAt IS NULL');

        if ($exceptId !== null) {
            $qb->andWhere('ay.id != :exceptId')
               ->setParameter('exceptId', $exceptId);
        }

        $qb->getQuery()->execute();
    }

    /**
     * Get semester order for comparison
     * Returns numeric value for semester ordering (1st=1, 2nd=2, Summer=3)
     */
    public function getSemesterOrder(string $semester): int
    {
        return match($semester) {
            '1st' => 1,
            '2nd' => 2,
            'Summer' => 3,
            default => 0
        };
    }

    /**
     * Get next logical semester
     * 1st -> 2nd, 2nd -> Summer, Summer -> 1st (new year)
     */
    public function getNextSemester(string $currentSemester): ?string
    {
        return match($currentSemester) {
            '1st' => '2nd',
            '2nd' => 'Summer',
            'Summer' => '1st', // This would need a new academic year
            default => null
        };
    }
}
