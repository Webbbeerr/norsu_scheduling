<?php

namespace App\Service;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class RoomService
{
    public function __construct(
        private RoomRepository $roomRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Get all rooms with optional filters
     */
    public function getRooms(array $filters = []): array
    {
        $queryBuilder = $this->roomRepository->createQueryBuilder('r');

        // Search filter
        if (!empty($filters['search'])) {
            $queryBuilder->andWhere('r.name LIKE :search OR r.code LIKE :search OR r.building LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Active/Inactive filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $queryBuilder->andWhere('r.isActive = :isActive')
                ->setParameter('isActive', (bool) $filters['is_active']);
        }

        // Type filter
        if (!empty($filters['type'])) {
            $queryBuilder->andWhere('r.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Building filter
        if (!empty($filters['building'])) {
            $queryBuilder->andWhere('r.building = :building')
                ->setParameter('building', $filters['building']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'name';
        $sortOrder = $filters['order'] ?? 'ASC';
        $queryBuilder->orderBy('r.' . $sortField, $sortOrder);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get paginated rooms
     */
    public function getPaginatedRooms(array $filters = []): array
    {
        $page = max(1, $filters['page'] ?? 1);
        $limit = $filters['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        $rooms = $this->getRooms($filters);
        $total = count($rooms);
        $pages = ceil($total / $limit);

        $paginatedRooms = array_slice($rooms, $offset, $limit);

        return [
            'rooms' => $paginatedRooms,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'pages' => $pages,
                'limit' => $limit,
                'has_previous' => $page > 1,
                'has_next' => $page < $pages,
            ]
        ];
    }

    /**
     * Get room by ID
     */
    public function getRoomById(int $id): ?Room
    {
        return $this->roomRepository->find($id);
    }

    /**
     * Create a new room
     */
    public function createRoom(Room $room): Room
    {
        $room->setCreatedAt(new \DateTime());
        $room->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    /**
     * Update an existing room
     */
    public function updateRoom(Room $room): Room
    {
        $room->setUpdatedAt(new \DateTime());
        
        $this->entityManager->flush();

        return $room;
    }

    /**
     * Delete a room (soft delete)
     */
    public function deleteRoom(Room $room): void
    {
        $room->setDeletedAt(new \DateTime());
        $room->setIsActive(false);
        $this->entityManager->flush();
    }

    /**
     * Toggle room status
     */
    public function toggleRoomStatus(Room $room): Room
    {
        $room->setIsActive(!$room->isActive());
        $room->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $room;
    }

    /**
     * Get room statistics
     */
    public function getRoomStatistics(): array
    {
        $total = $this->roomRepository->count([]);
        $active = $this->roomRepository->count(['isActive' => true]);
        $inactive = $this->roomRepository->count(['isActive' => false]);
        
        // Get rooms created in last 7 days
        $sevenDaysAgo = new \DateTime('-7 days');
        $queryBuilder = $this->roomRepository->createQueryBuilder('r');
        $recent = $queryBuilder
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Get room type counts
        $typeStats = $this->entityManager->createQuery(
            'SELECT r.type, COUNT(r.id) as count FROM App\Entity\Room r WHERE r.isActive = true GROUP BY r.type'
        )->getResult();

        $typeCounts = [];
        foreach ($typeStats as $stat) {
            $typeCounts[$stat['type'] ?? 'unspecified'] = $stat['count'];
        }

        // Get building counts
        $buildingStats = $this->entityManager->createQuery(
            'SELECT r.building, COUNT(r.id) as count FROM App\Entity\Room r WHERE r.isActive = true AND r.building IS NOT NULL GROUP BY r.building'
        )->getResult();

        $buildingCounts = [];
        foreach ($buildingStats as $stat) {
            $buildingCounts[$stat['building']] = $stat['count'];
        }

        // Total capacity
        $totalCapacity = $this->entityManager->createQuery(
            'SELECT SUM(r.capacity) FROM App\Entity\Room r WHERE r.isActive = true AND r.capacity IS NOT NULL'
        )->getSingleScalarResult() ?? 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'recent' => $recent,
            'type_counts' => $typeCounts,
            'building_counts' => $buildingCounts,
            'total_capacity' => $totalCapacity,
        ];
    }

    /**
     * Get all unique buildings
     */
    public function getAllBuildings(): array
    {
        $queryBuilder = $this->roomRepository->createQueryBuilder('r');
        $buildings = $queryBuilder
            ->select('DISTINCT r.building')
            ->where('r.building IS NOT NULL')
            ->orderBy('r.building', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($buildings, 'building');
    }

    /**
     * Check if room code exists
     */
    public function isCodeUnique(string $code, ?int $excludeId = null): bool
    {
        $queryBuilder = $this->roomRepository->createQueryBuilder('r');
        $queryBuilder->where('r.code = :code')
            ->setParameter('code', $code);

        if ($excludeId) {
            $queryBuilder->andWhere('r.id != :id')
                ->setParameter('id', $excludeId);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult() === null;
    }
}
