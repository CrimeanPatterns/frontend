<?php

namespace AwardWallet\MainBundle\Controller\Manager\Email;

use AwardWallet\MainBundle\Email\EmailAddressManager;
use AwardWallet\MainBundle\Email\GmailFilter;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class GmailFilterController
{
    private GmailFilter $gf;

    private RouterInterface $router;

    private UsrRepository $urep;

    private EmailAddressManager $eam;

    public function __construct(GmailFilter $gf, EmailAddressManager $eam, RouterInterface $router, UsrRepository $urep)
    {
        $this->gf = $gf;
        $this->router = $router;
        $this->urep = $urep;
        $this->eam = $eam;
    }

    /**
     * @Route("/manager/email/gmailFilter", name="aw_manager_email_gmailFilter")
     * @Security("is_granted('ROLE_STAFF')")
     * @Template("@AwardWalletMain/Manager/Email/GmailFilter/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $response = [
            'title' => 'Gmail Filter Generator',
        ];
        $response['meta'] = $this->eam->getMeta();

        if ($login = $request->query->get('login')) {
            $response['urls'] = [];

            for ($i = 0; $i < $response['meta']['listCount']; $i++) {
                $response['urls'][] = $this->router->generate('aw_manager_email_gmailFilter_file', ['login' => $login, 'pos' => $i]);
            }
            $response['login'] = $login;
        }

        return $response;
    }

    /**
     * @Route("/manager/email/gmailFilter/{login}/{pos}", name="aw_manager_email_gmailFilter_file")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function fileAction($login, $pos)
    {
        $usr = $this->urep->findOneBy(['login' => $login]);

        if (empty($usr)) {
            throw new BadRequestHttpException('Invalid login');
        }
        $filter = $this->gf->getFilter($usr, intval($pos), '', '');

        if (empty($filter)) {
            throw new NotFoundHttpException();
        }
        $response = new Response($filter);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "gmailFilter-{$pos}.xml"));

        return $response;
    }

    /**
     * @Route("/manager/email/gmailFilter/list", name="aw_manager_email_gmailFilter_list")
     * @Security("is_granted('ROLE_STAFF')")
     * @Template("@AwardWalletMain/Manager/Email/GmailFilter/list.html.twig")
     */
    public function listAction()
    {
        $response = [
            'title' => 'Gmail Filter Address List',
            'list' => $this->eam->getFullList(),
            'meta' => $this->eam->getMeta(),
        ];

        return $response;
    }

    /**
     * @Route("/manager/email/gmailFilter/post", name="aw_manager_email_gmailFilter_post")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function postAction(Request $request)
    {
        $verified = intval($request->request->get('status'));

        if (!in_array($verified, [1, -1])) {
            return new JsonResponse(['error' => 'invalid status']);
        }
        $ids = $request->request->get('ids');
        $ids = array_filter(
            array_map('trim', explode(',', $ids)), function ($item) {return !empty($item) && is_numeric($item); });

        if (empty($ids)) {
            return new JsonResponse(['error' => 'invalid ids']);
        }
        $this->eam->writeBatch($ids, $verified);
        $this->eam->clearCache();

        return new JsonResponse(['error' => null]);
    }
}
