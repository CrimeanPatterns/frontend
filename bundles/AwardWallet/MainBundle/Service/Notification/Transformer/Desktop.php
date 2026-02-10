<?php

namespace AwardWallet\MainBundle\Service\Notification\Transformer;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\PushNotification\Target\ItineraryUpdate;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\AlertTarget;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\TransformedContent;
use AwardWallet\MainBundle\Service\Notification\TransformerInterface;
use AwardWallet\MainBundle\Service\Notification\Unsubscriber;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Symfony\Component\Routing\RouterInterface;

class Desktop implements TransformerInterface
{
    private RouterInterface $router;

    private Unsubscriber $unsubscriber;

    private string $personalHost;

    private string $businessHost;

    public function __construct(
        RouterInterface $router,
        Unsubscriber $unsubscriber,
        string $host,
        string $businessHost
    ) {
        $this->router = $router;
        $this->unsubscriber = $unsubscriber;
        $this->personalHost = $host;
        $this->businessHost = $businessHost;
    }

    public function transform(MobileDevice $device, Content $content): ?TransformedContent
    {
        $target = $content->target;

        if (\is_string($target)) {
            $target = TransformerUtils::transformRefcode($device->getUser(), $target);
        }

        $debugUrl = null;
        $debugHost = null;

        if (in_array($content->type, [Content::TYPE_BLOG_POST, Content::TYPE_PRODUCT_UPDATES, Content::TYPE_OFFER])) {
            $url = $target;
        } elseif (!is_empty($target)) {
            if (is_object($target)) {
                $targetClass = get_class($target);

                if ($target instanceof AbMessage) {
                    $debugHost = $target->getFromBooker() ? $this->personalHost : $this->businessHost;
                    $url = $this->router->generate('aw_booking_view_index', ['id' => $target->getRequest()->getAbRequestID()]) . '#' . $target->getAbMessageID();
                }

                if ($target instanceof AlertTarget) {
                    $target = $target->tripSegment;
                }

                if ($target instanceof Tripsegment) {
                    $url = $this->router->generate('aw_timeline_show', [
                        'segmentId' => TransformerUtils::getTimelineKindByEntity($target) . '.' . $target->getId(),
                    ]);
                }

                if ($target instanceof Account) {
                    $url = $this->router->generate('aw_account_list', ['account' => $target->getAccountid()]);
                }

                if ($target instanceof Subaccount) {
                    $url = $this->router->generate('aw_account_list', [
                        'account' => $target->getAccountid()->getAccountid(),
                        'subaccount' => $target->getId(),
                    ]);
                }

                if ($target instanceof Providercoupon) {
                    $url = $this->router->generate('aw_account_list', ['coupon' => $target->getProvidercouponid()]);
                }

                if ($target instanceof ItineraryUpdate) {
                    $url = $this->getTimelineRoute($target->getEntity());
                    $targetClass = get_class($target->getEntity());
                }

                if (empty($url)) {
                    throw new \Exception("Unknown target type: " . get_class($target));
                }
            } else {
                switch ($target) {
                    case Content::TARGET_PAY:
                        $url = $this->router->generate('aw_users_pay');

                        break;

                    default:
                        throw new \Exception("Unknown target type: " . $target);
                }
            }
        } else {
            $url = "/";
        }

        $routerContext = $this->router->getContext();

        if (isset($url)) {
            $debugUrl = sprintf(
                "%s://%s%s",
                $routerContext->getScheme(),
                $debugHost ?? $routerContext->getHost(),
                $url
            );
        }
        $options = $content->options ?: new Options();
        $options->setLogContext(array_merge(
            $options->getLogContext(),
            [
                'debug' => [
                    'title' => $content->title,
                    'message' => $content->message,
                    'url' => $debugUrl,
                ],
                '_aw_push_subtype' => (isset($targetClass) && ($classParts = explode('\\', $targetClass))) ?
                    end($classParts) : null,
            ]
        ));

        return new TransformedContent(
            $content->title,
            [
                "body" => $content->message,
                "url" => $this->unsubscriber->addUnsubscribeCode($device, $url),
            ],
            Content::getTypeName($content->type),
            $options
        );
    }

    /**
     * @param object $entity
     * @return string
     */
    public function getTimelineRoute($entity)
    {
        if ($entity instanceof Trip) {
            return $this->router->generate('aw_timeline_show_trip', ['tripId' => $entity->getId()]);
        } elseif ($entity instanceof Itinerary || $entity instanceof Tripsegment) {
            return $this->router->generate('aw_timeline_show', [
                'segmentId' => TransformerUtils::getTimelineKindByEntity($entity) . '.' . $entity->getId(),
            ]);
        } else {
            throw new \RuntimeException(sprintf('Unknown entity type: "%s"', get_class($entity)));
        }
    }
}
