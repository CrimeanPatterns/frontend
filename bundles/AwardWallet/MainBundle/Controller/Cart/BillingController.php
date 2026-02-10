<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Form\Type\Cart\BillingAddressType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/cart")
 */
class BillingController extends AbstractController
{
    private RouterInterface $router;
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(RouterInterface $router, AwTokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/billing/add", name="aw_billing_add", options={"expose"=false})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/Billing/add.html.twig")
     * @return array
     */
    public function addAction(Request $request)
    {
        $billingAddress = new Billingaddress();
        $billingAddress->setAddressname('Name'); // TODO Need refactoring

        $form = $this->createForm(BillingAddressType::class, $billingAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveAddress($form->getData());

            return $this->redirect($this->router->generate('aw_cart_common_orderdetails'));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/billing/edit/{id}", name="aw_billing_edit", requirements={"id" = "\d+"}, options={"expose"=false})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/Billing/edit.html.twig")
     * @return array
     */
    public function editAction(Request $request, $id)
    {
        $billingAddress = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($id);

        if (!$billingAddress || $billingAddress->getUserid() != $this->tokenStorage->getBusinessUser()) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(BillingAddressType::class, $billingAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveAddress($form->getData());

            return $this->redirect($this->router->generate('aw_cart_common_orderdetails'));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/billing/set/{id}", name="aw_billing_set", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function setAction($id, SessionInterface $session)
    {
        $address = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($id);

        if (!$address || $address->getUserid() != $this->tokenStorage->getBusinessUser()) {
            throw new AccessDeniedException();
        }

        $session->set('billing.address', $id);

        return $this->render('@AwardWalletMain/Cart/Billing/_address.html.twig', ['address' => $address, 'changeLink' => true]);
    }

    /**
     * @Route("/billing/delete/{id}", name="aw_billing_delete", methods={"POST"}, requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function deleteAction($id)
    {
        $address = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($id);

        if (!$address || $address->getUserid() != $this->tokenStorage->getBusinessUser()) {
            throw new AccessDeniedException();
        }

        $this->getDoctrine()->getManager()->remove($address);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/billing/get-states", name="aw_billing_get_states", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @return array
     */
    public function getStatesByCountry(Request $request, TranslatorInterface $translator)
    {
        $countryId = $request->get('countryId');
        $country = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->find($countryId);

        if ($country && $country->getHavestates()) {
            $states = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\State::class)->findBy(['countryid' => $countryId]);

            if ($states) {
                $result = [];
                $result[] = [
                    'id' => '',
                    'name' => $translator->trans('account.option.please.select'),
                ];

                foreach ($states as $state) {
                    if ($state->getStateid() && $state->getName()) {
                        $result[] = [
                            'id' => $state->getStateid(),
                            'name' => $state->getName(),
                        ];
                    }
                }

                return new JsonResponse([
                    'success' => true,
                    'states' => $result,
                ]);
            }
        }

        return new JsonResponse(['success' => false]);
    }

    private function saveAddress(Billingaddress $address)
    {
        $em = $this->getDoctrine()->getManager();
        $address->setUserid($this->tokenStorage->getBusinessUser());
        $em->persist($address);
        $em->flush();
    }
}
