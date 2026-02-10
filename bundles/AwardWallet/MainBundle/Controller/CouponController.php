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
 * @Route("/coupon")
 */
class CouponController extends AbstractController
{
    private EntityManagerInterface $em;

    private Connection $connection;

    private Handler $formProviderCouponHandlerDesktop;

    public function __construct(
        EntityManagerInterface $em,
        Connection $connection,
        Handler $formProviderCouponHandlerDesktop
    ) {
        $this->em = $em;
        $this->connection = $connection;
        $this->formProviderCouponHandlerDesktop = $formProviderCouponHandlerDesktop;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/add", name="aw_coupon_add", options={"expose"=true})
     */
    public function addCouponAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        UseragentRepository $useragentRepository
    ) {
        $coupon = new Providercoupon();
        $user = $tokenStorage->getBusinessUser();

        if (!$user instanceof Usr) {
            throw $this->createNotFoundException();
        }

        $coupon->setUser($user);
        $agentId = $request->query->get('agentId');

        if (!empty($agentId) && is_numeric($agentId)) {
            $agent = $useragentRepository->find($agentId);

            if (empty($agent) || !$this->isGranted('EDIT_ACCOUNTS', $agent)) {
                throw $this->createAccessDeniedException();
            }

            if (!empty($agent->getClientid())) {
                $coupon->setUser($agent->getClientid());
                $coupon->getUseragents()->add($agent);
            } else {
                $coupon->setUserAgent($agent);
            }
        }

        return $this->editForm($coupon, $request);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', coupon)")
     * @Route("/edit/{couponId}", name="aw_coupon_edit", options={"expose"=true})
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function editAction(Request $request, Providercoupon $coupon)
    {
        return $this->editForm($coupon, $request);
    }

    private function editForm(Providercoupon $coupon, Request $request): Response
    {
        $form = $this->createForm(Type\ProvidercouponType::class, $coupon);
        $this->connection->beginTransaction();

        try {
            if ($this->formProviderCouponHandlerDesktop->handleRequest($form, $request)) {
                if (empty($coupon->getProvidercouponid())) {
                    $this->em->persist($coupon);
                }
                $this->em->flush();
                $this->connection->commit();

                if ($request->query->has('backTo')) {
                    return $this->redirect($request->getSchemeAndHttpHost() . $request->query->get('backTo'));
                }
                $params['coupon'] = $coupon->getProvidercouponid();

                if ($coupon->getIsArchived()) {
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

        return $this->render('@AwardWalletMain/Coupon/edit.html.twig', [
            'coupon' => $coupon,
            'form' => $form->createView(),
        ]);
    }
}
