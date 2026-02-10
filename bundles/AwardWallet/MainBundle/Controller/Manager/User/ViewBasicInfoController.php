<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\StateRepository;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ViewBasicInfoController extends AbstractController
{
    public const ERR_BASIC_INFO_INDEX_NOT_FOUND = 'ERR_BASIC_INFO_INDEX_NOT_FOUND';

    /**
     * @Security("is_granted('ROLE_MANAGE_LIMITED_EDIT_USER') or is_granted('ROLE_MANAGE_EDIT_USER') or is_granted('ROLE_MANAGE_USERADMIN')")
     * @Route("/manager/user-view-basic-info/{userid}", name="aw_manager_view_user_basic_info", requirements={"userid": "\d+"})
     * @ParamConverter("user", class="AwardWalletMainBundle:Usr")
     */
    public function userViewBasicInfoAction(
        Usr $user,
        CountryRepository $countryRepository,
        StateRepository $stateRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CartRepository $cartRepository,
        string $projectDir
    ) {
        global $arAccountLevel;
        $country = $user->getCountryid() ? $countryRepository->find($user->getCountryid()) : null;
        $state = $user->getStateid() ? $stateRepository->find($user->getStateid()) : null;
        $cartItemDiscMap = $entityManager->getClassMetadata(CartItem::class)->discriminatorMap;
        $lastAwPlusCart =
            it($cartRepository->getPayedCarts($user))
            ->filter(fn (CartItem $cartItem) =>
                $cartItem instanceof AwPlusUpgradableInterface
                && !$cartItem->isAwPlusSubscription()
            )
            ->map(fn (CartItem $cartItem) => $cartItem->getCart())
            ->first();
        $isTrial = $lastAwPlusCart ? $lastAwPlusCart->hasItemsByType(CartItem::TRIAL_TYPES) : false;
        $lastPlusItem = $lastAwPlusCart ? $lastAwPlusCart->getPlusItem() : null;

        return $this->render('@AwardWalletMain/Manager/User/view_basic_info.html.twig', [
            'user' => $user,
            'title' => 'Basic Info for user ' . $user->getId(),
            'accountLevelNamesTable' => $arAccountLevel,
            'emailVerifiedNamesTable' => [
                \EMAIL_UNVERIFIED => 'EMAIL_UNVERIFIED',
                \EMAIL_VERIFIED => 'EMAIL_VERIFIED',
                \EMAIL_NDR => 'EMAIL_NDR',
            ],
            'subscriptionTypeNamesTable' => [
                Usr::SUBSCRIPTION_TYPE_AWPLUS => 'SUBSCRIPTION_TYPE_AWPLUS',
                Usr::SUBSCRIPTION_TYPE_AT201 => 'SUBSCRIPTION_TYPE_AT201',
            ],
            'cartItemFQCNTable' => $cartItemDiscMap,
            'cartItemShortTable' =>
                it($cartItemDiscMap)
                ->map(fn (string $class) => (new \ReflectionClass($class))->getShortName())
                ->toArrayWithKeys(),
            'paymentTypesTable' => Cart::PAYMENT_TYPES,
            'isTrial' => $isTrial,
            'lastPlusItem' => $lastPlusItem,
            'groups' =>
                it($user->getGroups())
                ->usort(fn (Sitegroup $a, Sitegroup $b) => $b->getSitegroupid() <=> $a->getSitegroupid())
                ->toArray(),
            'country' => $country,
            'state' => $state,
            'lastLogonPoint' => $entityManager->getConnection()->fetchAssociative(
                'select ST_LATITUDE(Point) as lat, ST_LONGITUDE(Point) as lng from UsrLastLogonPoint where UserID = ? AND IsSet = 1',
                [$user->getId()]
            ),
            'helper' => new class($logger, $projectDir) {
                private LoggerInterface $logger;
                private string $projectDir;

                public function __construct(LoggerInterface $logger, string $projectDir)
                {
                    $this->logger = $logger;
                    $this->projectDir = $projectDir;
                }

                public function tableLookup(array $table, $index)
                {
                    if (!\array_key_exists($index, $table)) {
                        $this->logger->error("Index {$index} not found in table, please fix it", ['table' => $table, 'index' => $index]);

                        return ViewBasicInfoController::ERR_BASIC_INFO_INDEX_NOT_FOUND;
                    }

                    return $table[$index];
                }

                public function getFileName(?string $class): string
                {
                    if (StringUtils::isEmpty($class)) {
                        return '';
                    }

                    return \substr((new \ReflectionClass($class))->getFileName(), \strlen($this->projectDir) + 1);
                }
            },
        ]);
    }
}
