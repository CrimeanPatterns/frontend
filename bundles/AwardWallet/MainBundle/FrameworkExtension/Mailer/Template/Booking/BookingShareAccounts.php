<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbCustomProgram;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookingShareAccounts extends AbstractBookingTemplate
{
    /**
     * @var AbAccountProgram[]|AbCustomProgram[]
     */
    public $programs = [];

    public $enableUnsubscribe = false;

    public static function getDescription(): string
    {
        return 'Booker has requested access to user accounts';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);
        $template->toUser($template->request->getUser(), false);
        $template->toBooker = false;

        $template->programs = array_merge(
            $template->request->getCustomPrograms()->toArray(),
            $template->request->getAccounts()->toArray()
        );

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
