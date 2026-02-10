<?php

namespace AwardWallet\Tests\FunctionalSymfony\Globals\Localizer;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Codeception\Example;
use Symfony\Component\Intl\Locales;

class LocalizeServiceCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var LocalizeService
     */
    private $localizer;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->localizer = $I->grabService(LocalizeService::class);
    }

    /**
     * @dataprovider localizedTimeProvider
     */
    public function timeParse(\TestSymfonyGuy $I, Example $example)
    {
        $testList = [
            '00:00',
            '00:01',
            '11:59',
            '12:00',
            '12:01',
            '23:59',
        ];

        foreach ($testList as $time) {
            $this->localizer->setLocale($example['locale']);
            $localizedTime = $this->localizer->formatTime(new \DateTime($time));
            $I->comment("Localized time: $localizedTime");
            $parsedTime = $this->localizer->parseTime($localizedTime);
            $I->assertSame($time, $parsedTime->format('H:i'));
        }
    }

    public function formatCurrency(\TestSymfonyGuy $I): void
    {
        $data = [
            ['num' => 12345678, 'formatted' => '$12,345,678.00', 'currency' => 'USD', 'round' => false],
            ['num' => 12345678.99, 'formatted' => '$12,345,678.99', 'currency' => 'USD', 'round' => false],
            ['num' => 12345678, 'formatted' => '$12,345,678', 'currency' => 'USD', 'round' => true],
            ['num' => 12345678.99, 'formatted' => '$12,345,678.99', 'currency' => 'USD', 'round' => true],
            // ['num' => 12345678, 'formatted' => '$12,345,678.0', 'currency' => 'USD', 'round' => 1],
            // ['num' => 12345678.99, 'formatted' => '$12,345,678.99', 'currency' => 'USD', 'round' => 2],
        ];

        foreach ($data as $item) {
            $I->assertEquals(
                $item['formatted'],
                $this->localizer->formatCurrency($item['num'], $item['currency'], $item['round'])
            );
        }
    }

    protected function localizedTimeProvider()
    {
        $examples = [];

        foreach (Locales::getLocales() as $locale) {
            $examples[] = ['locale' => $locale];
        }

        return $examples;
    }
}
