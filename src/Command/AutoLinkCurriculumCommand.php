<?php

namespace App\Command;

use App\Repository\ScheduleRepository;
use App\Service\CurriculumLinkingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:auto-link-curriculum',
    description: 'Automatically link schedules to curriculum subjects for block sectioning conflict detection',
)]
class AutoLinkCurriculumCommand extends Command
{
    private ScheduleRepository $scheduleRepository;
    private CurriculumLinkingService $curriculumLinkingService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ScheduleRepository $scheduleRepository,
        CurriculumLinkingService $curriculumLinkingService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->scheduleRepository = $scheduleRepository;
        $this->curriculumLinkingService = $curriculumLinkingService;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run without making any changes to the database'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Process all schedules, including those already linked'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $processAll = $input->getOption('all');

        $io->title('Auto-Link Curriculum to Schedules');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be saved to the database');
        }

        // Get schedules without curriculum links
        if ($processAll) {
            $schedules = $this->scheduleRepository->findBy(['status' => 'active']);
            $io->info(sprintf('Processing all %d active schedules...', count($schedules)));
        } else {
            $schedules = $this->scheduleRepository->createQueryBuilder('s')
                ->where('s.curriculumSubject IS NULL')
                ->andWhere('s.status = :status')
                ->setParameter('status', 'active')
                ->getQuery()
                ->getResult();
            $io->info(sprintf('Found %d active schedules without curriculum links', count($schedules)));
        }

        if (empty($schedules)) {
            $io->success('All schedules are already linked to curriculum!');
            return Command::SUCCESS;
        }

        $io->newLine();
        $io->writeln('Processing schedules...');
        $io->newLine();

        $stats = [
            'linked' => 0,
            'already_linked' => 0,
            'failed' => 0
        ];

        $progressBar = $io->createProgressBar(count($schedules));
        $progressBar->start();

        foreach ($schedules as $schedule) {
            if ($schedule->getCurriculumSubject()) {
                $stats['already_linked']++;
                $progressBar->advance();
                continue;
            }

            $subject = $schedule->getSubject();
            $semester = $schedule->getSemester();
            
            if ($this->curriculumLinkingService->autoLinkCurriculum($schedule)) {
                $stats['linked']++;
                
                $io->writeln(sprintf(
                    "\n✅ Linked: %s Section %s (%s Semester) → Year %s",
                    $subject->getCode(),
                    $schedule->getSection(),
                    $semester,
                    $schedule->getCurriculumSubject()->getCurriculumTerm()
                        ? $schedule->getCurriculumSubject()->getCurriculumTerm()->getYearLevel()
                        : 'Unknown'
                ));
            } else {
                $stats['failed']++;
                $io->writeln(sprintf(
                    "\n❌ No match: %s Section %s (%s Semester)",
                    $subject->getCode(),
                    $schedule->getSection(),
                    $semester
                ));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Save changes unless in dry-run mode
        if (!$dryRun && $stats['linked'] > 0) {
            $this->entityManager->flush();
            $io->success('Changes saved to database!');
        } elseif ($dryRun && $stats['linked'] > 0) {
            $io->warning('DRY RUN - Changes were NOT saved to database');
        }

        // Display summary
        $io->section('Summary');
        $io->table(
            ['Status', 'Count'],
            [
                ['✅ Successfully Linked', $stats['linked']],
                ['✔️  Already Linked', $stats['already_linked']],
                ['❌ No Match Found', $stats['failed']],
                ['📊 Total Processed', count($schedules)],
            ]
        );

        if ($stats['failed'] > 0) {
            $io->warning(sprintf(
                '%d schedule(s) could not be linked. These schedules may:',
                $stats['failed']
            ));
            $io->listing([
                'Be for subjects not in the curriculum',
                'Have incorrect semester values',
                'Be from departments without curriculum data',
                'Be for non-standard courses (electives, special topics, etc.)'
            ]);
            $io->note('These schedules will not participate in block sectioning conflict detection.');
        }

        if ($stats['linked'] > 0) {
            $io->success(sprintf(
                'Successfully linked %d schedule(s) to curriculum!',
                $stats['linked']
            ));
            $io->note('Block sectioning conflict detection is now active for these schedules.');
        }

        return Command::SUCCESS;
    }
}
