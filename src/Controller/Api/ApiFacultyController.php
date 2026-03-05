<?php

namespace App\Controller\Api;

use App\Entity\AcademicYear;
use App\Entity\Notification;
use App\Entity\Schedule;
use App\Entity\User;
use App\Service\NotificationService;
use App\Service\SystemSettingsService;
use App\Service\TeachingLoadPdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TCPDF;

#[Route('/api/faculty', name: 'api_faculty_')]
#[IsGranted('ROLE_FACULTY')]
class ApiFacultyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SystemSettingsService $systemSettingsService,
        private TeachingLoadPdfService $teachingLoadPdfService,
        private NotificationService $notificationService,
    ) {
    }

    // ──────────────────────────────────────────────
    //  PROFILE
    // ──────────────────────────────────────────────

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id'           => $user->getId(),
            'username'     => $user->getUsername(),
            'email'        => $user->getEmail(),
            'first_name'   => $user->getFirstName(),
            'middle_name'  => $user->getMiddleName(),
            'last_name'    => $user->getLastName(),
            'full_name'    => $user->getFullName(),
            'employee_id'  => $user->getEmployeeId(),
            'position'     => $user->getPosition(),
            'address'      => $user->getAddress(),
            'other_designation' => $user->getOtherDesignation(),
            'department'   => $user->getDepartment() ? [
                'id'   => $user->getDepartment()->getId(),
                'name' => $user->getDepartment()->getName(),
            ] : null,
            'college' => $user->getCollege() ? [
                'id'   => $user->getCollege()->getId(),
                'name' => $user->getCollege()->getName(),
            ] : null,
        ]);
    }

    #[Route('/profile', name: 'profile_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (isset($data['first_name'])) {
            $user->setFirstName($data['first_name']);
        }
        if (isset($data['middle_name'])) {
            $user->setMiddleName($data['middle_name']);
        }
        if (isset($data['last_name'])) {
            $user->setLastName($data['last_name']);
        }
        if (isset($data['address'])) {
            $user->setAddress($data['address']);
        }
        if (isset($data['other_designation'])) {
            $user->setOtherDesignation($data['other_designation']);
        }

        try {
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ──────────────────────────────────────────────
    //  DASHBOARD
    // ──────────────────────────────────────────────

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $currentAcademicYear = $this->entityManager->getRepository(AcademicYear::class)
            ->findOneBy(['isCurrent' => true]);

        $activeSemester = $currentAcademicYear?->getCurrentSemester();

        // Today's day patterns
        $today = new \DateTime();
        $dayOfWeek = $today->format('l');
        $dayPatterns = $this->getDayPatternsForDay($dayOfWeek);

        // Today's schedules
        $todaySchedules = [];
        if ($currentAcademicYear && $activeSemester && !empty($dayPatterns)) {
            $todaySchedules = $this->entityManager->getRepository(Schedule::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.subject', 'sub')->addSelect('sub')
                ->leftJoin('s.room', 'r')->addSelect('r')
                ->where('s.faculty = :faculty')
                ->andWhere('s.status = :status')
                ->andWhere('s.academicYear = :ay')
                ->andWhere('s.semester = :semester')
                ->andWhere('s.dayPattern IN (:patterns)')
                ->setParameter('faculty', $user)
                ->setParameter('status', 'active')
                ->setParameter('ay', $currentAcademicYear)
                ->setParameter('semester', $activeSemester)
                ->setParameter('patterns', $dayPatterns)
                ->orderBy('s.startTime', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // All active schedules for stats
        $allSchedules = [];
        if ($currentAcademicYear && $activeSemester) {
            $allSchedules = $this->entityManager->getRepository(Schedule::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.subject', 'sub')->addSelect('sub')
                ->where('s.faculty = :faculty')
                ->andWhere('s.status = :status')
                ->andWhere('s.academicYear = :ay')
                ->andWhere('s.semester = :semester')
                ->setParameter('faculty', $user)
                ->setParameter('status', 'active')
                ->setParameter('ay', $currentAcademicYear)
                ->setParameter('semester', $activeSemester)
                ->getQuery()
                ->getResult();
        }

        // Calculate statistics
        $totalHours = 0;
        $uniqueClasses = [];
        $totalStudents = 0;
        foreach ($allSchedules as $schedule) {
            $start = $schedule->getStartTime();
            $end = $schedule->getEndTime();
            $diff = $start->diff($end);
            $hours = $diff->h + ($diff->i / 60);
            $daysPerWeek = count($schedule->getDaysFromPattern());
            $totalHours += $hours * $daysPerWeek;

            $classKey = $schedule->getSubject()->getId() . '_' . $schedule->getSection();
            if (!isset($uniqueClasses[$classKey])) {
                $uniqueClasses[$classKey] = true;
                $totalStudents += $schedule->getEnrolledStudents();
            }
        }

        return $this->json([
            'today'       => $dayOfWeek,
            'academic_year' => $currentAcademicYear ? [
                'id'       => $currentAcademicYear->getId(),
                'year'     => $currentAcademicYear->getYear(),
                'semester' => $activeSemester,
            ] : null,
            'today_schedules' => array_map([$this, 'serializeSchedule'], $todaySchedules),
            'stats' => [
                'total_hours'    => round($totalHours, 1),
                'active_classes' => count($uniqueClasses),
                'total_students' => $totalStudents,
                'today_count'    => count($todaySchedules),
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  SCHEDULE
    // ──────────────────────────────────────────────

    #[Route('/schedule', name: 'schedule', methods: ['GET'])]
    public function schedule(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $currentAcademicYear = $this->entityManager->getRepository(AcademicYear::class)
            ->findOneBy(['isCurrent' => true]);

        $activeSemester = $this->systemSettingsService->getActiveSemester();
        $selectedSemester = $request->query->get('semester', $activeSemester);

        $schedules = $this->entityManager->getRepository(Schedule::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.subject', 'sub')->addSelect('sub')
            ->leftJoin('s.room', 'r')->addSelect('r')
            ->leftJoin('s.academicYear', 'ay')->addSelect('ay')
            ->where('s.faculty = :faculty')
            ->andWhere('s.status = :status')
            ->andWhere('ay.isCurrent = :isCurrent')
            ->andWhere('s.semester = :semester')
            ->setParameter('faculty', $user)
            ->setParameter('status', 'active')
            ->setParameter('isCurrent', true)
            ->setParameter('semester', $selectedSemester)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Calculate stats
        $stats = $this->calculateScheduleStats($schedules);

        return $this->json([
            'academic_year' => $currentAcademicYear ? [
                'id'   => $currentAcademicYear->getId(),
                'year' => $currentAcademicYear->getYear(),
            ] : null,
            'semester'  => $selectedSemester,
            'schedules' => array_map([$this, 'serializeSchedule'], $schedules),
            'stats'     => $stats,
        ]);
    }

    #[Route('/schedule/weekly', name: 'schedule_weekly', methods: ['GET'])]
    public function scheduleWeekly(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeSemester = $this->systemSettingsService->getActiveSemester();
        $selectedSemester = $request->query->get('semester', $activeSemester);

        $schedules = $this->entityManager->getRepository(Schedule::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.subject', 'sub')->addSelect('sub')
            ->leftJoin('s.room', 'r')->addSelect('r')
            ->where('s.faculty = :faculty')
            ->andWhere('s.status = :status')
            ->andWhere('s.semester = :semester')
            ->setParameter('faculty', $user)
            ->setParameter('status', 'active')
            ->setParameter('semester', $selectedSemester)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $weekly = [
            'Monday'    => [],
            'Tuesday'   => [],
            'Wednesday' => [],
            'Thursday'  => [],
            'Friday'    => [],
            'Saturday'  => [],
            'Sunday'    => [],
        ];

        foreach ($schedules as $schedule) {
            foreach ($schedule->getDaysFromPattern() as $day) {
                if (isset($weekly[$day])) {
                    $weekly[$day][] = $this->serializeSchedule($schedule);
                }
            }
        }

        return $this->json([
            'semester' => $selectedSemester,
            'weekly'   => $weekly,
        ]);
    }

    // ──────────────────────────────────────────────
    //  CLASSES
    // ──────────────────────────────────────────────

    #[Route('/classes', name: 'classes', methods: ['GET'])]
    public function classes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $currentAcademicYear = $this->entityManager->getRepository(AcademicYear::class)
            ->findOneBy(['isCurrent' => true]);

        $activeSemester = $this->systemSettingsService->getActiveSemester();
        $selectedSemester = $request->query->get('semester', $activeSemester);

        $schedules = $this->entityManager->getRepository(Schedule::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.subject', 'sub')->addSelect('sub')
            ->leftJoin('s.room', 'r')->addSelect('r')
            ->leftJoin('s.academicYear', 'ay')->addSelect('ay')
            ->where('s.faculty = :faculty')
            ->andWhere('s.status = :status')
            ->andWhere('ay.isCurrent = :isCurrent')
            ->andWhere('s.semester = :semester')
            ->setParameter('faculty', $user)
            ->setParameter('status', 'active')
            ->setParameter('isCurrent', true)
            ->setParameter('semester', $selectedSemester)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $totalStudents = 0;
        $totalHours = 0;
        foreach ($schedules as $schedule) {
            $totalStudents += $schedule->getEnrolledStudents() ?? 0;
            if ($schedule->getStartTime() && $schedule->getEndTime()) {
                $hours = ($schedule->getEndTime()->getTimestamp() - $schedule->getStartTime()->getTimestamp()) / 3600;
                $totalHours += $hours * count($schedule->getDaysFromPattern());
            }
        }

        return $this->json([
            'academic_year' => $currentAcademicYear ? [
                'id'   => $currentAcademicYear->getId(),
                'year' => $currentAcademicYear->getYear(),
            ] : null,
            'semester'  => $selectedSemester,
            'classes'   => array_map([$this, 'serializeSchedule'], $schedules),
            'stats'     => [
                'total_classes'  => count($schedules),
                'total_students' => $totalStudents,
                'teaching_hours' => round($totalHours, 1),
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  PDF EXPORTS
    // ──────────────────────────────────────────────

    #[Route('/schedule/export-pdf', name: 'schedule_export_pdf', methods: ['GET'])]
    public function exportSchedulePdf(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $currentAcademicYear = $this->entityManager->getRepository(AcademicYear::class)
            ->findOneBy(['isCurrent' => true]);

        $activeSemester = $this->systemSettingsService->getActiveSemester();
        $selectedSemester = $request->query->get('semester', $activeSemester);

        $schedules = $this->entityManager->getRepository(Schedule::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.subject', 'sub')->addSelect('sub')
            ->leftJoin('s.room', 'r')->addSelect('r')
            ->leftJoin('s.academicYear', 'ay')->addSelect('ay')
            ->where('s.faculty = :faculty')
            ->andWhere('s.status = :status')
            ->andWhere('ay.isCurrent = :isCurrent')
            ->andWhere('s.semester = :semester')
            ->setParameter('faculty', $user)
            ->setParameter('status', 'active')
            ->setParameter('isCurrent', true)
            ->setParameter('semester', $selectedSemester)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $weeklySchedule = $this->buildWeeklySchedule($schedules);
        $stats = $this->calculateScheduleStats($schedules);
        $pdf = $this->generateSchedulePdf($user, $schedules, $weeklySchedule, $stats, $currentAcademicYear, $selectedSemester);

        return new Response(
            $pdf->Output('teaching-schedule.pdf', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="teaching-schedule.pdf"',
            ]
        );
    }

    #[Route('/schedule/teaching-load-pdf', name: 'teaching_load_pdf', methods: ['GET'])]
    public function exportTeachingLoadPdf(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $currentAcademicYear = $this->entityManager->getRepository(AcademicYear::class)
            ->findOneBy(['isCurrent' => true]);

        if (!$currentAcademicYear) {
            return $this->json([
                'success' => false,
                'message' => 'No active academic year found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $activeSemester = $this->systemSettingsService->getActiveSemester();
        $selectedSemester = $request->query->get('semester', $activeSemester);

        $pdfContent = $this->teachingLoadPdfService->generateTeachingLoadPdf(
            $user,
            $currentAcademicYear,
            $selectedSemester
        );

        $facultyName = str_replace(' ', '_', $user->getFirstName() . '_' . $user->getLastName());
        $filename = 'Teaching_Load_' . $facultyName . '_' . $currentAcademicYear->getYear() . '_Sem' . $selectedSemester . '.pdf';

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    // ──────────────────────────────────────────────
    //  NOTIFICATIONS
    // ──────────────────────────────────────────────

    #[Route('/notifications', name: 'notifications', methods: ['GET'])]
    public function notifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $limit  = (int) $request->query->get('limit', 20);
        $offset = (int) $request->query->get('offset', 0);

        $notifications = $this->notificationService->getForUser($user, $limit, $offset);
        $unreadCount   = $this->notificationService->getUnreadCount($user);

        return $this->json([
            'notifications' => array_map(
                fn(Notification $n) => $this->notificationService->serialize($n),
                $notifications,
            ),
            'unread_count' => $unreadCount,
        ]);
    }

    #[Route('/notifications/unread-count', name: 'notifications_unread_count', methods: ['GET'])]
    public function notificationsUnreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'notification_read', methods: ['POST'])]
    public function markNotificationRead(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $notification = $this->entityManager->getRepository(Notification::class)->find($id);

        if (!$notification || $notification->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        $this->notificationService->markAsRead($notification);

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/read-all', name: 'notifications_read_all', methods: ['POST'])]
    public function markAllNotificationsRead(): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $updated = $this->notificationService->markAllAsRead($user);

        return $this->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    #[Route('/notifications/{id}', name: 'notification_delete', methods: ['DELETE'])]
    public function deleteNotification(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $notification = $this->entityManager->getRepository(Notification::class)->find($id);

        if (!$notification || !$this->notificationService->delete($notification, $user)) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        return $this->json(['success' => true]);
    }

    // ══════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════

    private function serializeSchedule(Schedule $schedule): array
    {
        return [
            'id'                => $schedule->getId(),
            'subject' => [
                'id'    => $schedule->getSubject()?->getId(),
                'code'  => $schedule->getSubject()?->getCode(),
                'title' => $schedule->getSubject()?->getTitle(),
                'units' => $schedule->getSubject()?->getUnits(),
                'type'  => $schedule->getSubject()?->getType(),
            ],
            'room' => [
                'id'       => $schedule->getRoom()?->getId(),
                'name'     => $schedule->getRoom()?->getName(),
                'code'     => $schedule->getRoom()?->getCode(),
                'building' => $schedule->getRoom()?->getBuilding(),
                'floor'    => $schedule->getRoom()?->getFloor(),
                'capacity' => $schedule->getRoom()?->getCapacity(),
            ],
            'day_pattern'       => $schedule->getDayPattern(),
            'day_pattern_label' => $schedule->getDayPatternLabel(),
            'days'              => $schedule->getDaysFromPattern(),
            'start_time'        => $schedule->getStartTime()?->format('H:i'),
            'end_time'          => $schedule->getEndTime()?->format('H:i'),
            'start_time_12h'    => $schedule->getStartTime()?->format('g:i A'),
            'end_time_12h'      => $schedule->getEndTime()?->format('g:i A'),
            'section'           => $schedule->getSection(),
            'enrolled_students' => $schedule->getEnrolledStudents(),
            'semester'          => $schedule->getSemester(),
            'academic_year' => $schedule->getAcademicYear() ? [
                'id'   => $schedule->getAcademicYear()->getId(),
                'year' => $schedule->getAcademicYear()->getYear(),
            ] : null,
            'status' => $schedule->getStatus(),
        ];
    }

    private function getDayPatternsForDay(string $dayOfWeek): array
    {
        return match ($dayOfWeek) {
            'Monday'    => ['M-W-F', 'M-T-TH-F', 'M-T'],
            'Tuesday'   => ['T-TH', 'M-T-TH-F', 'M-T'],
            'Wednesday' => ['M-W-F'],
            'Thursday'  => ['T-TH', 'M-T-TH-F', 'TH-F'],
            'Friday'    => ['M-W-F', 'M-T-TH-F', 'TH-F'],
            'Saturday'  => ['SAT'],
            'Sunday'    => ['SUN'],
            default     => [],
        };
    }

    private function buildWeeklySchedule(array $schedules): array
    {
        $weekly = [
            'Monday' => [], 'Tuesday' => [], 'Wednesday' => [],
            'Thursday' => [], 'Friday' => [], 'Saturday' => [], 'Sunday' => [],
        ];
        foreach ($schedules as $schedule) {
            foreach ($schedule->getDaysFromPattern() as $day) {
                if (isset($weekly[$day])) {
                    $weekly[$day][] = $schedule;
                }
            }
        }
        return $weekly;
    }

    private function calculateScheduleStats(array $schedules): array
    {
        $totalHours = 0;
        $totalStudents = 0;
        $uniqueRooms = [];
        $uniqueSubjects = [];

        foreach ($schedules as $schedule) {
            $start = $schedule->getStartTime();
            $end = $schedule->getEndTime();
            if ($start && $end) {
                $diff = $start->diff($end);
                $hours = $diff->h + ($diff->i / 60);
                $totalHours += $hours * count($schedule->getDaysFromPattern());
            }
            $totalStudents += $schedule->getEnrolledStudents() ?? 0;
            if ($schedule->getRoom()) {
                $uniqueRooms[$schedule->getRoom()->getId()] = true;
            }
            if ($schedule->getSubject()) {
                $uniqueSubjects[$schedule->getSubject()->getId()] = true;
            }
        }

        return [
            'total_hours'    => round($totalHours, 1),
            'total_classes'  => count($schedules),
            'total_students' => $totalStudents,
            'total_rooms'    => count($uniqueRooms),
        ];
    }

    private function generateSchedulePdf(User $user, array $schedules, array $weeklySchedule, array $stats, ?AcademicYear $academicYear, string $semester): TCPDF
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Smart Scheduling System');
        $pdf->SetAuthor($user->getFirstName() . ' ' . $user->getLastName());
        $pdf->SetTitle('Teaching Schedule');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Teaching Schedule', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $facultyName = $user->getFirstName() . ' ' . $user->getLastName();
        $ayText = $academicYear ? $academicYear->getYear() : '';
        $pdf->Cell(0, 5, $facultyName . ' - ' . $ayText . ' (' . $semester . ' Semester)', 0, 1, 'C');
        $pdf->Ln(5);

        // Stats row
        $pdf->SetFont('helvetica', 'B', 10);
        $boxWidth = 60;
        $statsData = [
            ['Total Hours', $stats['total_hours']],
            ['Classes', $stats['total_classes']],
            ['Students', $stats['total_students']],
            ['Rooms', $stats['total_rooms']],
        ];
        $x = 15;
        foreach ($statsData as $stat) {
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($boxWidth, 15, '', 1, 0, 'C', true);
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->Cell($boxWidth, 7, $stat[0], 0, 2, 'C');
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell($boxWidth, 8, (string)$stat[1], 0, 0, 'C');
            $pdf->SetFont('helvetica', 'B', 10);
            $x += $boxWidth + 5;
        }
        $pdf->Ln(20);

        // Table
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Class List', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(30, 7, 'Code', 1, 0, 'L', true);
        $pdf->Cell(65, 7, 'Subject', 1, 0, 'L', true);
        $pdf->Cell(45, 7, 'Schedule', 1, 0, 'L', true);
        $pdf->Cell(40, 7, 'Room', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Students', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Section', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($schedules as $schedule) {
            $pdf->Cell(30, 12, $schedule->getSubject()->getCode(), 1, 0, 'L');
            $pdf->MultiCell(65, 12, $schedule->getSubject()->getTitle(), 1, 'L', false, 0);
            $pdf->MultiCell(45, 6, $schedule->getDayPatternLabel() . "\n" . $schedule->getStartTime()->format('g:i A') . '-' . $schedule->getEndTime()->format('g:i A'), 1, 'L', false, 0);
            $pdf->Cell(40, 12, $schedule->getRoom()->getName(), 1, 0, 'L');
            $pdf->Cell(25, 12, (string)($schedule->getEnrolledStudents() ?? 0), 1, 0, 'C');
            $pdf->Cell(25, 12, $schedule->getSection() ?? '-', 1, 1, 'C');
        }

        return $pdf;
    }
}
