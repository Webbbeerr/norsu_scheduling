<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:create-test-users-sql',
    description: 'Create test users using raw SQL to avoid migration issues',
)]
class CreateTestUsersSqlCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Create a temporary user object for password hashing
            $tempUser = new User();
            
            $testUsers = [
                [
                    'username' => 'admin',
                    'email' => 'admin@test.com',
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'role' => 'admin',
                    'password' => $this->passwordHasher->hashPassword($tempUser, 'password')
                ],
                [
                    'username' => 'depthead',
                    'email' => 'dept@test.com',
                    'first_name' => 'John',
                    'last_name' => 'Department Head',
                    'role' => 'department_head',
                    'password' => $this->passwordHasher->hashPassword($tempUser, 'password')
                ],
                [
                    'username' => 'faculty',
                    'email' => 'faculty@test.com',
                    'first_name' => 'Jane',
                    'last_name' => 'Faculty Member',
                    'role' => 'faculty',
                    'password' => $this->passwordHasher->hashPassword($tempUser, 'password')
                ]
            ];

            foreach ($testUsers as $userData) {
                // Check if user already exists
                $existing = $this->connection->fetchOne(
                    'SELECT COUNT(*) FROM users WHERE email = ?',
                    [$userData['email']]
                );

                if ($existing > 0) {
                    $io->warning("User {$userData['email']} already exists, skipping...");
                    continue;
                }

                // Insert user
                $this->connection->insert('users', [
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'role' => $userData['role'],
                    'is_active' => 1,
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);

                $io->success("Created user: {$userData['email']} ({$userData['role']})");
            }

            $io->success('Test users created successfully!');
            $io->table(
                ['Email', 'Password', 'Role'],
                [
                    ['admin@test.com', 'password', 'Administrator'],
                    ['dept@test.com', 'password', 'Department Head'],
                    ['faculty@test.com', 'password', 'Faculty'],
                ]
            );

        } catch (\Exception $e) {
            $io->error('Error creating test users: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}