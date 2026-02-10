<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\MainBundle\Service\Blog\NonAffiliateCards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNonAffiliateDisclosureCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:update-non-affiliate-disclosure';

    private NonAffiliateCards $nonAffiliateCards;

    public function __construct(
        NonAffiliateCards $nonAffiliateCards
    ) {
        parent::__construct();

        $this->nonAffiliateCards = $nonAffiliateCards;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updating the "IsNonAffiliateDisclosure" field from blog to display the card is not available on this site.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $result = $this->nonAffiliateCards->syncNonAffiliateDisclosure();

        $keys = [
            'notFound' => 'Not Found Cards',
            'actives' => 'Disclosure is ON',
            // 'inactives' => 'Disclosure is OFF',
        ];

        foreach ($keys as $key => $title) {
            $output->writeln($title);

            foreach ($result[$key] as $item) {
                $output->writeln(' - ' . ($item['CardFullName'] ?? $item['Name'] ?? $item['name']));
            }

            $output->writeln('');
        }

        $output->writeln(['', 'done.']);
    }
}
