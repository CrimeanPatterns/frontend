<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use AwardWallet\MainBundle\Service\MerchantLookup;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/merchant-reverse")
 */
class MerchantReverseLookupController
{
    /** @var MerchantLookup */
    private $lookupService;
    /** @var EntityManagerInterface */
    private $em;
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(MerchantLookup $lookupService, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->lookupService = $lookupService;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/", name="aw_merchant_reverse_lookup", methods={"GET"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/MerchantReverseLookup/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request, PageVisitLogger $pageVisitLogger)
    {
        $response = [];
        //        if($this->tokenStorage->getToken()->getUser()->isAwPlus()) {
        $response['initData'] = $this->lookupService->getReverseLookupInitial();

        //        } else {
        //            $response['faq'] = $this->em->getRepository(Faq::class)->find(21);
        //        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_REVERSE_MERCHANT_LOOKUP_TOOL);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/offer/{id}", name="aw_merchant_reverse_lookup_offer", methods={"GET"}, options={"expose"=true})
     * @ParamConverter("multiplier", class="AwardWalletMainBundle:CreditCardShoppingCategoryGroup", options={"id" = "id"})
     * @Template("@AwardWalletMain/MerchantReverseLookup/offer.html.twig")
     * @return array
     */
    public function offerAction(Request $request, CreditCardShoppingCategoryGroup $multiplier)
    {
        //        if(!$this->tokenStorage->getToken()->getUser()->isAwPlus()) {
        //            throw new AccessDeniedHttpException();
        //        }
        if (null === $multiplier->getShoppingCategoryGroup()) {
            throw new NotFoundHttpException();
        }
        $merchants = $this->lookupService->buildReverseLookupOffer($multiplier->getShoppingCategoryGroup());

        return [
            'merchants' => $merchants,
            'cardName' => $multiplier->getCreditCard()->getName(),
            'category' => $multiplier->getShoppingCategoryGroup()->getName(),
            'multiplier' => $multiplier->getMultiplier(),
            'blogUrl' => $multiplier->getShoppingCategoryGroup()->getClickURL(),
        ];
    }
}
