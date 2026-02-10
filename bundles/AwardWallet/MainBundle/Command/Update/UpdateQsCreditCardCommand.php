<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\Service\CreditCards\QsCreditCards;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateQsCreditCardCommand extends Command
{
    public const FEED_URLS = [
        'qs_1' => 'https://www.nextinsure.com/listingdisplay/display/?json=1&max=999&xml_version=2&static_links=1&ccis=639943%2C188933%2C188934%2C188938%2C574429%2C636744%2C637052%2C637902%2C639385%2C640029&src=639396',
        // 'qs_2' => 'https://nextinsure.quinstage.com/listingdisplay/Display/?json=1&src=639396&crd=4',
        // 'qs_3' => 'https://nextinsure.quinstage.com/listingdisplay/Display/?json=1&src=639396&crd=106',
        'aw_1' => 'https://awardwallet.com/blog/?aw_quinstreet_export',
    ];
    public static $defaultName = 'aw:update-qs-credit-card';

    private LoggerInterface $logger;
    private QsCreditCards $qsCreditCards;

    public function __construct(
        LoggerInterface $logger,
        QsCreditCards $qsCreditCards
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->qsCreditCards = $qsCreditCards;
    }

    protected function configure()
    {
        $this->setDescription('Update QuinStreet Credit Card from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cards = $this->qsCreditCards->getCreditCard(self::FEED_URLS);

        if (empty($cards)) {
            throw new \Exception('Feed data not received');
        }

        $log = $this->qsCreditCards->update($cards);

        $msg = [
            'Result processed, feed card count ' . \count($cards),
            ' Updated: ' . $log['update'],
            ' Inserted: ' . $log['insert'],
            ' Renamed: ' . (empty($log['rename']) ? 0 : implode("\r\n", $log['rename'])),
        ];
        $this->logger->info(implode("\n\n", $msg));
        $output->writeln($msg);

        return 0;
    }
}
