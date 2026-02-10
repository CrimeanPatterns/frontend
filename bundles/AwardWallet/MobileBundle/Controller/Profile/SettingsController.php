<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\NotificationSettings;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MobileBundle\Form\Type\Profile\NotificationType;
use AwardWallet\MobileBundle\Form\Type\Profile\OtherSettingsType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingsController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private PageVisitLogger $pageVisitLogger;

    public function __construct(
        LocalizeService $localizeService,
        PageVisitLogger $pageVisitLogger
    ) {
        $localizeService->setRegionalSettings();
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * @Route("/notifications/{group}",
     *     name="aw_mobile_notifications",
     *     methods={"GET", "PUT"},
     *     requirements={"group": "email|push|all"},
     *     defaults={"group" = "all"}
     * )
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function notificationsAction(
        Request $request,
        $group = 'all',
        Handler $awFormNotificationHandlerMobile,
        TranslatorInterface $tr,
        ApiVersioningService $apiVersioningService,
        FormDehydrator $formDehydrator,
        UsrRepository $userRepository,
        GeoLocation $geoLocation
    ) {
        /** @var Usr $user */
        $user = $this->getCurrentUser();
        $isTrial = $userRepository->isTrialAccount($user);
        $isUsClientIp = $geoLocation->getCountryIdByIp($request->getClientIp()) === Country::UNITED_STATES;
        $freeVersion = ($user->isFree() || $isTrial) && $user->isUs() && $isUsClientIp;
        $pageName = null;

        switch ($group) {
            case "email":
                $groups = [NotificationSettings::KIND_EMAIL];
                $pageName = PageVisitLogger::PAGE_EDIT_EMAIL_NOTIFICATIONS;

                break;

            case "push":
                $groups = [NotificationSettings::KIND_MP];
                $pageName = PageVisitLogger::PAGE_EDIT_PUSH_NOTIFICATIONS;

                break;

            default:
                $groups = [NotificationSettings::KIND_EMAIL, NotificationSettings::KIND_MP];

                break;
        }
        $form = $this->createForm(NotificationType::class, $user, [
            'method' => 'PUT',
            'isApp' => $this->isGranted("SITE_MOBILE_APP"),
            'groups' => $groups,
            'freeVersion' => $freeVersion,
        ]);

        if ($awFormNotificationHandlerMobile->handleRequest($form, $request)) {
            if ($pageName !== null) {
                $this->pageVisitLogger->log($pageName, true);
            }

            if ($apiVersioningService->supports(MobileVersions::ADVANCED_NOTIFICATIONS_SETTINGS)) {
                return $this->successJsonResponse();
            } else {
                return $this->successJsonResponse(['needUpdate' => true]);
            }
        }

        $formTitle = $tr->trans('personal_info.notifications');

        if (
            $apiVersioningService->supports(MobileVersions::NOTIFICATIONS_SETTINGS_REMOVE_GROUP_TITLE)
            && sizeof($groups) === 1
        ) {
            switch ($group) {
                case "email":
                    $formTitle = $tr->trans('email-notifications');

                    break;

                case "push":
                    $formTitle = $tr->trans('push-notifications');

                    break;
            }
        }

        $formJson = $formDehydrator->dehydrateForm($form);

        if (isset($formJson['children'])) {
            $formJson['children'] = array_map(function (array $item) {
                if (!empty($item['errors'] ?? null) && isset($item['errors'][0])) {
                    $item['error'] = $item['errors'][0];
                }

                return $item;
            }, $formJson['children']);
        }

        if ($pageName !== null) {
            $this->pageVisitLogger->log($pageName, true);
        }

        return new JsonResponse(
            array_merge(
                $formJson,
                ['formTitle' => $formTitle]
            )
        );
    }

    /**
     * @Route("/other-settings", name="aw_mobile_other_settings", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function otherAction(
        Request $request,
        Handler $awFormOtherSettingsHandlerMobile,
        FormDehydrator $formDehydrator,
        TranslatorInterface $translator
    ) {
        $form = $this->createForm(OtherSettingsType::class, $this->getCurrentUser(), [
            'method' => 'PUT',
        ]);
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_CHANGE_OTHER_SETTINGS, true);

        if ($awFormOtherSettingsHandlerMobile->handleRequest($form, $request)) {
            $response = $this->successJsonResponse();
            $response->headers->clearCookie('refCode', '/blog');
            $response->headers->clearCookie('user', '/blog');

            return $response;
        }

        return new JsonResponse(
            array_merge(
                $formDehydrator->dehydrateForm($form),
                ['formTitle' => $translator->trans('settings')]
            )
        );
    }
}
