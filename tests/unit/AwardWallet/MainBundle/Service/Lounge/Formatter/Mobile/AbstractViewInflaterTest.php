<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Service\AirportTerminalMatcher\Matcher;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\OpeningHoursScheduleBuilder;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\ViewInflater;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use AwardWallet\Tests\Unit\BaseUserTest;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractViewInflaterTest extends BaseUserTest
{
    protected const DEP_CODE = 'XXX';
    protected const ARR_CODE = 'YYY';

    protected ?ViewInflater $viewInflater;

    public function _before()
    {
        parent::_before();

        $this->viewInflater = new ViewInflater(
            $this->em,
            $this->container->get(TranslatorInterface::class),
            $this->container->get(Finder::class),
            $this->container->get(Matcher::class),
            $this->container->get(OpeningHoursScheduleBuilder::class),
            $this->make(ApiVersioningService::class, [
                'supports' => true,
            ]),
            $this->container->get(Logger::class),
            [],
            [],
            $this->makeEmpty(BlogPostInterface::class, [
                'fetchPostById' => [],
            ]),
            $this->container->get(SafeExecutorFactory::class)
        );

        $this->db->executeQuery("DELETE FROM Lounge WHERE AirportCode IN ('" . self::DEP_CODE . "', '" . self::ARR_CODE . "')");
    }

    public function _after()
    {
        $this->viewInflater = null;

        parent::_after();
    }

    protected function createTripSegment(
        string $depAirport = self::DEP_CODE,
        string $arrAirport = self::ARR_CODE,
        ?string $depCode = self::DEP_CODE,
        ?string $depTerminal = null,
        ?string $arrCode = self::ARR_CODE,
        ?string $arrTerminal = null
    ): ?Tripsegment {
        return $this->makeEmpty(Tripsegment::class, [
            'getDepAirportName' => $depAirport,
            'getArrAirportName' => $arrAirport,
            'getDepcode' => $depCode,
            'getDepartureTerminal' => $depTerminal,
            'getArrcode' => $arrCode,
            'getArrivalTerminal' => $arrTerminal,
        ]);
    }
}
