<?php

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use App\Validator\NoOverlappingTimeSlot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TimeSlotRepository::class)]
#[NoOverlappingTimeSlot]
class TimeSlot implements HasCreators
{
    use Field\Id { __construct as generateId; }
    use Field\StartEndDates;
    use Field\Timestampable;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'event_id', nullable: false)]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: Booth::class, inversedBy: 'timeSlots')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private Booth $booth;

    #[ORM\Column(name: 'is_open', type: Types::BOOLEAN, nullable: false, options: ['default' => 1])]
    #[Assert\Type('bool')]
    #[Assert\NotNull]
    private bool $open = true;

    #[ORM\OneToMany(targetEntity: ScheduledActivity::class, mappedBy: 'timeSlot')]
    private Collection $scheduledActivities;

    public function __construct()
    {
        $this->generateId();
        $this->generateTimestamps();
        $this->scheduledActivities = new ArrayCollection();
    }

    public static function create(Event $event, Booth $booth, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): self
    {
        $item = new self();

        $item->startsAt = $startsAt;
        $item->endsAt = $endsAt;
        $item->event = $event;
        $item->booth = $booth;

        return $item;
    }

    public function __toString(): string
    {
        return sprintf('%s (⏲ %s ➡ %s)', $this->booth, $this->startsAt?->format('Y-m-d H:i:s'), $this->endsAt?->format('Y-m-d H:i:s'));
    }

    #[Assert\IsTrue(message: 'Time slot start and end date must be included in start and end date from the associated Event.')]
    public function isEventDateValid(): bool
    {
        return $this->startsAt >= $this->event->getStartsAt()
            && $this->startsAt <= $this->event->getEndsAt()
            && $this->endsAt >= $this->event->getStartsAt()
            && $this->endsAt <= $this->event->getEndsAt()
        ;
    }

    public function isInHour(int $hour): bool
    {
        return $this->getStartsAt()->format('H') <= $hour
            && $this->getEndsAt()->format('H') > $hour;
    }

    private function isOpenForPlanning(): bool
    {
        if (!$this->open) {
            return false;
        }

        return \array_any($this->scheduledActivities->toArray(), static fn (ScheduledActivity $activity) => $activity->isAccepted());
    }

    public function getCreators(): Collection
    {
        return $this->event->getCreators();
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    public function getBooth(): Booth
    {
        return $this->booth;
    }

    public function setBooth(Booth $booth): void
    {
        $this->booth = $booth;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function setOpen(bool $open): void
    {
        $this->open = $open;
    }

    /**
     * @return Collection<ScheduledActivity>
     */
    public function getScheduledActivities(): Collection
    {
        return $this->scheduledActivities;
    }
}
