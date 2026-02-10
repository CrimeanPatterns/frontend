<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\RetentionUser;
use GeoIp2\Database\Reader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SendRetentionController extends AbstractController
{
    use JsonTrait;

    private AccountRepository $accountRepository;
    private Reader $geoIpCountry;
    private Mailer $mailer;

    public function __construct(AccountRepository $accountRepository, Reader $geoIpCountry, Mailer $mailer)
    {
        $this->accountRepository = $accountRepository;
        $this->geoIpCountry = $geoIpCountry;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/manager/sendRetention", name="review", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_RETENTIONAD')")
     */
    public function indexAction(Request $request)
    {
        $this->notifyUser($request->request->get('Content'), true);

        return $this->jsonResponse(['answer' => 'OK Retention send!!']);
    }

    public function notifyUser($content, $enableAd)
    {
        $user = $this->getUser();
        $accounts = $this->accountRepository->findBy(['user' => $user->getUserid()]);

        try {
            $countryCode = $this->geoIpCountry->country($user->getRegistrationip())->country->isoCode;
        } catch (\Exception $e) {
            $countryCode = null;
        }

        $template = new RetentionUser($user);
        $template->fromUS = $countryCode == 'US';
        $template->accountsCount = count($accounts);
        $template->ad = $content;
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }
}
