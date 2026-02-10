<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/document")
 */
class DocumentController extends AbstractController
{
    public const DOCUMENT_ROUTE_TYPES = Providercoupon::KEY_TYPE_PASSPORT
        . '|' . Providercoupon::KEY_TYPE_TRAVELER_NUMBER
        . '|' . Providercoupon::KEY_TYPE_VACCINE_CARD
        . '|' . Providercoupon::KEY_TYPE_INSURANCE_CARD
        . '|' . Providercoupon::KEY_TYPE_VISA
        . '|' . Providercoupon::KEY_TYPE_DRIVERS_LICENSE
        . '|' . Providercoupon::KEY_TYPE_PRIORITY_PASS;

    private Handler $formDocumentHandlerDesktop;

    private Connection $connection;

    private EntityManagerInterface $em;

    public function __construct(
        Handler $formDocumentHandlerDesktop,
        Connection $connection,
        EntityManagerInterface $em
    ) {
        $this->formDocumentHandlerDesktop = $formDocumentHandlerDesktop;
        $this->connection = $connection;
        $this->em = $em;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route(
     *     "/add/{type}",
     *     name="aw_document_add",
     *     options={"expose"=true},
     *     requirements={"type"=DocumentController::DOCUMENT_ROUTE_TYPES}
     * )
     */
    public function addDocumentAction(
        Request $request,
        string $type,
        AwTokenStorageInterface $tokenStorage,
        UseragentRepository $useragentRepository
    ) {
        $document = (new Providercoupon())
            ->setKind(PROVIDER_KIND_DOCUMENT);
        $user = $tokenStorage->getBusinessUser();

        if (!$user instanceof Usr) {
            throw $this->createNotFoundException();
        }

        $document->setTypeid(Providercoupon::DOCUMENT_KEY_TO_TYPE_MAP[$type] ?? null);
        $document->setUser($user);
        $agentId = $request->query->get('agentId');

        if (!empty($agentId) && is_numeric($agentId)) {
            $agent = $useragentRepository->find($agentId);

            if (empty($agent) || !$this->isGranted('EDIT_ACCOUNTS', $agent)) {
                throw $this->createAccessDeniedException();
            }

            if (!empty($agent->getClientid())) {
                $document->setUser($agent->getClientid());
                $document->getUseragents()->add($agent);
            } else {
                $document->setUseragent($agent);
            }
        }

        return $this->editForm($document, $request);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', document)")
     * @Route("/edit/{documentId}", name="aw_document_edit", options={"expose"=true})
     * @ParamConverter("document", class="AwardWalletMainBundle:Providercoupon", options={"id" = "documentId"})
     */
    public function editAction(Request $request, Providercoupon $document)
    {
        return $this->editForm($document, $request);
    }

    private function editForm(Providercoupon $document, Request $request): Response
    {
        $form = $this->createForm(Type\DocumentType::class, $document);
        $edit = !empty($document->getProvidercouponid());
        $this->connection->beginTransaction();

        try {
            if ($this->formDocumentHandlerDesktop->handleRequest($form, $request)) {
                if (!$edit) {
                    $this->em->persist($document);
                }
                $this->em->flush();
                $this->connection->commit();

                if ($request->query->has('backTo')) {
                    return $this->redirect($request->getSchemeAndHttpHost() . $request->query->get('backTo'));
                }
                $params['coupon'] = $document->getProvidercouponid();

                if ($document->getIsArchived()) {
                    $params['archive'] = 'on';
                }

                return $this->redirectToRoute('aw_account_list', $params);
            } else {
                $this->connection->rollBack();
            }
        } catch (\Exception $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $e;
        }

        return $this->render('@AwardWalletMain/Document/edit.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'type' => Providercoupon::DOCUMENT_TYPE_TO_KEY_MAP[$document->getTypeid()] ?? '',
            'edit' => $edit,
        ]);
    }
}
