<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\ProviderStatusHandler;
use AwardWallet\WidgetBundle\Widget\ContactUsWidget;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/status")
 */
class StatusController extends AbstractController implements TranslationContainerInterface
{
    private ProviderStatusHandler $statusHandler;

    public function __construct(ProviderStatusHandler $statusHandler)
    {
        $this->statusHandler = $statusHandler;
    }

    /**
     * @Route("/", name="aw_status_index", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Status/index.html.twig")
     */
    public function indexAction(
        ContactUsWidget $contactUsWidget,
        TranslatorInterface $translator,
        PageVisitLogger $pageVisitLogger
    ) {
        $contactUsWidget->setActiveItem(2);
        $pageVisitLogger->log(PageVisitLogger::PAGE_PROVIDER_HEALTH_DASHBOARD);

        return [
            'broken' => [
                'caption' => $translator->trans('status.broken'),
                'list' => $this->getStatusHandler()->getBroken(),
            ],
            'consideringAdd' => [
                'caption' => $translator->trans('status.considering-add'),
                'list' => $this->getStatusHandler()->getConsideringAdd(),
            ],
            'cannotAdd' => [
                'caption' => $translator->trans('status.cannot-added'),
                'list' => $this->getStatusHandler()->getCannotAdd(),
            ],
            'working' => [
                'caption' => $translator->trans('status.working-programs'),
                'list' => $this->getStatusHandler()->getWorking(),
            ],
        ];
    }

    /**
     * @Route("/vote", name="aw_status_vote", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function voteAction(Request $request, TranslatorInterface $translator)
    {
        $providerId = (int) $request->get('id', 0);
        $comment = $request->get('comment');

        $additionalData = '';
        $v3 = is_string($request->get('v3')) ? $request->get('v3') : null;
        $mobileExtensionVersion = $request->headers->get(MobileHeaders::MOBILE_EXTENSION_VERSION);

        if (is_string($mobileExtensionVersion) && !empty($mobileExtensionVersion)) {
            $v3 = $mobileExtensionVersion;
        }

        $v3Text = !empty($v3) ? 'Enabled, v' . $v3 : 'Disabled';
        $isIOS = $this->isGranted('SITE_MOBILE_APP_IOS');
        $isAndroid = $this->isGranted('SITE_MOBILE_APP_ANDROID');

        if ($isIOS || $isAndroid) {
            $additionalData .= sprintf("Mobile Platform: %s<br>", $isIOS ? 'iOS' : 'Android');

            if (empty($v3)) {
                $v3Text = $isAndroid ? 'Enabled' : 'Unknown';
            }
        }

        $additionalData .= sprintf("Browser Extension: %s<br>", $v3Text);
        $response = ['success' => false];

        if ($providerId) {
            if (!empty($extensionVersion = $request->request->get('extensionVersion'))) {
                $this->getStatusHandler()->setUserExtensionVersion($extensionVersion);
            }

            if ($this->getStatusHandler()->isVoted($providerId)) {
                $response['message'] = $translator->trans('status.already-voted');
            } else {
                $result = $this->getStatusHandler()->vote($providerId, $comment, $additionalData);

                if ($result) {
                    $response['success'] = true;
                }
            }
        }

        return $this->json($response);
    }

    /**
     * @Route("/getMessage", name="aw_status_getmessage", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function getMessage(Request $request)
    {
        $providerId = (int) $request->get('id', 0);

        if ($providerId && is_array($result = $this->getStatusHandler()->getProviderMessage($providerId))) {
            return $this->json($result);
        }

        return $this->json(['success' => false]);
    }

    /**
     * @Route("/getSuccessRate", name="aw_status_getrate", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function getSuccessRateAction(Request $request, ProviderRepository $providerRepository)
    {
        $providerId = (int) $request->get('id', 0);
        $successRate = $providerRepository->getSuccessRateProvider($providerId);
        $successRate = empty($successRate) ? '0%' : $successRate . '%';

        return $this->json(['successRate' => $successRate]);
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('status.page-title'))->setDesc('Status program'),
            (new Message('status.all'))->setDesc('All'),
            (new Message('status.broken'))->setDesc('Broken'),
            (new Message('status.considering-add'))->setDesc('Considering to add'),
            (new Message('status.cannot-added'))->setDesc('Cannot be added'),
            (new Message('status.working-programs'))->setDesc('Working programs'),
            (new Message('status.display-name'))->setDesc('Display Name'),
            (new Message('status.comments'))->setDesc('Comments'),
            (new Message('status.complaints'))->setDesc('Complaints'),
            (new Message('status.requests'))->setDesc('Requests'),
            (new Message('status.email-when-fixed'))->setDesc('Email me when fixed'),
            (new Message('status.not-found'))->setDesc('No programs are currently known (confirmed) to be broken at the moment.'),
            (new Message('status.i-need-program'))->setDesc('I need this program'),
            (new Message('status.program-type'))->setDesc('Program Type'),
            (new Message('status.support-added'))->setDesc('Support added on'),
            (new Message('status.mark-broken'))->setDesc('Mark as broken'),
            (new Message('status.please-confirm'))->setDesc('Please Confirm'),
            (new Message('status.please-wait'))->setDesc('Please wait'),
            (new Message('status.confirm.broken-text'))->setDesc('You are about to request to get an automated email from us whenever we fix the known issues with %providerName% program. Please confirm this is what you want.'),
            (new Message('status.confirm.broken-yes'))->setDesc('Yes, email me when %providerName% is fixed'),
            (new Message('status.confirm.considering-text'))->setDesc('You\'ve indicated that you want us to add support for %providerName% program. We add support for new loyalty programs based on demand and your vote will count. Once we do implement support for %providerName% we will send you an automated email. Please confirm that you want us to add support for %providerName% program.'),
            (new Message('status.confirm.working-text'))->setDesc('You\'ve indicated that you are having issues with the %providerName% program. The success rate on this program is %providerSuccessRate% (in the last 24 hours %providerSuccessRate% of %providerName% accounts were successfully updated).<br><br>Please make sure that your %providerName% account is added to your profile so that we can investigate this issue. Also, please note, that the most common reason our users can’t update an account is they provide us with credentials that don\'t let us see their account balance either because the credentials are wrong or because there are pending questions / prompts on the account that don\'t let us navigate to the page that has your balance. If you suspect this could be the case please follow this FAQ first before submitting this request to us. If you still think that we have a problem with %providerName% program please confirm it below.'),
            (new Message('status.confirm.working-yes'))->setDesc('Yes, I want to report %providerShortName% as broken'),
            (new Message('status.already-voted'))->setDesc('Already voted'),
            (new Message('status.error-occurred'))->setDesc('Error occurred'),
            (new Message('status.not-detect-accounts'))->setDesc('We did not detect any %providerName% accounts in your profile, so our support staff will not be able to look into this issue for you. If you wish to report a program as broken please make sure you have at least one account of that program added to your AwardWallet profile'),
            (new Message('status.report-error'))->setDesc('Report an error'),
            (new Message('status.not-seeing-accounts'))->setDesc('You\'ve indicated that you are having issues with the %providerName% program. The success rate on this program is %providerSuccessRate% (in the last 24 hours %providerSuccessRate% of %providerName% accounts were successfully updated).
                    <br><br>The most common reason our users can’t update an account is they provide us with credentials that don\'t let us see their account balance either because the credentials are wrong or because there are pending questions / prompts on the account that don\'t let us navigate to the page that has your balance. If you suspect this could be the case please follow <a href="/faqs#9" target="_blank">this FAQ</a> first before submitting this request to us. At the moment we are not seeing any %providerName% accounts in your AwardWallet profile that have errors, if you deleted the account that had an error please put it back so that we can look into it.'),
            (new Message('status.please-add-comments'))->setDesc('Please, add some comments so that our support staff have a better understanding of your issue'),
            (new Message('status.account'))->setDesc('Account'),
            (new Message('status.owner'))->setDesc('Owner'),
            (new Message('status.error'))->setDesc('Error'),
            (new Message('status.last-update-attempt'))->setDesc('Last update attempt'),
            (new Message('status.error-program-account'))->setDesc('Here is the error that we are able to see in your profile for %providerName% program:'),
            (new Message('status.error-program-accounts'))->setDesc('Here are the errors that we are able to see in your profile for %providerName% program:'),
            (new Message('status.error-program.comment-account'))->setDesc('Comments about your %providerName% account'),
            (new Message('status.still-have-problem'))->setDesc('If you still think that we have a problem with %providerName% program please add your comments and confirm it below.'),
            (new Message('status.issues-program'))->setDesc('You\'ve indicated that you are having issues with the %providerName% program. The success rate on this program is %providerSuccessRate% (in the last 24 hours %providerSuccessRate% of %providerName% accounts were successfully updated.
                    <br><br>The most common reason our users can’t update an account is they provide us with credentials that don\'t let us see their account balance either because the credentials are wrong or because there are pending questions / prompts on the account that don\'t let us navigate to the page that has your balance. 
                    If you suspect this could be the case please follow <a href="/faqs#9" target="_blank">this FAQ</a> first before submitting this request to us.'),
        ];
    }

    /**
     * @Route("/index.php", name="aw_status_compatibility")
     */
    public function linkCompatibility(Request $request)
    {
        $hash = '#';
        $tab = $request->get('showTabG', $request->get('group', null));

        switch ($tab) {
            case 'broken':
                $hash .= 'broken';

                break;

            case 'toAdd':
                $hash .= 'consideringAdd';

                break;

            case 'intoAdd':
                $hash .= 'cannotAdd';

                break;

            case 'support':
                $hash .= 'working';

                break;

            default:
                $hash .= 'all';

                break;
        }

        return $this->redirect($this->generateUrl('aw_status_index') . $hash, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }

    private function getStatusHandler()
    {
        /** @var Usr $user */
        $user = $this->getUser();
        $this->statusHandler->setUser($user);

        return $this->statusHandler;
    }
}
