<?php

namespace App\Service;

use App\Entity\Schedule;
use App\Entity\CurriculumSubject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to automatically link schedules to curriculum subjects
 * This ensures block sectioning conflict detection works correctly
 */
class CurriculumLinkingService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Automatically link a schedule to its curriculum subject
     * 
     * This finds the curriculum subject that matches:
     * - The schedule's subject
     * - The schedule's semester
     * - The subject's department
     * 
     * @param Schedule $schedule The schedule to link
     * @return bool True if a curriculum link was found and set, false otherwise
     */
    public function autoLinkCurriculum(Schedule $schedule): bool
    {
        // Skip if already linked
        if ($schedule->getCurriculumSubject()) {
            $this->logger->debug(
                'Schedule already has curriculum link',
                [
                    'schedule_id' => $schedule->getId(),
                    'subject' => $schedule->getSubject()->getCode(),
                    'curriculum_subject_id' => $schedule->getCurriculumSubject()->getId()
                ]
            );
            return true;
        }

        // Get required data
        $subject = $schedule->getSubject();
        $semester = $schedule->getSemester();
        
        if (!$subject || !$semester) {
            $this->logger->warning(
                'Cannot auto-link curriculum: missing subject or semester',
                ['schedule_id' => $schedule->getId()]
            );
            return false;
        }

        $department = $subject->getDepartment();
        if (!$department) {
            $this->logger->warning(
                'Cannot auto-link curriculum: subject has no department',
                [
                    'schedule_id' => $schedule->getId(),
                    'subject' => $subject->getCode()
                ]
            );
            return false;
        }

        // Find matching curriculum subject
        $curriculumSubject = $this->entityManager->createQueryBuilder()
            ->select('cs')
            ->from(CurriculumSubject::class, 'cs')
            ->join('cs.curriculumTerm', 'ct')
            ->join('cs.curriculum', 'c')
            ->where('cs.subject = :subject')
            ->andWhere('ct.semester = :semester')
            ->andWhere('c.department = :department')
            ->setParameter('subject', $subject)
            ->setParameter('semester', $semester)
            ->setParameter('department', $department)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($curriculumSubject) {
            $schedule->setCurriculumSubject($curriculumSubject);
            
            $yearLevel = $curriculumSubject->getCurriculumTerm() 
                ? $curriculumSubject->getCurriculumTerm()->getYearLevel() 
                : 'Unknown';
                
            $this->logger->info(
                'Auto-linked schedule to curriculum',
                [
                    'schedule_id' => $schedule->getId(),
                    'subject' => $subject->getCode(),
                    'section' => $schedule->getSection(),
                    'semester' => $semester,
                    'curriculum_subject_id' => $curriculumSubject->getId(),
                    'year_level' => $yearLevel
                ]
            );
            
            return true;
        }

        $this->logger->notice(
            'No matching curriculum subject found for schedule',
            [
                'schedule_id' => $schedule->getId(),
                'subject' => $subject->getCode(),
                'semester' => $semester,
                'department' => $department->getName()
            ]
        );

        return false;
    }

    /**
     * Auto-link curriculum for multiple schedules
     * 
     * @param Schedule[] $schedules Array of schedules to link
     * @return array Statistics: ['linked' => count, 'already_linked' => count, 'failed' => count]
     */
    public function autoLinkMultiple(array $schedules): array
    {
        $stats = [
            'linked' => 0,
            'already_linked' => 0,
            'failed' => 0
        ];

        foreach ($schedules as $schedule) {
            if (!$schedule instanceof Schedule) {
                continue;
            }

            if ($schedule->getCurriculumSubject()) {
                $stats['already_linked']++;
                continue;
            }

            if ($this->autoLinkCurriculum($schedule)) {
                $stats['linked']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }
}
