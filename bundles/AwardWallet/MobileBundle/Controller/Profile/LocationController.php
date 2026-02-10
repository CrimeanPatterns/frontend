<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use AwardWallet\MobileBundle\Form\Type\Profile\LoyaltyLocationListType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\filterIsSetColumn;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LocationController extends AbstractController
{
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/location/list", name="aw_mobile_location_list", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @JsonDecode
     */
    public function listAction(
        Request $request,
        AwTokenStorageInterface $awTokenStorage,
        TrackedLocationsLimiter $trackedLocationsLimiter,
        ApiVersioningService $apiVersioningService,
        EntityManagerInterface $em,
        CacheManager $cacheManager,
        Handler $awFormLoyaltyLocationListHandlerMobile,
        FormDehydrator $formDehydrator,
        LoyaltyLocation $loyaltyLocation,
        TranslatorInterface $translator
    ) {
        if ($apiVersioningService->supports(MobileVersions::PROFILE_LOCATIONS_SIMPLE)) {
            if ($request->getMethod() === 'PUT') {
                if ($this->isGranted('USER_IMPERSONATED')) {
                    throw $this->createAccessDeniedException();
                }

                $locationRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Location::class);
                $enabled = [];
                $disabled = [];

                foreach (filterIsSetColumn($request->request->all(), 'locationId') as $location) {
                    if ($location['delete'] ?? false) {
                        if (
                            ($location = $locationRep->find($location['locationId']))
                            && $this->isGranted('DELETE', $location)
                        ) {
                            $em->remove($location);
                        }
                    } elseif (isset($location['tracked'])) {
                        if ($location['tracked']) {
                            $enabled[] = $location['locationId'];
                        } else {
                            $disabled[] = $location['locationId'];
                        }
                    }
                }

                $loyaltyLocation->enableLocations($awTokenStorage->getBusinessUser(), $enabled);
                $loyaltyLocation->disableLocations($awTokenStorage->getBusinessUser(), $disabled);
                $em->flush();
                $cacheManager->invalidateTags([Tags::getLoyaltyLocationsKey($awTokenStorage->getBusinessUser()->getId())]);

                return $this->getList($awTokenStorage->getBusinessUser(), $trackedLocationsLimiter->getMaxTrackedLocations(), $loyaltyLocation, $translator);
            } else {
                return $this->getList($awTokenStorage->getBusinessUser(), $trackedLocationsLimiter->getMaxTrackedLocations(), $loyaltyLocation, $translator);
            }
        } else {
            $form = $this->createForm(LoyaltyLocationListType::class, null, [
                'user' => $awTokenStorage->getBusinessUser(),
                'method' => 'PUT',
                'showDisabled' => !$request->isMethod('PUT'),
            ]);

            if ($awFormLoyaltyLocationListHandlerMobile->handleRequest($form, $request)) {
                return $this->successJsonResponse(['needUpdate' => true]);
            }

            return new JsonResponse(
                $formDehydrator->dehydrateForm($form)
            );
        }
    }

    protected function getList(
        Usr $user,
        $maxTracked,
        LoyaltyLocation $loyaltyLocation,
        TranslatorInterface $translator
    ): JsonResponse {
        $userHasDisabledCardNotifications = !$user->isMpRetailCards();
        $result = [
            'locations' => [],
            'disableAll' => $userHasDisabledCardNotifications,
        ];
        $locations = $loyaltyLocation->getLocations($user);
        $trackedLocationsCount = it($locations)->filterByColumn('Tracked', 1)->count();
        $maxCountReached = false;

        if ($trackedLocationsCount >= $maxTracked) {
            $maxCountReached = true;
            $result['warning'] = $translator->trans("favorite_locations.only-able-up-to", [
                "%max%" => $maxTracked,
                "%count%" => $trackedLocationsCount,
            ], 'mobile');
        }

        foreach ($locations as $location) {
            $result['locations'][] = [
                'locationId' => $location['LocationID'],
                'accountId' => $location['ComplexAccountID'],
                'subAccountId' => $location['SubAccountID'],
                'name' => \htmlspecialchars_decode($location['ProgramName']),
                'address' => $location['LocationName'],
                'lat' => $location['Lat'],
                'lng' => $location['Lng'],
                'tracked' => (bool) $location['Tracked'],
                'disabled' => $userHasDisabledCardNotifications || ($maxCountReached && ($location['Tracked'] == "0")),
            ];
        }

        return new JsonResponse($result);
    }
}
