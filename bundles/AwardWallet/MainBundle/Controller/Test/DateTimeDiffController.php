<?php

namespace AwardWallet\MainBundle\Controller\Test;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DateTimeDiffController
{
    public const DATA_FILE = __DIR__ . '/../../../../../tests/_data/intervals.yml';

    /**
     * @Route("/test/date-time-diff")
     * @Security("is_granted('SITE_DEV_MODE')")
     */
    public function testAction(Environment $twig, KernelInterface $kernel)
    {
        return new Response($twig->render('@AwardWalletMain/Test/dateTimeDiff.html.twig', [
            'env' => $kernel->getEnvironment(),
            'tests' => $this->getFixtures(),
        ]));
    }

    /**
     * @Route("/test/date-time-diff-form")
     * @Security("is_granted('SITE_DEV_MODE')")
     */
    public function formAction(Environment $twig)
    {
        return new Response($twig->render('@AwardWalletMain/Test/formDateTimeDiff.html.twig'));
    }

    public static function parseData(
        ?string $intervalFilter = null,
        ?string $methodFilter = null,
        ?int $numberFilter = null
    ): array {
        return it(Yaml::parseFile(DateTimeDiffController::DATA_FILE))
            ->filter(function ($value) use ($intervalFilter) {
                return is_null($intervalFilter) || $intervalFilter === $value['interval'];
            })->map(function ($value) use ($methodFilter, $numberFilter) {
                if (!is_null($methodFilter) && isset($value['tests'][$methodFilter])) {
                    $value['tests'] = [
                        $methodFilter => $value['tests'][$methodFilter],
                    ];
                }

                if (!is_null($numberFilter)) {
                    foreach (array_keys($value['tests']) as $method) {
                        if (is_array($value['tests'][$method]) && isset($value['tests'][$method][$numberFilter])) {
                            $value['tests'][$method] = [
                                $numberFilter => $value['tests'][$method][$numberFilter],
                            ];
                        }
                    }
                }

                return $value;
            })->toArray();
    }

    /**
     * @return \DateTime[]
     */
    public static function getDates(string $interval, ?string $from = null): array
    {
        $fromDate = new \DateTime(date('2021-01-01 00:00:00'));

        if (!is_null($from)) {
            $fromDate->modify($from);
        }
        $interval = preg_replace('/^in\s+/', '', $interval);
        $to = (clone $fromDate)->modify($interval);

        return [$fromDate, $to];
    }

    public static function log(string $interval, \DateTime $from, \DateTime $to): string
    {
        return sprintf(
            'interval: %s (%s - %s)',
            $interval,
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s')
        );
    }

    private function getFixtures(): array
    {
        return it(static::parseData())->map(function (array $row) {
            foreach (array_keys($row['tests']) as $method) {
                if (count($row['tests'][$method])) {
                    foreach ($row['tests'][$method] as $k => &$test) {
                        [$from, $to] = static::getDates($row['interval'], $test['from'] ?? null);
                        $test['fromToday'] = !isset($test['from']);
                        $test['fromDate'] = $from->format('c');
                        $test['toDate'] = $to->format('c');
                        $test['log'] = static::log($row['interval'], $from, $to);
                    }
                }
            }

            return $row;
        })->toArray();
    }
}
