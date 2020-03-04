<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @param $user
     * @param $timestamp
     * @return Task[]
     * @throws DBALException
     */
    public function findCurrentTasks($user, $timestamp = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT * FROM tasks t
            WHERE  :timestamp > unix_timestamp(t.start_at)
            AND unix_timestamp(t.end_at) > :timestamp
            AND author_id = :author_id
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['timestamp' => $timestamp ?? time(), 'author_id' => $user->getId()]);

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAll();

    }
}

