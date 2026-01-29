<?php

namespace App\Tests\Service;

use App\Entity\Schedule;
use App\Entity\Subject;
use App\Entity\Room;
use App\Entity\AcademicYear;
use App\Entity\User;
use App\Entity\CurriculumSubject;
use App\Entity\CurriculumTerm;
use App\Repository\ScheduleRepository;
use App\Service\ScheduleConflictDetector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ScheduleConflictDetectorTest extends TestCase
{
    private $entityManager;
    private $scheduleRepository;
    private $conflictDetector;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->scheduleRepository = $this->createMock(ScheduleRepository::class);
        $this->conflictDetector = new ScheduleConflictDetector(
            $this->scheduleRepository,
            $this->entityManager
        );
    }

    /**
     * Test that same section, same year level, overlapping days and times = CONFLICT
     */
    public function testBlockSectioningConflictDetected(): void
    {
        echo "\n=== TEST 1: Block Sectioning Conflict (Same Year, Same Section, Overlapping Days) ===\n";
        
        // Create existing schedule: Year 3, Section A, T-TH, 7:00-8:30
        $existingSchedule = $this->createSchedule(
            'ITS 308',
            'A',
            '2nd Semester',
            'T-TH',
            '07:00',
            '08:30',
            3  // Year level 3
        );

        // Create new schedule: Year 3, Section A, M-T-TH-F, 7:00-8:30
        $newSchedule = $this->createSchedule(
            'ITS 310',
            'A',
            '2nd Semester',
            'M-T-TH-F',
            '07:00',
            '08:30',
            3  // Year level 3
        );

        echo "Existing: ITS 308, Year 3, Section A, T-TH, 7:00-8:30\n";
        echo "New:      ITS 310, Year 3, Section A, M-T-TH-F, 7:00-8:30\n";
        echo "Expected: CONFLICT (both have T and TH, same year, same section, same time)\n";

        // Mock the repository to return the existing schedule
        $this->scheduleRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderMock([$existingSchedule]));

        $conflicts = $this->conflictDetector->checkBlockSectioningConflicts($newSchedule);

        echo "Result:   " . (count($conflicts) > 0 ? "CONFLICT DETECTED ✅" : "NO CONFLICT ❌") . "\n";
        if (count($conflicts) > 0) {
            echo "Conflict Message: " . $conflicts[0] . "\n";
        }
        
        $this->assertGreaterThan(0, count($conflicts), 'Should detect block sectioning conflict');
    }

    /**
     * Test that different year levels = NO CONFLICT
     */
    public function testDifferentYearLevelsNoConflict(): void
    {
        echo "\n=== TEST 2: Different Year Levels (Should NOT Conflict) ===\n";
        
        // Existing: Year 2, Section A, T-TH, 7:00-8:30
        $existingSchedule = $this->createSchedule(
            'ITS 308',
            'A',
            '2nd Semester',
            'T-TH',
            '07:00',
            '08:30',
            2  // Year level 2
        );

        // New: Year 3, Section A, T-TH, 7:00-8:30
        $newSchedule = $this->createSchedule(
            'ITS 310',
            'A',
            '2nd Semester',
            'T-TH',
            '07:00',
            '08:30',
            3  // Year level 3
        );

        echo "Existing: ITS 308, Year 2, Section A, T-TH, 7:00-8:30\n";
        echo "New:      ITS 310, Year 3, Section A, T-TH, 7:00-8:30\n";
        echo "Expected: NO CONFLICT (different year levels)\n";

        $this->scheduleRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderMock([$existingSchedule]));

        $conflicts = $this->conflictDetector->checkBlockSectioningConflicts($newSchedule);

        echo "Result:   " . (count($conflicts) === 0 ? "NO CONFLICT ✅" : "CONFLICT DETECTED ❌") . "\n";
        
        $this->assertCount(0, $conflicts, 'Should NOT detect conflict for different year levels');
    }

    /**
     * Test that same year but different days = NO CONFLICT
     */
    public function testSameYearDifferentDaysNoConflict(): void
    {
        echo "\n=== TEST 3: Same Year, Different Days (Should NOT Conflict) ===\n";
        
        // Existing: Year 3, Section A, T-TH, 7:00-8:30
        $existingSchedule = $this->createSchedule(
            'ITS 308',
            'A',
            '2nd Semester',
            'T-TH',
            '07:00',
            '08:30',
            3
        );

        // New: Year 3, Section A, M-W-F, 7:00-8:30 (no overlapping days)
        $newSchedule = $this->createSchedule(
            'ITS 310',
            'A',
            '2nd Semester',
            'M-W-F',
            '07:00',
            '08:30',
            3
        );

        echo "Existing: ITS 308, Year 3, Section A, T-TH, 7:00-8:30\n";
        echo "New:      ITS 310, Year 3, Section A, M-W-F, 7:00-8:30\n";
        echo "Expected: NO CONFLICT (different days)\n";

        $this->scheduleRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderMock([$existingSchedule]));

        $conflicts = $this->conflictDetector->checkBlockSectioningConflicts($newSchedule);

        echo "Result:   " . (count($conflicts) === 0 ? "NO CONFLICT ✅" : "CONFLICT DETECTED ❌") . "\n";
        
        $this->assertCount(0, $conflicts, 'Should NOT detect conflict for different days');
    }

    /**
     * Test that same year but different times = NO CONFLICT
     */
    public function testSameYearDifferentTimesNoConflict(): void
    {
        echo "\n=== TEST 4: Same Year, Same Days, Different Times (Should NOT Conflict) ===\n";
        
        // Existing: Year 3, Section A, T-TH, 7:00-8:30
        $existingSchedule = $this->createSchedule(
            'ITS 308',
            'A',
            '2nd Semester',
            'T-TH',
            '07:00',
            '08:30',
            3
        );

        // New: Year 3, Section A, T-TH, 9:00-10:30 (different time)
        $newSchedule = $this->createSchedule(
            'ITS 310',
            'A',
            '2nd Semester',
            'T-TH',
            '09:00',
            '10:30',
            3
        );

        echo "Existing: ITS 308, Year 3, Section A, T-TH, 7:00-8:30\n";
        echo "New:      ITS 310, Year 3, Section A, T-TH, 9:00-10:30\n";
        echo "Expected: NO CONFLICT (different times)\n";

        $this->scheduleRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderMock([$existingSchedule]));

        $conflicts = $this->conflictDetector->checkBlockSectioningConflicts($newSchedule);

        echo "Result:   " . (count($conflicts) === 0 ? "NO CONFLICT ✅" : "CONFLICT DETECTED ❌") . "\n";
        
        $this->assertCount(0, $conflicts, 'Should NOT detect conflict for different times');
    }

    /**
     * Test day overlap logic specifically
     */
    public function testDayOverlapLogic(): void
    {
        echo "\n=== TEST 5: Day Overlap Logic ===\n";
        
        $testCases = [
            ['M-T-TH-F', 'T-TH', true, 'M-T-TH-F vs T-TH should overlap on T and TH'],
            ['M-W-F', 'T-TH', false, 'M-W-F vs T-TH should NOT overlap'],
            ['M-T-W-TH-F', 'M-W-F', true, 'M-T-W-TH-F vs M-W-F should overlap on M, W, F'],
            ['T-TH', 'T-TH', true, 'T-TH vs T-TH should overlap (exact match)'],
            ['M-W', 'T-TH', false, 'M-W vs T-TH should NOT overlap'],
        ];

        foreach ($testCases as [$pattern1, $pattern2, $expected, $description]) {
            $days1 = explode('-', $pattern1);
            $days2 = explode('-', $pattern2);
            $overlap = count(array_intersect($days1, $days2)) > 0;
            
            $status = $overlap === $expected ? '✅ PASS' : '❌ FAIL';
            echo "{$status}: {$description} - Result: " . ($overlap ? 'OVERLAP' : 'NO OVERLAP') . "\n";
            
            $this->assertEquals($expected, $overlap, $description);
        }
    }

    // Helper methods

    private function createSchedule(
        string $subjectCode,
        string $section,
        string $semester,
        string $dayPattern,
        string $startTime,
        string $endTime,
        int $yearLevel
    ): Schedule {
        $schedule = new Schedule();
        
        // Create and set subject
        $subject = new Subject();
        $subject->setCode($subjectCode);
        $schedule->setSubject($subject);
        
        // Create and set curriculum subject with year level
        $curriculumSubject = new CurriculumSubject();
        $curriculumTerm = new CurriculumTerm();
        $curriculumTerm->setYearLevel($yearLevel);
        $curriculumSubject->setCurriculumTerm($curriculumTerm);
        $schedule->setCurriculumSubject($curriculumSubject);
        
        // Set schedule details
        $schedule->setSection($section);
        $schedule->setSemester($semester);
        $schedule->setDayPattern($dayPattern);
        $schedule->setStartTime(new \DateTime($startTime));
        $schedule->setEndTime(new \DateTime($endTime));
        $schedule->setStatus('active');
        
        // Set required fields
        $room = new Room();
        $room->setCode('TEST-ROOM');
        $schedule->setRoom($room);
        
        $academicYear = new AcademicYear();
        $academicYear->setYear('2025-2026');
        $schedule->setAcademicYear($academicYear);
        
        $faculty = new User();
        $schedule->setFaculty($faculty);
        
        return $schedule;
    }

    private function createQueryBuilderMock(array $schedules)
    {
        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder(\Doctrine\ORM\AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMockForAbstractClass();

        $query->expects($this->any())
            ->method('getResult')
            ->willReturn($schedules);

        $qb->expects($this->any())
            ->method('innerJoin')
            ->willReturnSelf();

        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        return $qb;
    }
}
