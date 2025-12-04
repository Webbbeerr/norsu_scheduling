<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Department;
use App\Entity\College;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Create test users with colleges and departments for the smart scheduling system',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creating Test Data for Smart Scheduling System');

        // Step 1: Create Colleges
        $io->section('Creating Colleges');
        $colleges = $this->createColleges($io);

        // Step 2: Create Departments
        $io->section('Creating Departments');
        $departments = $this->createDepartments($io, $colleges);

        // Step 3: Create Users
        $io->section('Creating Test Users');
        $this->createUsers($io, $departments);

        $io->success('All test data created successfully!');
        
        $io->section('Test User Credentials');
        $io->table(
            ['Username', 'Email', 'Password', 'Role', 'Department'],
            [
                ['admin', 'admin@norsu.edu.ph', 'password', 'Admin', 'N/A'],
                ['it.head', 'john.doe@norsu.edu.ph', 'password', 'Dept Head', 'Information Technology'],
                ['eng.head', 'maria.santos@norsu.edu.ph', 'password', 'Dept Head', 'Engineering'],
                ['alice.it', 'alice.smith@norsu.edu.ph', 'password', 'Faculty', 'Information Technology'],
                ['bob.it', 'bob.johnson@norsu.edu.ph', 'password', 'Faculty', 'Information Technology'],
                ['carol.eng', 'carol.brown@norsu.edu.ph', 'password', 'Faculty', 'Engineering'],
            ]
        );

        $io->note('You can now login with any of these credentials!');

        return Command::SUCCESS;
    }

    private function createColleges(SymfonyStyle $io): array
    {
        $collegesData = [
            [
                'code' => 'CCS',
                'name' => 'College of Computer Studies',
                'description' => 'Offers programs in Information Technology, Computer Science, and related fields'
            ],
            [
                'code' => 'COE',
                'name' => 'College of Engineering',
                'description' => 'Offers programs in Civil, Mechanical, and Electrical Engineering'
            ]
        ];

        $colleges = [];
        foreach ($collegesData as $data) {
            $existing = $this->entityManager->getRepository(College::class)
                ->findOneBy(['code' => $data['code']]);

            if ($existing) {
                $io->writeln("✓ College '{$data['name']}' already exists");
                $colleges[$data['code']] = $existing;
                continue;
            }

            $college = new College();
            $college->setCode($data['code']);
            $college->setName($data['name']);
            $college->setDescription($data['description']);

            $this->entityManager->persist($college);
            $colleges[$data['code']] = $college;
            
            $io->writeln("✓ Created college: {$data['name']}");
        }

        $this->entityManager->flush();
        return $colleges;
    }

    private function createDepartments(SymfonyStyle $io, array $colleges): array
    {
        $departmentsData = [
            [
                'code' => 'IT',
                'name' => 'Information Technology',
                'description' => 'Department of Information Technology',
                'collegeCode' => 'CCS'
            ],
            [
                'code' => 'CS',
                'name' => 'Computer Science',
                'description' => 'Department of Computer Science',
                'collegeCode' => 'CCS'
            ],
            [
                'code' => 'CE',
                'name' => 'Civil Engineering',
                'description' => 'Department of Civil Engineering',
                'collegeCode' => 'COE'
            ],
            [
                'code' => 'ME',
                'name' => 'Mechanical Engineering',
                'description' => 'Department of Mechanical Engineering',
                'collegeCode' => 'COE'
            ]
        ];

        $departments = [];
        foreach ($departmentsData as $data) {
            $existing = $this->entityManager->getRepository(Department::class)
                ->findOneBy(['code' => $data['code']]);

            if ($existing) {
                $io->writeln("✓ Department '{$data['name']}' already exists");
                $departments[$data['code']] = $existing;
                continue;
            }

            $department = new Department();
            $department->setCode($data['code']);
            $department->setName($data['name']);
            $department->setDescription($data['description']);
            $department->setCollege($colleges[$data['collegeCode']]);

            $this->entityManager->persist($department);
            $departments[$data['code']] = $department;
            
            $io->writeln("✓ Created department: {$data['name']} ({$colleges[$data['collegeCode']]->getName()})");
        }

        $this->entityManager->flush();
        return $departments;
    }

    private function createUsers(SymfonyStyle $io, array $departments): void
    {
        $usersData = [
            // Admin
            [
                'username' => 'admin',
                'email' => 'admin@norsu.edu.ph',
                'firstName' => 'System',
                'lastName' => 'Administrator',
                'employeeId' => 'EMP-2025-001',
                'role' => 1,
                'password' => 'password',
                'departmentCode' => null,
                'address' => 'NORSU Main Campus, Dumaguete City, Negros Oriental'
            ],
            // Department Heads
            [
                'username' => 'it.head',
                'email' => 'john.doe@norsu.edu.ph',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'employeeId' => 'EMP-2025-100',
                'role' => 2,
                'password' => 'password',
                'departmentCode' => 'IT',
                'address' => '123 Tech Street, Dumaguete City, Negros Oriental'
            ],
            [
                'username' => 'eng.head',
                'email' => 'maria.santos@norsu.edu.ph',
                'firstName' => 'Maria',
                'lastName' => 'Santos',
                'employeeId' => 'EMP-2025-101',
                'role' => 2,
                'password' => 'password',
                'departmentCode' => 'CE',
                'address' => '456 Engineering Ave, Dumaguete City, Negros Oriental'
            ],
            // Faculty Members - IT Department
            [
                'username' => 'alice.it',
                'email' => 'alice.smith@norsu.edu.ph',
                'firstName' => 'Alice',
                'lastName' => 'Smith',
                'employeeId' => 'EMP-2025-200',
                'role' => 3,
                'password' => 'password',
                'departmentCode' => 'IT',
                'address' => '789 Innovation Road, Dumaguete City, Negros Oriental'
            ],
            [
                'username' => 'bob.it',
                'email' => 'bob.johnson@norsu.edu.ph',
                'firstName' => 'Bob',
                'lastName' => 'Johnson',
                'employeeId' => 'EMP-2025-201',
                'role' => 3,
                'password' => 'password',
                'departmentCode' => 'IT',
                'address' => '321 Digital Lane, Dumaguete City, Negros Oriental'
            ],
            [
                'username' => 'charlie.it',
                'email' => 'charlie.williams@norsu.edu.ph',
                'firstName' => 'Charlie',
                'lastName' => 'Williams',
                'employeeId' => 'EMP-2025-202',
                'role' => 3,
                'password' => 'password',
                'departmentCode' => 'IT',
                'address' => '654 Software Street, Dumaguete City, Negros Oriental'
            ],
            // Faculty Members - Engineering Department
            [
                'username' => 'carol.eng',
                'email' => 'carol.brown@norsu.edu.ph',
                'firstName' => 'Carol',
                'lastName' => 'Brown',
                'employeeId' => 'EMP-2025-300',
                'role' => 3,
                'password' => 'password',
                'departmentCode' => 'CE',
                'address' => '987 Builders Blvd, Dumaguete City, Negros Oriental'
            ],
            [
                'username' => 'david.eng',
                'email' => 'david.davis@norsu.edu.ph',
                'firstName' => 'David',
                'lastName' => 'Davis',
                'employeeId' => 'EMP-2025-301',
                'role' => 3,
                'password' => 'password',
                'departmentCode' => 'CE',
                'address' => '147 Construction Court, Dumaguete City, Negros Oriental'
            ]
        ];

        foreach ($usersData as $userData) {
            $existing = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $userData['email']]);

            if ($existing) {
                $io->writeln("⚠ User '{$userData['email']}' already exists, skipping...");
                continue;
            }

            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmployeeId($userData['employeeId']);
            $user->setRole($userData['role']);

            // Set address if provided
            if (!empty($userData['address'])) {
                $user->setAddress($userData['address']);
            }

            // Assign department
            $departmentCode = $userData['departmentCode'];
            if (is_string($departmentCode) && isset($departments[$departmentCode])) {
                $user->setDepartment($departments[$departmentCode]);
            }

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);

            $roleStr = match($userData['role']) {
                1 => 'Admin',
                2 => 'Dept Head',
                3 => 'Faculty',
                default => 'User'
            };

            $deptStr = 'N/A';
            if (is_string($departmentCode) && isset($departments[$departmentCode])) {
                $deptStr = $departments[$departmentCode]->getName();
            }

            $io->writeln("✓ Created {$roleStr}: {$userData['firstName']} {$userData['lastName']} ({$deptStr})");
        }

        $this->entityManager->flush();
    }
}