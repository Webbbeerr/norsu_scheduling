<?php

namespace App\Command;

use App\Service\SystemSettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-system-settings',
    description: 'Test SystemSettingsService functionality',
)]
class TestSystemSettingsCommand extends Command
{
    public function __construct(
        private SystemSettingsService $systemSettingsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing SystemSettingsService');

        // Test 1: Get active academic year
        $io->section('1. Getting Active Academic Year');
        $activeYear = $this->systemSettingsService->getActiveAcademicYear();
        if ($activeYear) {
            $io->success('Active Year: ' . $activeYear->getYear());
            $io->writeln('ID: ' . $activeYear->getId());
        } else {
            $io->warning('No active academic year found');
        }

        // Test 2: Get active semester
        $io->section('2. Getting Active Semester');
        $activeSemester = $this->systemSettingsService->getActiveSemester();
        if ($activeSemester) {
            $io->success('Active Semester: ' . $activeSemester);
        } else {
            $io->warning('No active semester set');
        }

        // Test 3: Get active semester display
        $io->section('3. Getting Active Semester Display');
        $display = $this->systemSettingsService->getActiveSemesterDisplay();
        $io->info('Display: ' . $display);

        // Test 4: Check if has active semester
        $io->section('4. Checking if System Has Active Semester');
        $hasActive = $this->systemSettingsService->hasActiveSemester();
        if ($hasActive) {
            $io->success('System has active semester configured');
        } else {
            $io->warning('System does NOT have active semester configured');
        }

        // Test 5: Get transition info
        $io->section('5. Getting Semester Transition Info');
        $transitionInfo = $this->systemSettingsService->getSemesterTransitionInfo();
        $io->table(
            ['Key', 'Value'],
            array_map(fn($k, $v) => [$k, is_bool($v) ? ($v ? 'true' : 'false') : (string)$v], 
                array_keys($transitionInfo), 
                array_values($transitionInfo)
            )
        );

        // Test 6: Get available semesters
        $io->section('6. Getting Available Semesters');
        $semesters = $this->systemSettingsService->getAvailableSemesters();
        $io->listing($semesters);

        // Test 7: Try to set active semester (if year is available)
        if ($activeYear) {
            $io->section('7. Testing Set Active Semester');
            $io->writeln('Attempting to set 1st semester...');
            
            try {
                $result = $this->systemSettingsService->setActiveSemester($activeYear->getId(), '1st');
                $io->success('Successfully set active semester!');
                $io->writeln('New display: ' . $result->getFullDisplayName());
            } catch (\Exception $e) {
                $io->error('Failed to set active semester: ' . $e->getMessage());
            }

            // Verify the change
            $io->writeln('Verifying change...');
            $newDisplay = $this->systemSettingsService->getActiveSemesterDisplay();
            $io->info('Current display: ' . $newDisplay);
        }

        $io->success('All tests completed!');

        return Command::SUCCESS;
    }
}
