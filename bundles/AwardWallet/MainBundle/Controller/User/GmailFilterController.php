<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Email\EmailAddressManager;
use AwardWallet\MainBundle\Email\GmailFilter;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class GmailFilterController extends AbstractController
{
    /**
     * @Route("/user/get-filter/{pos}/{alias}", name="aw_users_getfilter", options={"expose"=true}, requirements={"pos" = "\d+", "alias" = "\w+"}, defaults={"pos" = 0, "alias" = ""})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function getFilterAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        GmailFilter $gf,
        $pos,
        $alias
    ) {
        $filter = $gf->getFilter($tokenStorage->getUser(), $pos, $alias, $request->query->get('to', ''));

        if (empty($filter)) {
            throw new NotFoundHttpException();
        }
        $logger->info('gmail filter requested');
        $response = new Response($filter);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "gmailFilter" . ($pos + 1) . ".xml"));

        return $response;
    }

    /**
     * @Route("/user/get-filter-meta", name="aw_users_getfiltermeta", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function getFilterMetaAction(EmailAddressManager $eam, AwTokenStorageInterface $tokenStorage)
    {
        return new JsonResponse($eam->getMeta($tokenStorage->getBusinessUser()));
    }

    /**
     * @Route("/gmail-forwarding", name="aw_gmail_forwarding", methods={"GET"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function gmailForwardingAction(AwTokenStorageInterface $tokenStorage, Request $request, UserProfileWidget $userProfileWidget, Environment $twigEnv)
    {
        $user = $tokenStorage->getBusinessUser();
        $twigEnv->addGlobal('webpack', true);

        $data = [
            'userLogin' => $user->getLogin(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($data);
        }

        $userProfileWidget->setActiveNone();

        return $this->render('@AwardWalletMain/spa.html.twig', [
            'entrypoint' => 'user-settings',
            'data' => $data,
            'extendProfile' => true,
        ]);
    }
}
