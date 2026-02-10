<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LogoManager
{
    /**
     * @var Usr
     */
    private $booker;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AbRequest
     */
    private $abRequest;

    /**
     * @var string
     */
    private $host;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(ContainerInterface $container)
    {
        $this->tokenStorage = $container->get("aw.security.token_storage");
        $this->authorizationChecker = $container->get('security.authorization_checker');
        $this->container = $container;
        $this->host = $container->getParameter("host");
    }

    /**
     * get logo path, or null.
     *
     * @return LogoManagerResponse|null
     */
    public function getLogo()
    {
        // view/share -- from booking request
        // aw_booking_view_index, aw_booking_share_index
        if (!empty($this->abRequest)) {
            $logo = $this->abRequest->getBooker()->getBookerInfo()->getLogo('main');

            if ($logo) {
                return new LogoManagerResponse($logo, 'BOOK', $this->abRequest->getBooker()->getBookerInfo()->getServiceShortName());
            }
        }

        if (!empty($this->booker) && $this->booker->isBooker()) {
            $logo = $this->booker->getBookerInfo()->getLogo('main');

            if ($logo) {
                return new LogoManagerResponse($logo, 'BOOK', $this->booker->getBookerInfo()->getServiceShortName());
            }
        }

        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            // @TODO: remove getBrandPrefix, getBrandName, SITE_BRAND constants, introduce BrandManager?
            if (preg_match('#(cwt|bwr)\.' . preg_quote($this->host) . '$#i', $request->getHost(), $matches)) {
                return new LogoManagerResponse(null, strtoupper($matches[1]), $matches[1]);
            }

            $token = $this->tokenStorage->getToken();

            if (empty($token)) { // 404 page
                return null;
            }

            /** @var Usr $user */
            $user = $token->getUser();

            // booker on business
            if ($this->authorizationChecker->isGranted('USER_BOOKING_PARTNER')) {
                $bookerInfo = $this->tokenStorage->getBusinessUser()->getBookerInfo();

                return new LogoManagerResponse($bookerInfo->getLogo('main'), 'BOOK', $bookerInfo->getServiceShortName());
            }

            if ($this->authorizationChecker->isGranted('SITE_BOOKING_AREA') && $user instanceof Usr) {
                // logo from user owner
                $owner = $user->getOwnedByBusiness();

                if ($owner && $owner->isBooker()) {
                    $logo = $owner->getBookerInfo()->getLogo('main');

                    if ($logo) {
                        return new LogoManagerResponse($logo, 'BOOK', $owner->getBookerInfo()->getServiceShortName());
                    }
                }
            }
        }

        return null;
    }

    public function setBookingRequest(?AbRequest $abRequest = null)
    {
        $this->abRequest = $abRequest;
    }

    public function setBooker(?Usr $booker = null)
    {
        $this->booker = $booker;
    }
}
