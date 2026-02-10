<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringUtils;
use Codeception\Example;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Symfony\Component\Routing\Route;

class RoutesAccessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataprovider scenariosProvider
     */
    public function testAccess(\TestSymfonyGuy $I, Example $scenario)
    {
        $victimId = $I->createAwUser();
        $attackerLogin = StringUtils::getRandomCode(10);
        $attackerId = $I->createAwUser($attackerLogin);

        $accountLogin = StringUtils::getRandomCode(10);
        $entities = [
            'account' => ['id' => $I->createAwAccount($victimId, "aa", $accountLogin), 'lookFor' => $accountLogin],
        ];

        $conditions = [];

        if (!empty($scenario['victimAsClientConnection'])) {
            $clientConnectionId = $this->createConnection($I, $scenario['victimAsClientConnection'], $victimId, $attackerId);

            if ($scenario['victimAsClientConnection']['approved']) {
                $conditions[] = 'victimAsClientConnection.approved';
            }

            if (!empty($scenario['sharedAccount'])) {
                $I->shareAwAccountByConnection($entities['account']['id'], $clientConnectionId);
                $conditions[] = 'sharedAccount';
            }
        }

        if (!empty($scenario['victimAsAgentConnection'])) {
            $this->createConnection($I, $scenario['victimAsAgentConnection'], $attackerId, $victimId);
        }

        $router = $I->getContainer()->get("router");

        foreach ($this->getRoutes($I) as $route) {
            $params = [
                $route["param"] => $entities[$route['entity']]['id'],
                "_switch_user" => $attackerLogin,
            ];
            $url = $router->generate($route["name"], $params);
            $I->amOnPage($url);
            $allowed = empty(array_diff($route['conditions'], $conditions));
            $lookFor = $entities[$route['entity']]['lookFor'];

            if ($allowed) {
                $I->seeInSource($lookFor);
            } else {
                $I->dontSeeInSource($lookFor);
            }
        }
    }

    private function createConnection(\TestSymfonyGuy $I, array $connectionInfo, $clientId, $agentId)
    {
        return $I->createConnection(
            $clientId,
            $agentId,
            $connectionInfo['approved'],
            false,
            ['AccessLevel' => $connectionInfo['accessLevel']]
        );
    }

    private function scenariosProvider()
    {
        return [
            [
                'name' => 'no_connection',
            ],
            [
                'name' => 'approved_shared_full',
                'victimAsClientConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_WRITE],
                'victimAsAgentConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_NONE],
                'sharedAccount' => true,
            ],
            [
                'name' => 'approved',
                'victimAsClientConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_WRITE],
                'victimAsAgentConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_NONE],
            ],
            [
                'name' => 'approved_shared',
                'victimAsClientConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_READ_BALANCE_AND_STATUS],
                'victimAsAgentConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_NONE],
                'sharedAccount' => true,
            ],
            [
                'name' => 'approved_shared',
                'victimAsClientConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_WRITE],
                'victimAsAgentConnection' => ["approved" => true, 'accessLevel' => Useragent::ACCESS_NONE],
                'sharedAccount' => true,
            ],
        ];
    }

    private function getRoutes(\TestSymfonyGuy $I)
    {
        $router = $I->getContainer()->get("router");
        $routes = $router->getRouteCollection();
        $result = [];
        $excludePaths = [
            '/aa/catch-form', '/advertise', '/auth/capitalcards', '/barcode/',
            '/contact', '/logo/', '/engine/', '/google/', '/api/', '/iCal/', '/error/',
            '/manager/', '/offer/', '/page/', '/{_locale}/', '/pr/',
            '/user/notifications/', '/user/change-password', '/promos/',
            '/callback/tripalert/', '/upload/', '/awardBooking/shareResendMail',
            '/m/api/',
            '/js/routing/', '/translations/',
            '/trips/gcmap/', '/user/connections/{ID}',
        ];
        $excludeParams = [
            'query', 'action', '_locale', 'link', 'hash', 'shareCode', 'name', 'path', 'offerUserId', 'code',
            'providerId', 'providerid', 'type', 'username', 'betaInvite', 'deviceToken', 'provider',
        ];
        $paramToEntityMap = [
            'accountId' => 'account',
            'subAccountId' => 'subAccount',
            'cardImageId' => 'card',
            'couponId' => 'coupon',
            'userAgentId' => 'userAgent',
            'agentId' => 'userAgent',
            'segmentId' => 'timelineSegment',
            'itCode' => 'timelineSegment',
            'itIds' => 'timelineSegment',
        ];
        $pathToEntityMap = [
            '/account/' => 'account',
            '/cart/' => 'cart',
            '/trips/edit/flight' => 'trip.flight',
            '/trips/edit/reservation' => 'reservation',
            '/plan/' => 'plan',
            '/trips/edit/' => 'timelineSegment',
            '/awardBooking/edit/' => 'bookingRequest',
            '/awardBooking/getViewUpdates/' => 'bookingRequest',
            '/awardBooking/getMessages/' => 'bookingRequest',
            '/awardBooking/add_message_ajax/' => 'bookingRequest',
            '/awardBooking/add_message/' => 'bookingRequest',
            '/awardBooking/edit_message_ajax/' => 'bookingRequest',
            '/awardBooking/delete_message_ajax/' => 'bookingRequest',
            '/awardBooking/create_invoice/' => 'bookingRequest',
            '/awardBooking/seat_assignments/' => 'bookingRequest',
            '/awardBooking/payment/' => 'bookingRequest',
            '/awardBooking/view/' => 'bookingRequest',
        ];
        $unknown = [];
        $annotationReader = new AnnotationReader();

        foreach ($routes as $name => $route) {
            /** @var Route $route */
            if (
                preg_match_all('#\{([^\}]+)\}#ims', $route->getPath(), $matches)
                && (empty(array_intersect($matches[1], $excludeParams)) || count($matches[1]) > 1)
                && !$this->startsWith($excludePaths, $route->getPath())
            ) {
                $entity = null;

                if ($route->hasDefault('_controller')) {
                    $pair = explode("::", $route->getDefault('_controller'));

                    if (count($pair) == 2) {
                        [$class, $method] = $pair;
                        $reflecton = new \ReflectionMethod($class, $method);
                        $reflecton->getDocComment();
                        $secureParam = $annotationReader->getMethodAnnotation($reflecton, SecureParam::class);

                        if (!empty($secureParam)) {
                            continue;
                        }
                    }
                }

                foreach ($paramToEntityMap as $param => $detectedEntity) {
                    if (in_array($param, $matches[1])) {
                        $entity = $detectedEntity;

                        break;
                    }
                }

                if (empty($entity)) {
                    foreach ($pathToEntityMap as $path => $detectedEntity) {
                        if (strpos($route->getPath(), $path) === 0) {
                            $entity = $detectedEntity;

                            break;
                        }
                    }
                }

                if (empty($entity)) {
                    $unknown[] = $route->getPath();

                    continue;
                }
                $result[] = [
                    'entity' => $entity,
                    'url' => $route->getPath(),
                ];
            }
        }
        $I->assertEmpty($unknown);
        var_dump($result);

        return [
            [
                'method' => 'GET',
                'name' => 'aw_account_edit',
                'entity' => 'account',
                'param' => 'accountId',
                'conditions' => ['victimAsClientConnection.approved', 'sharedAccount'],
            ],
        ];
    }

    private function startsWith(array $prefixes, $route)
    {
        foreach ($prefixes as $prefix) {
            if (strpos($route, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}
