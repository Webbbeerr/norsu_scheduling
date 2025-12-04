<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s', 'subj', 'r', 'ay')
            ->join('s.subject', 'subj')
            ->join('s.room', 'r')
            ->join('s.academicYear', 'ay')
            ->orderBy('s.dayPattern', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDepartment(int $departmentId): array
    {
        return $this->createQueryBuilder('s')
            ->select('s', 'subj', 'r', 'ay')
            ->join('s.subject', 'subj')
            ->join('s.room', 'r')
            ->join('s.academicYear', 'ay')
            ->where('subj.department = :deptId')
            ->setParameter('deptId', $departmentId)
            ->orderBy('s.dayPattern', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFaculty(int $facultyId): array
    {
        // Faculty field has been removed from schedules
        // This method is no longer applicable
        return [];
    }
}
