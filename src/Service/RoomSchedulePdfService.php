<?php

namespace App\Service;

use App\Entity\Room;
use App\Repository\ScheduleRepository;
use TCPDF;

class RoomSchedulePdfService
{
    private ScheduleRepository $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }

    public function generateRoomSchedulePdf(Room $room, ?string $academicYear = null, ?string $semester = null): string
    {
        // Create new PDF document
        $pdf = new TCPDF('L', PDF_UNIT, 'LEGAL', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Smart Scheduling System');
        $pdf->SetAuthor('NORSU');
        $pdf->SetTitle('Room Schedule - ' . $room->getCode());
        $pdf->SetSubject('Room Schedule Report');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Get schedules for this room
        $schedules = $this->getSchedulesForRoom($room, $academicYear, $semester);

        // Generate header
        $this->generateHeader($pdf, $room, $academicYear, $semester);

        // Generate schedule table
        $this->generateScheduleTable($pdf, $schedules);

        // Return PDF as string
        return $pdf->Output('', 'S');
    }

    private function getSchedulesForRoom(Room $room, ?string $academicYear, ?string $semester): array
    {
        $qb = $this->scheduleRepository->createQueryBuilder('s')
            ->select('s', 'subj', 'f', 'ay', 'd', 'c')
            ->join('s.subject', 'subj')
            ->join('s.room', 'r')
            ->join('s.academicYear', 'ay')
            ->leftJoin('s.faculty', 'f')
            ->leftJoin('subj.department', 'd')
            ->leftJoin('d.college', 'c')
            ->where('r.id = :roomId')
            ->setParameter('roomId', $room->getId())
            ->orderBy('s.dayPattern', 'ASC')
            ->addOrderBy('s.startTime', 'ASC');

        if ($academicYear) {
            $qb->andWhere('ay.year = :academicYear')
               ->setParameter('academicYear', $academicYear);
        }

        if ($semester) {
            $qb->andWhere('s.semester = :semester')
               ->setParameter('semester', $semester);
        }

        return $qb->getQuery()->getResult();
    }

    private function generateHeader(TCPDF $pdf, Room $room, ?string $academicYear, ?string $semester): void
    {
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 8, 'NORSU - ROOM SCHEDULE REPORT', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 7, 'Room: ' . $room->getCode() . ' - ' . $room->getName(), 0, 1, 'C');
        
        $pdf->Ln(2);
        
        // Room Details
        $pdf->SetFont('helvetica', '', 10);
        $details = [];
        
        if ($room->getType()) {
            $details[] = 'Type: ' . ucfirst($room->getType());
        }
        if ($room->getBuilding()) {
            $details[] = 'Building: ' . $room->getBuilding();
        }
        if ($room->getFloor()) {
            $details[] = 'Floor: ' . $room->getFloor();
        }
        if ($room->getCapacity()) {
            $details[] = 'Capacity: ' . $room->getCapacity();
        }
        if ($room->getDepartment()) {
            $details[] = 'Department: ' . $room->getDepartment()->getName();
        }
        
        $detailsText = implode(' | ', $details);
        $pdf->Cell(0, 5, $detailsText, 0, 1, 'C');
        
        // Academic Year and Semester
        if ($academicYear || $semester) {
            $pdf->SetFont('helvetica', 'B', 10);
            $periodText = '';
            if ($academicYear) {
                $periodText .= 'Academic Year: ' . $academicYear;
            }
            if ($semester) {
                $periodText .= ($academicYear ? ' | ' : '') . 'Semester: ' . $semester;
            }
            $pdf->Cell(0, 5, $periodText, 0, 1, 'C');
        }
        
        $pdf->Ln(3);
    }

    private function generateScheduleTable(TCPDF $pdf, array $schedules): void
    {
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(41, 128, 185); // Blue background
        $pdf->SetTextColor(255, 255, 255); // White text
        
        // Column widths (total should be around 335 for legal landscape)
        $colWidths = [
            'code' => 25,
            'subject' => 60,
            'instructor' => 50,
            'day' => 25,
            'time' => 40,
            'section' => 20,
            'students' => 20,
            'semester' => 20,
            'year' => 25,
            'dept' => 50
        ];
        
        $pdf->Cell($colWidths['code'], 7, 'Code', 1, 0, 'C', true);
        $pdf->Cell($colWidths['subject'], 7, 'Subject', 1, 0, 'C', true);
        $pdf->Cell($colWidths['instructor'], 7, 'Instructor', 1, 0, 'C', true);
        $pdf->Cell($colWidths['day'], 7, 'Day', 1, 0, 'C', true);
        $pdf->Cell($colWidths['time'], 7, 'Time', 1, 0, 'C', true);
        $pdf->Cell($colWidths['section'], 7, 'Section', 1, 0, 'C', true);
        $pdf->Cell($colWidths['students'], 7, 'Students', 1, 0, 'C', true);
        $pdf->Cell($colWidths['semester'], 7, 'Semester', 1, 0, 'C', true);
        $pdf->Cell($colWidths['year'], 7, 'Year', 1, 0, 'C', true);
        $pdf->Cell($colWidths['dept'], 7, 'Department', 1, 1, 'C', true);
        
        // Reset text color for content
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        
        // Table rows
        $fill = false;
        foreach ($schedules as $schedule) {
            $subject = $schedule->getSubject();
            $faculty = $schedule->getFaculty();
            $academicYear = $schedule->getAcademicYear();
            $department = $subject ? $subject->getDepartment() : null;
            
            // Alternate row colors
            if ($fill) {
                $pdf->SetFillColor(245, 245, 245);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            // Get time range
            $startTime = $schedule->getStartTime() ? $schedule->getStartTime()->format('h:i A') : '';
            $endTime = $schedule->getEndTime() ? $schedule->getEndTime()->format('h:i A') : '';
            $timeRange = $startTime . ' - ' . $endTime;
            
            // Instructor name
            $instructorName = $faculty 
                ? ($faculty->getFirstName() . ' ' . $faculty->getLastName())
                : 'TBA';
            
            // Department name (truncate if too long)
            $deptName = $department ? $department->getName() : '';
            if (strlen($deptName) > 25) {
                $deptName = substr($deptName, 0, 22) . '...';
            }
            
            // Subject description (truncate if too long)
            $subjectDesc = $subject ? $subject->getDescription() : '';
            if (strlen($subjectDesc) > 35) {
                $subjectDesc = substr($subjectDesc, 0, 32) . '...';
            }
            
            $pdf->Cell($colWidths['code'], 6, $subject ? $subject->getCode() : '', 1, 0, 'L', true);
            $pdf->Cell($colWidths['subject'], 6, $subjectDesc, 1, 0, 'L', true);
            $pdf->Cell($colWidths['instructor'], 6, $instructorName, 1, 0, 'L', true);
            $pdf->Cell($colWidths['day'], 6, $schedule->getDayPattern() ?: '', 1, 0, 'C', true);
            $pdf->Cell($colWidths['time'], 6, $timeRange, 1, 0, 'C', true);
            $pdf->Cell($colWidths['section'], 6, $schedule->getSection() ?: '', 1, 0, 'C', true);
            $pdf->Cell($colWidths['students'], 6, (string)($schedule->getEnrolledStudents() ?: '0'), 1, 0, 'C', true);
            $pdf->Cell($colWidths['semester'], 6, $schedule->getSemester() ?: '', 1, 0, 'C', true);
            $pdf->Cell($colWidths['year'], 6, $academicYear ? $academicYear->getYear() : '', 1, 0, 'C', true);
            $pdf->Cell($colWidths['dept'], 6, $deptName, 1, 1, 'L', true);
            
            $fill = !$fill;
        }
        
        // Summary
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Total Schedules: ' . count($schedules), 0, 1, 'L');
        
        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Generated: ' . date('F d, Y h:i A'), 0, 1, 'R');
    }
}
