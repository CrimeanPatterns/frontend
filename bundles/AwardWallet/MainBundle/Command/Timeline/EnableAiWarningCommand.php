<?php

namespace AwardWallet\MainBundle\Command\Timeline;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EnableAiWarningCommand extends Command
{
    protected static $defaultName = 'aw:timeline:enable-ai-warning';
    private TimelineManager $timelineManager;
    private ClockInterface $clock;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TimelineManager $timelineManager,
        ClockInterface $clock,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->timelineManager = $timelineManager;
        $this->clock = $clock;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->addArgument('itineraries', InputArgument::REQUIRED, 'Itineraries ids, comma-separated');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SourceListInterface $itinerary */
        foreach (
            it(\explode("\n", $input->getArgument('itineraries')))
            ->flatMap(fn (string $itineraryLine) => \explode(',', \trim($itineraryLine)))
            ->take(10)
            ->map(function (string $itineraryId) use ($output) {
                $output->write("Processing itinerary: $itineraryId");

                return [
                    $itineraryId,
                    $this->timelineManager->getEntityByItCode($itineraryId),
                ];
            })
            ->filter(function (array $tuple) use ($output): bool {
                /** @var SourceListInterface $itinerary */
                [$_, $itinerary] = $tuple;

                $pass =
                    isset($itinerary)
                    && \method_exists($itinerary, 'getUser')
                    && $itinerary->getUser()->hasRole('ROLE_STAFF');

                if (!$pass) {
                    $output->writeln(' ...SKIPPED because of itinerary not found or non-staff user');
                }

                return $pass;
            }) as [$id, $itinerary]
        ) {
            if (
                it($itinerary->getSources())
                ->none(fn (SourceInterface $source) => ($source instanceof Email) && $source->isGpt())
            ) {
                $itinerary->addSource(new Email(
                    self::$defaultName . '_' . StringHandler::getRandomCode(12),
                    self::$defaultName . '_' . StringHandler::getRandomCode(12),
                    new ParsedEmailSource(
                        ParsedEmailSource::SOURCE_SCANNER,
                        null,
                        self::$defaultName . '_' . StringHandler::getRandomCode(12),
                        true
                    ),
                    $this->clock->current()->getAsDateTimeImmutable()
                ));
                $output->write(" ...adding source");
            }

            if (\method_exists($itinerary, 'setShowAIWarning')) {
                $itinerary->setShowAIWarning(true);
                $output->write("...set show AI warning flag");
            }

            $this->entityManager->flush();
            $output->writeln(' ...DONE');
        }
    }
}
