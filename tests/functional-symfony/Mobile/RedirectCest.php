<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\MobileRouteListener\Routes;
use Codeception\Scenario;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group frontend-functional
 * @group mobile
 */
class RedirectCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function desktopLinksShouldRedirectToMobile(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $examples = [];

        foreach (Routes::METHOD_MAP as $routeName => $routeData) {
            if (count($routeData) === 0) {
                $method = null;
                $params = [];
            } elseif (count($routeData) === 1) {
                $params = [];
                $method = $routeData[0];
            } else {
                [$method, $params] = $routeData;
            }

            if (null === $method) {
                $method = 'route_' . $routeName;
            }

            $examples[] = [$method, $routeName, $params];
        }

        $I->haveHttpHeader('User-Agent', 'Iphone 7');

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $I->grabService('router');
        $I->followRedirects(false);
        $I->specify('mobile redirects',
            function ($method, $routeName, $params) use ($I, $urlGenerator) {
                if (isset($params[0]) && is_array($params[0])) {
                    $testCases = $params;
                } else {
                    $testCases[] = $params;
                }

                foreach ($testCases as $testCaseParams) {
                    $fqcnMethod = Routes::class . '::' . $method;
                    $I->sendGET($urlGenerator->generate($routeName, $testCaseParams), $testCaseParams);
                    $I->seeRedirectTo(call_user_func($fqcnMethod, $testCaseParams));
                }
            },
            ['examples' => $examples]
        );
    }
}
