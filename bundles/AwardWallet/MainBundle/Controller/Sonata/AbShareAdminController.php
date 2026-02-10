<?php

namespace AwardWallet\MainBundle\Controller\Sonata;

use AwardWallet\MainBundle\Entity\AbShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AbShareAdminController extends CRUDController
{
    private ProgramShareManager $programShareManager;

    public function __construct(ProgramShareManager $programShareManager)
    {
        $this->programShareManager = $programShareManager;
    }

    public function approveAction()
    {
        $object = $this->handleRequest('approve');

        if ($object->isApproved()) {
            return $this->redirectToList();
        }

        $this->approve($object);

        return $this->redirectToList();
    }

    public function unapproveAction()
    {
        $object = $this->handleRequest('unapprove');

        if (!$object->isApproved()) {
            return $this->redirectToList();
        }

        $em = $this->getDoctrine()->getManager();
        $ua = $this->getConnection($object);

        if ($ua) {
            $this->programShareManager->setUser($object->getUser());
            $this->programShareManager->apiSharingDenyAll($object->getUser(), $ua);
            $object->setIsApproved(false);
            $em->flush();
        }

        return $this->redirectToList();
    }

    public function impersonateAction()
    {
        $object = $this->handleRequest('impersonate');

        return $this->redirect('/manager/impersonate?UserID=' . $object->getUser()->getUserid());
    }

    public function batchActionApprove(ProxyQuery $selectedModelQuery)
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $selectedModelQuery->execute();

        try {
            foreach ($selectedModels as $selectedModel) {
                /** @var AbShare $selectedModel */
                $this->approve($selectedModel);
            }

            $this->addFlash('sonata_flash_success', 'Success');
        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', $e->getMessage());
        }

        return $this->redirectToList();
    }

    private function handleRequest($action): AbShare
    {
        /** @var AbShare $object */
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException('Share request not found');
        }

        $this->admin->checkAccess($action, $object);

        return $object;
    }

    private function getConnection(AbShare $object): ?Useragent
    {
        return $this->getDoctrine()->getManager()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findOneBy([
            'agentid' => $object->getBooker(),
            'clientid' => $object->getUser(),
            'isapproved' => true,
        ]);
    }

    private function approve(AbShare $abShare)
    {
        if ($abShare->isApproved()) {
            return;
        }
        $em = $this->getDoctrine()->getManager();
        $ua = $this->getConnection($abShare);

        if ($ua) {
            $this->programShareManager->setUser($abShare->getUser());
            $this->programShareManager->apiSharingShareAll($abShare->getUser(), $ua, 'full');
            $abShare->setIsApproved(true);
            $em->flush();
        }
    }
}
