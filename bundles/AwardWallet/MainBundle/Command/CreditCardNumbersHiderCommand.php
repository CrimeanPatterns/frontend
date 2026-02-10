<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CreditCardNumbersHiderCommand extends Command
{
    private const REGEX = '/[[:<:]]([[:digit:]][- ]?){15,16}[[:>:]]/ims';
    protected static $defaultName = 'aw:booking:cc-hide';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Replacing credit card numbers in booking posts with "xxxx"')
            ->addArgument('messageId', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'filter by AbMessage.AbMessageID')
            ->addOption('testMode', 't', InputOption::VALUE_NONE, 'test mode, do not send anything, just log');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;

        // filter by AbMessage.AbMessageID
        if ($messageIds = $input->getArgument('messageId')) {
            $messageIds = array_map('intval', $messageIds);
            $logger->info(sprintf('filter by AbMessage.AbMessageID: [%s], count: %d', implode(', ', $messageIds), count($messageIds)));
        }

        // test mode
        $testMode = $input->getOption('testMode');
        $logger->info(sprintf('test mode: %s', $testMode ? 'true' : 'false'));

        $processed = 0;
        $rep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class);

        foreach (it($messageIds)
            ->onNth(100, function () {
                $this->em->clear();
            })
            ->map(function (int $id) use ($rep) {
                return $rep->find($id);
            })
            ->filterNotNull() as $message) {
            /** @var AbMessage $message */
            $logger->info(sprintf('processing #%d', $message->getAbMessageID()));

            $post = $message->getPost();

            if (preg_match(self::REGEX, $post)) {
                $logger->info('сс number was found');

                if ($testMode) {
                    continue;
                }

                $message->setPost(
                    preg_replace(self::REGEX, str_repeat('x', 15), $post)
                );
                $this->em->flush();
                $processed++;
            } else {
                $logger->info('сс number was not found');
            }
        }

        $logger->info(sprintf('processed %d messages', $processed));
        $output->writeln('done.');

        return 0;
    }
}
