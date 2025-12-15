<?php

namespace App\Command;

use App\Repository\ScheduleRepository;
use App\Service\SystemSettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-schedule-filtering',
    description: 'Test schedule filtering by active semester',
)]
class TestScheduleFilteringCommand extends Command
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private SystemSettingsService $systemSettingsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Schedule Filtering by Active Semester');

        // Get active semester info
        $io->section('1. Active Semester Info');
        $activeYear = $this->systemSettingsService->getActiveAcademicYear();
        $activeSemester = $this->systemSettingsService->getActiveSemester();
        
        if ($activeYear && $activeSemester) {
            $io->success(sprintf('Active: %s | %s Semester', $activeYear->getYear(), $activeSemester));
            $io->writeln('Year ID: ' . $activeYear->getId());
        } else {
            $io->warning('No active semester configured');
            return Command::SUCCESS;
        }

        // Test finding all schedules with active semester filter
        $io->section('2. Schedules with Active Semester Filter');
        $schedulesFiltered = $this->scheduleRepository->findAllWithRelations($activeYear, $activeSemester);
        $io->info('Schedules found: ' . count($schedulesFiltered));
        
        if (count($schedulesFiltered) > 0) {
            $io->writeln('First few schedules:');
            foreach (array_slice($schedulesFiltered, 0, 3) as $schedule) {
                $io->writeln(sprintf(
                    '  - %s (Room: %s, Year: %s, Semester: %s)',
                    $schedule->getSubject()->getCode(),
                    $schedule->getRoom()->getName(),
                    $schedule->getAcademicYear()->getYear(),
                    $schedule->getSemester()
                ));
            }
        }

        // Test finding all schedules without filter
        $io->section('3. All Schedules (No Filter)');
        $schedulesAll = $this->scheduleRepository->findAllWithRelations();
        $io->info('Total schedules (all semesters): ' . count($schedulesAll));

        // Count by semester
        $io->section('4. Schedule Count by Active Semester');
        $count = $this->scheduleRepository->countByAcademicYearAndSemester($activeYear, $activeSemester);
        $io->success(sprintf('Count: %d schedules', $count));

        $io->success('All tests completed!');

        return Command::SUCCESS;
    }
}
