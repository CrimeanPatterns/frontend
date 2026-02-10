<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class TravelPlan extends AbstractDbEntity implements OwnableInterface
{
    /**
     * @var User|UserAgent|null
     */
    private $user;

    public function __construct(
        string $name,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'StartDate' => $start->format('Y-m-d H:i:s'),
            'EndDate' => $end->format('Y-m-d H:i:s'),
        ]));

        $this->user = $user;
    }

    /**
     * @return User|UserAgent|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|UserAgent|null $user
     */
    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }
}
