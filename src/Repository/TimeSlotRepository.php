<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSlot>
 */
final class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    public function hasOverlap(TimeSlot $timeSlot): bool
    {
        $result = $this->getEntityManager()->createQuery(<<<DQL
            SELECT count(time_slot) as has_overlaps
            FROM {$this->getEntityName()} time_slot
            WHERE time_slot.event = :event
                AND time_slot.startsAt < :end
                AND time_slot.endsAt > :start
                AND time_slot.id != :id
        DQL
        )
            ->setParameter('id', $timeSlot->getId())
            ->setParameter('start', $timeSlot->getStartsAt())
            ->setParameter('end', $timeSlot->getEndsAt())
            ->setParameter('event', $timeSlot->getEvent())
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * @return array<TimeSlot>
     */
    public function findForEvent(Event $event): array
    {
        return $this->getEntityManager()->createQuery(<<<DQL
            SELECT
                time_slot,
                event,
                booth,
                scheduled_activity,
                activity
            FROM {$this->getEntityName()} time_slot
            INNER JOIN time_slot.event event
            LEFT JOIN time_slot.booth booth
            LEFT JOIN time_slot.scheduledActivities scheduled_activity
            LEFT JOIN scheduled_activity.activity activity
            WHERE time_slot.event = :event
        DQL
        )
            ->setParameter('event', $event)
            ->getResult();
    }
}
