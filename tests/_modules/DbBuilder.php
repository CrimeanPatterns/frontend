<?php

namespace Codeception\Module;

use AwardWallet\Tests\Modules\DbBuilder\AbstractDbEntity;
use AwardWallet\Tests\Modules\DbBuilder\AbstractItinerary;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\AccountShare;
use AwardWallet\Tests\Modules\DbBuilder\AirCode;
use AwardWallet\Tests\Modules\DbBuilder\Airline;
use AwardWallet\Tests\Modules\DbBuilder\Alliance;
use AwardWallet\Tests\Modules\DbBuilder\AllianceEliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\BusinessInfo;
use AwardWallet\Tests\Modules\DbBuilder\Cart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem;
use AwardWallet\Tests\Modules\DbBuilder\Country;
use AwardWallet\Tests\Modules\DbBuilder\Coupon;
use AwardWallet\Tests\Modules\DbBuilder\CreditCard;
use AwardWallet\Tests\Modules\DbBuilder\Currency;
use AwardWallet\Tests\Modules\DbBuilder\EliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\FlightStats;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\GroupUserLink;
use AwardWallet\Tests\Modules\DbBuilder\Lounge;
use AwardWallet\Tests\Modules\DbBuilder\LoungeAction;
use AwardWallet\Tests\Modules\DbBuilder\LoungeSource;
use AwardWallet\Tests\Modules\DbBuilder\LoungeSourceChange;
use AwardWallet\Tests\Modules\DbBuilder\MileValue;
use AwardWallet\Tests\Modules\DbBuilder\OwnableInterface;
use AwardWallet\Tests\Modules\DbBuilder\Parking;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCouponShare;
use AwardWallet\Tests\Modules\DbBuilder\ProviderPhone;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\RAFlight;
use AwardWallet\Tests\Modules\DbBuilder\RaFlightFullSearchStat;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightRouteSearchVolume;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchQuery;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRequest;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchResponse;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRoute;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRouteSegment;
use AwardWallet\Tests\Modules\DbBuilder\Rental;
use AwardWallet\Tests\Modules\DbBuilder\Reservation;
use AwardWallet\Tests\Modules\DbBuilder\Restaurant;
use AwardWallet\Tests\Modules\DbBuilder\SubAccount;
use AwardWallet\Tests\Modules\DbBuilder\TextEliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\TravelPlan;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent;
use AwardWallet\Tests\Modules\DbBuilder\UserPointValue;
use Codeception\Module;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DbBuilder extends Module
{
    private ?Module\CustomDb $db;

    public function _initialize()
    {
        parent::_initialize();

        /** @var Module\CustomDb $db */
        $db = $this->getModule('CustomDb');
        $this->db = $db;
    }

    public function makeUser(User $user): int
    {
        if (!$user->startMaking()) {
            return $user->getId();
        }

        $userId = $this->make($user);

        if (count($groups = $user->getGroups()) > 0) {
            foreach ($groups as $group) {
                if (!$group->isMakeable()) {
                    continue;
                }

                $this->makeGroupUserLink($group->extendFields(['UserID' => $userId]));
            }
        }

        if (count($carts = $user->getCarts()) > 0) {
            foreach ($carts as $cart) {
                if (!$cart->isMakeable()) {
                    continue;
                }

                $this->makeCart($cart->extendFields(['UserID' => $userId]));
            }
        }

        if (($info = $user->getBusinessInfo()) && $info->isMakeable()) {
            $this->makeBusinessInfo($info->extendFields(['UserID' => $userId]));
        }

        if (($pointValue = $user->getUserPointValue()) && $pointValue->isMakeable()) {
            $this->makeUserPointValue($pointValue->extendFields(['UserID' => $userId]));
        }

        $user->finishMaking();

        return $userId;
    }

    public function makeUserAgent(UserAgent $userAgent): int
    {
        if (!$userAgent->startMaking()) {
            return $userAgent->getId();
        }

        if (($agent = $userAgent->getAgent()) && $agent->isMakeable()) {
            $userAgent->extendFields(['AgentID' => $this->makeUser($agent)]);
        }

        if (($client = $userAgent->getClient()) && $client->isMakeable()) {
            $userAgent->extendFields(['ClientID' => $this->makeUser($client)]);
        }

        return $userAgent->finishMaking(fn () => $this->make($userAgent));
    }

    public function makeGroupUserLink(GroupUserLink $groupUserLink): int
    {
        if (!$groupUserLink->startMaking()) {
            return $groupUserLink->getId();
        }

        return $groupUserLink->finishMaking(fn () => $this->make($groupUserLink));
    }

    public function makeProviderCoupon(ProviderCoupon $providerCoupon): int
    {
        if (!$providerCoupon->startMaking()) {
            return $providerCoupon->getId();
        }

        $this->makeOwner($providerCoupon);

        if (($currency = $providerCoupon->getCurrency()) && $currency->isMakeable()) {
            $providerCoupon->extendFields(['CurrencyID' => $this->makeCurrency($currency)]);
        }

        if (($account = $providerCoupon->getAccount()) && $account->isMakeable()) {
            $providerCoupon->extendFields(['AccountID' => $this->makeAccount($account)]);
        }

        $providerCouponId = $this->make($providerCoupon);

        if (($couponShare = $providerCoupon->getProviderCouponShare()) && $couponShare->isMakeable()) {
            $this->makeProviderCouponShare(
                $couponShare->extendFields([
                    'ProviderCouponID' => $providerCouponId,
                ])
            );
        }

        $providerCoupon->finishMaking();

        return $providerCouponId;
    }

    public function makeAccount(Account $account): int
    {
        if (!$account->startMaking()) {
            return $account->getId();
        }

        $this->makeOwner($account);

        if (($provider = $account->getProvider()) && $provider->isMakeable()) {
            $account->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($currency = $account->getCurrency()) && $currency->isMakeable()) {
            $account->extendFields(['CurrencyID' => $this->makeCurrency($currency)]);
        }

        $accountId = $this->make($account);
        $providerId = $account->getFields()['ProviderID'] ?? null;

        if (count($props = $account->getProperties()) > 0 && !is_null($providerId)) {
            foreach ($props as $prop) {
                $prop->getProviderProperty()->extendFields(['ProviderID' => $providerId]);

                if ($prop->isMakeable()) {
                    $this->makeAccountProperty($prop->extendFields(['AccountID' => $accountId]));
                }
            }
        }

        if (count($subAccounts = $account->getSubAccounts()) > 0) {
            foreach ($subAccounts as $subAccount) {
                if (!$subAccount->isMakeable()) {
                    continue;
                }

                $this->makeSubAccount(
                    $subAccount->extendFields(['AccountID' => $accountId])
                );
            }
        }

        if (count($its = $account->getItineraries()) > 0) {
            foreach ($its as $it) {
                if (!$it->isMakeable()) {
                    continue;
                }

                $this->makeItinerary($it->extendFields(['AccountID' => $accountId]));
            }
        }

        if (($accountShare = $account->getAccountShare()) && $accountShare->isMakeable()) {
            $this->makeAccountShare(
                $accountShare->extendFields([
                    'AccountID' => $accountId,
                ])
            );
        }

        $account->finishMaking();

        return $accountId;
    }

    public function makeSubAccount(SubAccount $subAccount): int
    {
        if (!$subAccount->startMaking()) {
            return $subAccount->getId();
        }

        $subAccountId = $this->make($subAccount);

        if (count($props = $subAccount->getProperties()) > 0) {
            foreach ($props as $prop) {
                if (!$prop->isMakeable()) {
                    continue;
                }

                $this->makeAccountProperty(
                    $prop->extendFields([
                        'AccountID' => $subAccount->getFields()['AccountID'] ?? null,
                        'SubAccountID' => $subAccountId,
                    ])
                );
            }
        }

        $subAccount->finishMaking();

        return $subAccountId;
    }

    public function makeProvider(Provider $provider): int
    {
        if (!$provider->startMaking()) {
            return $provider->getId();
        }

        $providerId = $this->make($provider);

        if (count($props = $provider->getProperties()) > 0) {
            foreach ($props as $prop) {
                if (!$prop->isMakeable()) {
                    continue;
                }

                $this->makeProviderProperty($prop->extendFields(['ProviderID' => $providerId]));
            }
        }

        if (($pointValue = $provider->getUserPointValue()) && $pointValue->isMakeable()) {
            $this->makeUserPointValue($pointValue->extendFields(['ProviderID' => $providerId]));
        }

        $provider->finishMaking();

        return $providerId;
    }

    public function makeProviderProperty(ProviderProperty $providerProperty): int
    {
        if (!$providerProperty->startMaking()) {
            return $providerProperty->getId();
        }

        return $providerProperty->finishMaking(fn () => $this->make($providerProperty));
    }

    public function makeAccountProperty(AccountProperty $property): int
    {
        if (!$property->startMaking()) {
            return $property->getId();
        }

        if (($providerProperty = $property->getProviderProperty()) && $providerProperty->isMakeable()) {
            $property->extendFields(['ProviderPropertyID' => $this->makeProviderProperty($providerProperty)]);
        }

        return $property->finishMaking(fn () => $this->make($property));
    }

    public function makeCurrency(Currency $currency): int
    {
        if (!$currency->startMaking()) {
            return $currency->getId();
        }

        return $currency->finishMaking(fn () => $this->make($currency));
    }

    public function makeTravelPlan(TravelPlan $travelPlan): int
    {
        if (!$travelPlan->startMaking()) {
            return $travelPlan->getId();
        }

        $this->makeOwner($travelPlan);

        return $travelPlan->finishMaking(fn () => $this->make($travelPlan));
    }

    public function makeBusinessInfo(BusinessInfo $businessInfo): int
    {
        if (!$businessInfo->startMaking()) {
            return $businessInfo->getId();
        }

        return $businessInfo->finishMaking(fn () => $this->make($businessInfo));
    }

    public function makeTripSegment(TripSegment $tripSegment): int
    {
        if (!$tripSegment->startMaking()) {
            return $tripSegment->getId();
        }

        if (($trip = $tripSegment->getTrip()) && $trip->isMakeable()) {
            $tripSegment->extendFields(['TripID' => $this->makeTrip($trip)]);
        }

        if (($depGeoTag = $tripSegment->getDepGeoTag()) && $depGeoTag->isMakeable()) {
            $tripSegment->extendFields(['DepGeoTagID' => $this->makeGeoTag($depGeoTag)]);
        }

        if (($arrGeoTag = $tripSegment->getArrGeoTag()) && $arrGeoTag->isMakeable()) {
            $tripSegment->extendFields(['ArrGeoTagID' => $this->makeGeoTag($arrGeoTag)]);
        }

        if (($depAirCode = $tripSegment->getDepAirCode()) && $depAirCode->isMakeable()) {
            $tripSegment->extendFields(['DepCode' => $this->makeAirCode($depAirCode)]);
        }

        if (($arrAirCode = $tripSegment->getArrAirCode()) && $arrAirCode->isMakeable()) {
            $tripSegment->extendFields(['ArrCode' => $this->makeAirCode($arrAirCode)]);
        }

        if (($airline = $tripSegment->getAirline()) && $airline->isMakeable()) {
            $tripSegment->extendFields(['AirlineID' => $this->makeAirline($airline)]);
        }

        if (($operatingAirline = $tripSegment->getOperatingAirline()) && $operatingAirline->isMakeable()) {
            $tripSegment->extendFields(['OperatingAirlineID' => $this->makeAirline($operatingAirline)]);
        }

        if (($wetLeaseAirline = $tripSegment->getWetLeaseAirline()) && $wetLeaseAirline->isMakeable()) {
            $tripSegment->extendFields(['WetLeaseAirlineID' => $this->makeAirline($wetLeaseAirline)]);
        }

        return $tripSegment->finishMaking(fn () => $this->make($tripSegment));
    }

    public function makeTrip(Trip $trip): int
    {
        if (!$trip->startMaking()) {
            return $trip->getId();
        }

        $this->makeCommonItinerary($trip);
        $tripId = $this->make($trip);

        foreach ($trip->getSegments() as $segment) {
            if (!$segment->isMakeable()) {
                continue;
            }

            $this->makeTripSegment(
                $segment->extendFields(['TripID' => $tripId])
            );
        }

        $trip->finishMaking();

        return $tripId;
    }

    public function makeRental(Rental $rental): int
    {
        if (!$rental->startMaking()) {
            return $rental->getId();
        }

        $this->makeCommonItinerary($rental);

        if (($pickupGeoTag = $rental->getPickupGeoTag()) && $pickupGeoTag->isMakeable()) {
            $rental->extendFields(['PickupGeoTagID' => $this->makeGeoTag($pickupGeoTag)]);
        }

        if (($dropoffGeoTag = $rental->getDropoffGeoTag()) && $dropoffGeoTag->isMakeable()) {
            $rental->extendFields(['DropoffGeoTagID' => $this->makeGeoTag($dropoffGeoTag)]);
        }

        return $rental->finishMaking(fn () => $this->make($rental));
    }

    public function makeReservation(Reservation $reservation): int
    {
        if (!$reservation->startMaking()) {
            return $reservation->getId();
        }

        $this->makeCommonItinerary($reservation);

        if (($geoTag = $reservation->getGeoTag()) && $geoTag->isMakeable()) {
            $reservation->extendFields(['GeoTagID' => $this->makeGeoTag($geoTag)]);
        }

        return $reservation->finishMaking(fn () => $this->make($reservation));
    }

    public function makeRestaurant(Restaurant $restaurant): int
    {
        if (!$restaurant->startMaking()) {
            return $restaurant->getId();
        }

        $this->makeCommonItinerary($restaurant);

        if (($geoTag = $restaurant->getGeoTag()) && $geoTag->isMakeable()) {
            $restaurant->extendFields(['GeoTagID' => $this->makeGeoTag($geoTag)]);
        }

        return $restaurant->finishMaking(fn () => $this->make($restaurant));
    }

    public function makeParking(Parking $parking): int
    {
        if (!$parking->startMaking()) {
            return $parking->getId();
        }

        $this->makeCommonItinerary($parking);

        if (($geoTag = $parking->getGeoTag()) && $geoTag->isMakeable()) {
            $parking->extendFields(['GeoTagID' => $this->makeGeoTag($geoTag)]);
        }

        return $parking->finishMaking(fn () => $this->make($parking));
    }

    public function makeGeoTag(GeoTag $geoTag): int
    {
        if (!$geoTag->startMaking()) {
            return $geoTag->getId();
        }

        return $geoTag->finishMaking(fn () => $this->make($geoTag));
    }

    public function makeAirCode(AirCode $airCode): string
    {
        if (!$airCode->startMaking()) {
            return $airCode->getFields()['AirCode'];
        }

        $airCodeId = $this->make($airCode);

        return $airCode->finishMaking(fn () => $this->db->grabFromDatabase('AirCode', 'AirCode', ['AirCodeID' => $airCodeId]));
    }

    public function makeAirline(Airline $airline): int
    {
        if (!$airline->startMaking()) {
            return $airline->getId();
        }

        return $airline->finishMaking(fn () => $this->make($airline));
    }

    public function makeUserPointValue(UserPointValue $userPointValue): int
    {
        if (!$userPointValue->startMaking()) {
            return $userPointValue->getId();
        }

        if (($user = $userPointValue->getUser()) && $user->isMakeable()) {
            $userPointValue->extendFields(['UserID' => $this->makeUser($user)]);
        }

        if (($provider = $userPointValue->getProvider()) && $provider->isMakeable()) {
            $userPointValue->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        return $userPointValue->finishMaking(fn () => $this->make($userPointValue));
    }

    public function makeAccountShare(AccountShare $accountShare): int
    {
        if (!$accountShare->startMaking()) {
            return $accountShare->getId();
        }

        if (($connection = $accountShare->getConnection()) && $connection->isMakeable()) {
            $accountShare->extendFields(['UserAgentID' => $this->makeUserAgent($connection)]);
        }

        if (($account = $accountShare->getAccount()) && $account->isMakeable()) {
            $accountShare->extendFields(['AccountID' => $this->makeAccount($account)]);
        }

        return $accountShare->finishMaking(fn () => $this->make($accountShare));
    }

    public function makeProviderCouponShare(ProviderCouponShare $providerCouponShare): int
    {
        if (!$providerCouponShare->startMaking()) {
            return $providerCouponShare->getId();
        }

        if (($connection = $providerCouponShare->getConnection()) && $connection->isMakeable()) {
            $providerCouponShare->extendFields(['UserAgentID' => $this->makeUserAgent($connection)]);
        }

        if (($providerCoupon = $providerCouponShare->getProviderCoupon()) && $providerCoupon->isMakeable()) {
            $providerCouponShare->extendFields(['ProviderCouponID' => $this->makeProviderCoupon($providerCoupon)]);
        }

        return $providerCouponShare->finishMaking(fn () => $this->make($providerCouponShare));
    }

    public function makeItinerary(AbstractItinerary $itinerary): int
    {
        if ($itinerary instanceof Trip) {
            return $this->makeTrip($itinerary);
        } elseif ($itinerary instanceof Reservation) {
            return $this->makeReservation($itinerary);
        } elseif ($itinerary instanceof Restaurant) {
            return $this->makeRestaurant($itinerary);
        } elseif ($itinerary instanceof Rental) {
            return $this->makeRental($itinerary);
        } elseif ($itinerary instanceof Parking) {
            return $this->makeParking($itinerary);
        }

        throw new \InvalidArgumentException('Unknown itinerary type');
    }

    public function makeFlightStats(FlightStats $flightStats): array
    {
        if (!$flightStats->startMaking()) {
            return $flightStats->getId();
        }

        return $flightStats->finishMaking(fn () => $this->make($flightStats));
    }

    public function makeLounge(Lounge $lounge): int
    {
        if (!$lounge->startMaking()) {
            return $lounge->getId();
        }

        $loungeId = $this->make($lounge);

        foreach ($lounge->getLoungeActions() as $action) {
            if (!$action->isMakeable()) {
                continue;
            }

            $this->makeLoungeAction(
                $action->extendFields(['LoungeID' => $loungeId])
            );
        }

        $lounge->finishMaking();

        return $loungeId;
    }

    public function makeLoungeSource(LoungeSource $loungeSource): int
    {
        if (!$loungeSource->startMaking()) {
            return $loungeSource->getId();
        }

        if (($lounge = $loungeSource->getLounge()) && $lounge->isMakeable()) {
            $loungeSource->extendFields(['LoungeID' => $this->makeLounge($lounge)]);
        }

        $loungeSourceId = $this->make($loungeSource);

        foreach ($loungeSource->getLoungeSourceChanges() as $change) {
            if (!$change->isMakeable()) {
                continue;
            }

            $this->makeLoungeSourceChange(
                $change->extendFields(['LoungeSourceID' => $loungeSourceId])
            );
        }

        $loungeSource->finishMaking();

        return $loungeSourceId;
    }

    public function makeLoungeAction(LoungeAction $loungeAction): int
    {
        if (!$loungeAction->startMaking()) {
            return $loungeAction->getId();
        }

        return $loungeAction->finishMaking(fn () => $this->make($loungeAction));
    }

    public function makeLoungeSourceChange(LoungeSourceChange $loungeSourceChange): int
    {
        if (!$loungeSourceChange->startMaking()) {
            return $loungeSourceChange->getId();
        }

        return $loungeSourceChange->finishMaking(fn () => $this->make($loungeSourceChange));
    }

    public function makeCreditCard(CreditCard $creditCard): int
    {
        if (!$creditCard->startMaking()) {
            return $creditCard->getId();
        }

        if (($provider = $creditCard->getProvider()) && $provider->isMakeable()) {
            $creditCard->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        return $creditCard->finishMaking(fn () => $this->make($creditCard));
    }

    public function makeCart(Cart $cart): int
    {
        if (!$cart->startMaking()) {
            return $cart->getId();
        }

        $cartId = $this->make($cart);

        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isMakeable()) {
                continue;
            }

            $this->makeCartItem(
                $cartItem->extendFields(['CartID' => $cartId])
            );
        }

        $cart->finishMaking();

        return $cartId;
    }

    public function makeCartItem(CartItem $cartItem): int
    {
        if (!$cartItem->startMaking()) {
            return $cartItem->getId();
        }

        return $cartItem->finishMaking(fn () => $this->make($cartItem));
    }

    public function makeCoupon(Coupon $coupon): int
    {
        if (!$coupon->startMaking()) {
            return $coupon->getId();
        }

        return $coupon->finishMaking(fn () => $this->make($coupon));
    }

    public function makeRAFlightRouteSearchVolume(RAFlightRouteSearchVolume $flightRouteSearchVolume): int
    {
        if (!$flightRouteSearchVolume->startMaking()) {
            return $flightRouteSearchVolume->getId();
        }

        if (($provider = $flightRouteSearchVolume->getProvider()) && $provider->isMakeable()) {
            $flightRouteSearchVolume->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        return $flightRouteSearchVolume->finishMaking(fn () => $this->make($flightRouteSearchVolume));
    }

    public function makeRaFlightFullSearchStat(RaFlightFullSearchStat $flightFullSearchStat): array
    {
        if (!$flightFullSearchStat->startMaking()) {
            return $flightFullSearchStat->getId();
        }

        return $flightFullSearchStat->finishMaking(fn () => $this->make($flightFullSearchStat));
    }

    public function makeMileValue(MileValue $mileValue): int
    {
        if (!$mileValue->startMaking()) {
            return $mileValue->getId();
        }

        if (($provider = $mileValue->getProvider()) && $provider->isMakeable()) {
            $mileValue->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($trip = $mileValue->getTrip()) && $trip->isMakeable()) {
            $mileValue->extendFields(['TripID' => $this->makeTrip($trip)]);
        }

        return $mileValue->finishMaking(fn () => $this->make($mileValue));
    }

    public function makeRAFlightSearchQuery(RAFlightSearchQuery $query): int
    {
        if (!$query->startMaking()) {
            return $query->getId();
        }

        if (($user = $query->getUser()) && $user->isMakeable()) {
            $query->extendFields(['UserID' => $this->makeUser($user)]);
        }

        if (($mileValue = $query->getMileValue()) && $mileValue->isMakeable()) {
            $query->extendFields(['MileValueID' => $this->makeMileValue($mileValue)]);
        }

        $queryId = $this->make($query);

        if (count($routes = $query->getRoutes()) > 0) {
            foreach ($routes as $route) {
                if (!$route->isMakeable()) {
                    continue;
                }

                $this->makeRAFlightSearchRoute(
                    $route->extendFields(['RAFlightSearchQueryID' => $queryId])
                );
            }
        }

        $query->finishMaking();

        return $queryId;
    }

    public function makeRAFlightSearchRequest(RAFlightSearchRequest $request): string
    {
        if (!$request->startMaking()) {
            return $request->getId();
        }

        if (($query = $request->getRAFlightSearchQuery()) && $query->isMakeable()) {
            $request->extendFields(['RAFlightSearchQueryID' => $this->makeRAFlightSearchQuery($query)]);
        }

        return $request->finishMaking(fn () => $this->make($request));
    }

    public function makeRAFlightSearchResponse(RAFlightSearchResponse $response): array
    {
        if (!$response->startMaking()) {
            return $response->getId();
        }

        if (($request = $response->getRAFlightSearchRequest()) && $request->isMakeable()) {
            $response->extendFields(['RAFlightSearchRequestID' => $this->makeRAFlightSearchRequest($request)]);
        }

        if (($route = $response->getRAFlightSearchRoute()) && $route->isMakeable()) {
            $response->extendFields(['RAFlightSearchRouteID' => $this->makeRAFlightSearchRoute($route)]);
        }

        return $response->finishMaking(fn () => $this->make($response));
    }

    public function makeRAFlightSearchRoute(RAFlightSearchRoute $route): int
    {
        if (!$route->startMaking()) {
            return $route->getId();
        }

        if (($query = $route->getRAFlightSearchQuery()) && $query->isMakeable()) {
            $route->extendFields(['RAFlightSearchQueryID' => $this->makeRAFlightSearchQuery($query)]);
        }

        $routeId = $this->make($route);

        if (count($segments = $route->getSegments()) > 0) {
            foreach ($segments as $segment) {
                if (!$segment->isMakeable()) {
                    continue;
                }

                $this->makeRAFlightSearchRouteSegment(
                    $segment->extendFields(['RAFlightSearchRouteID' => $routeId])
                );
            }
        }

        $route->finishMaking();

        return $routeId;
    }

    public function makeRAFlightSearchRouteSegment(RAFlightSearchRouteSegment $routeSegment): int
    {
        if (!$routeSegment->startMaking()) {
            return $routeSegment->getId();
        }

        if (($route = $routeSegment->getRAFlightSearchRoute()) && $route->isMakeable()) {
            $routeSegment->extendFields(['RAFlightSearchRouteID' => $this->makeRAFlightSearchRoute($route)]);
        }

        return $routeSegment->finishMaking(fn () => $this->make($routeSegment));
    }

    public function makeCountry(Country $country): int
    {
        if (!$country->startMaking()) {
            return $country->getId();
        }

        return $country->finishMaking(fn () => $this->make($country));
    }

    public function makeAlliance(Alliance $alliance): int
    {
        if (!$alliance->startMaking()) {
            return $alliance->getId();
        }

        return $alliance->finishMaking(fn () => $this->make($alliance));
    }

    public function makeAllianceEliteLevel(AllianceEliteLevel $allianceEliteLevel): int
    {
        if (!$allianceEliteLevel->startMaking()) {
            return $allianceEliteLevel->getId();
        }

        if (($alliance = $allianceEliteLevel->getAlliance()) && $alliance->isMakeable()) {
            $allianceEliteLevel->extendFields(['AllianceID' => $this->makeAlliance($alliance)]);
        }

        return $allianceEliteLevel->finishMaking(fn () => $this->make($allianceEliteLevel));
    }

    public function makeEliteLevel(EliteLevel $eliteLevel): int
    {
        if (!$eliteLevel->startMaking()) {
            return $eliteLevel->getId();
        }

        if (($provider = $eliteLevel->getProvider()) && $provider->isMakeable()) {
            $eliteLevel->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($allianceEliteLevel = $eliteLevel->getAllianceEliteLevel()) && $allianceEliteLevel->isMakeable()) {
            $eliteLevel->extendFields(['AllianceEliteLevelID' => $this->makeAllianceEliteLevel($allianceEliteLevel)]);
        }

        $id = $this->make($eliteLevel);

        if (count($texts = $eliteLevel->getValueTexts()) > 0) {
            foreach ($texts as $text) {
                if (!$text->isMakeable()) {
                    continue;
                }

                $this->makeTextEliteLevel(
                    $text->extendFields(['EliteLevelID' => $id])
                );
            }
        }

        $eliteLevel->finishMaking();

        return $id;
    }

    public function makeTextEliteLevel(TextEliteLevel $textEliteLevel): int
    {
        if (!$textEliteLevel->startMaking()) {
            return $textEliteLevel->getId();
        }

        if (($eliteLevel = $textEliteLevel->getEliteLevel()) && $eliteLevel->isMakeable()) {
            $textEliteLevel->extendFields(['EliteLevelID' => $this->makeEliteLevel($eliteLevel)]);
        }

        return $textEliteLevel->finishMaking(fn () => $this->make($textEliteLevel));
    }

    public function makeProviderPhone(ProviderPhone $providerPhone): int
    {
        if (!$providerPhone->startMaking()) {
            return $providerPhone->getId();
        }

        if (($checkedBy = $providerPhone->getCheckedBy()) && $checkedBy->isMakeable()) {
            $providerPhone->extendFields(['CheckedBy' => $this->makeUser($checkedBy)]);
        }

        if (($provider = $providerPhone->getProvider()) && $provider->isMakeable()) {
            $providerPhone->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($eliteLevel = $providerPhone->getEliteLevel()) && $eliteLevel->isMakeable()) {
            $providerPhone->extendFields(['EliteLevelID' => $this->makeEliteLevel($eliteLevel)]);
        }

        if (($country = $providerPhone->getCountry()) && $country->isMakeable()) {
            $providerPhone->extendFields(['CountryID' => $this->makeCountry($country)]);
        }

        return $providerPhone->finishMaking(fn () => $this->make($providerPhone));
    }

    public function makeRAFlight(RAFlight $flight): int
    {
        if (!$flight->startMaking()) {
            return $flight->getId();
        }

        return $flight->finishMaking(fn () => $this->make($flight));
    }

    private function makeCommonItinerary(AbstractItinerary $itinerary)
    {
        $this->makeOwner($itinerary);

        if (($provider = $itinerary->getProvider()) && $provider->isMakeable()) {
            $itinerary->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($travelAgency = $itinerary->getTravelAgency()) && $travelAgency->isMakeable()) {
            $itinerary->extendFields(['TravelAgencyID' => $this->makeProvider($travelAgency)]);
        }

        if (($account = $itinerary->getAccount()) && $account->isMakeable()) {
            $itinerary->extendFields(['AccountID' => $this->makeAccount($account)]);
        }

        if (($travelPlan = $itinerary->getTravelPlan()) && $travelPlan->isMakeable()) {
            $itinerary->extendFields(['TravelPlanID' => $this->makeTravelPlan($travelPlan)]);
        }
    }

    /**
     * @param OwnableInterface|AbstractDbEntity $entity
     */
    private function makeOwner(OwnableInterface $entity)
    {
        $user = $entity->getUser();

        if ($user instanceof User && $user->isMakeable()) {
            $userId = $this->makeUser($user);
            $entity->extendFields(['UserID' => $userId]);
        } elseif ($user instanceof UserAgent && $user->isMakeable()) {
            $userAgentId = $this->makeUserAgent($user);
            $entity->extendFields([
                'UserID' => $user->getAgent()->getId(),
                'UserAgentID' => $userAgentId,
            ]);
        }
    }

    /**
     * @return mixed Primary key
     */
    private function make(AbstractDbEntity $dbEntity)
    {
        $fields = $dbEntity->getFields();
        $table = $dbEntity->getTableName();
        $pk = is_array($dbEntity->getPrimaryKey()) ? $dbEntity->getPrimaryKey() : [$dbEntity->getPrimaryKey()];
        $id = array_map(static function ($key) use ($fields) {
            return $fields[$key] ?? null;
        }, array_combine($pk, $pk));
        $fieldsWithoutPk = array_diff_key($fields, $id);
        $countIdFields = count($id);

        if (
            $countIdFields > 1
            && array_search(null, $id) !== false
            && count(array_filter($id, static function ($value) {
                return !is_null($value);
            })) > 0
        ) {
            throw new \InvalidArgumentException('Not all primary key fields are set');
        }

        $idNotNull = array_search(null, $id) === false;
        $foundInDatabase = false;

        if ($idNotNull) {
            // try find in database by primary key
            $foundInDatabase = $this->selectFromDb($pk, $table, $id) !== false;
        }

        if (!$foundInDatabase) {
            // try find in database by unique index
            $idByIndex = $this->findIdByUniqueIndex($pk, $table, $fields);

            if (!is_null($idByIndex)) {
                $id = $idByIndex;
                $idNotNull = true;
                $foundInDatabase = true;
            }
        }

        if ($foundInDatabase) {
            $this->db->updateInDatabase($table, $fieldsWithoutPk, $id);
        } else {
            $lastId = $this->db->haveInDatabase($table, $fields);

            if (!$idNotNull) {
                if ($lastId == 0) {
                    throw new \InvalidArgumentException('Primary key is not autoincrement');
                }

                $id = [$pk[0] => $lastId];
            }
        }

        $dbEntity->extendFields($id);

        if ($countIdFields === 1) {
            return reset($id);
        }

        return array_values($id);
    }

    /**
     * @return array|false
     */
    private function selectFromDb(array $select, string $from, array $where)
    {
        $select = implode(', ', $select);
        $where = it($where)
            ->mapIndexed(static function ($value, $field) {
                return sprintf("%s = '%s'", $field, addslashes($value));
            })
            ->joinToString(' AND ');

        return $this->db->query("SELECT $select FROM $from WHERE $where")->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string[] $pk
     */
    private function findIdByUniqueIndex(array $pk, string $table, array $fields): ?array
    {
        $indexes = $this->getUniqueIndexes($table);

        foreach ($indexes as $index) {
            if (count(array_diff($index, array_keys($fields))) === 0) {
                $indexFields = [];

                foreach ($index as $fieldName) {
                    $indexFields[$fieldName] = $fields[$fieldName];
                }

                if (array_search(null, $indexFields) !== false) {
                    continue;
                }

                $idFromDb = $this->selectFromDb($pk, $table, $indexFields);

                if ($idFromDb !== false) {
                    return $idFromDb;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<int, string>> Unique indexes
     */
    private function getUniqueIndexes(string $table): array
    {
        $q = $this->db->query("SHOW INDEX FROM $table");
        $indexes = [];

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['Key_name'] === 'PRIMARY' || $row['Non_unique']) {
                continue;
            }

            if (!isset($indexes[$row['Key_name']])) {
                $indexes[$row['Key_name']] = [];
            }

            $indexes[$row['Key_name']][$row['Seq_in_index']] = $row['Column_name'];
        }

        return $indexes;
    }
}
