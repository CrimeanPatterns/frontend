<?php

namespace AwardWallet\MainBundle\Controller\Sonata;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercountry;
use AwardWallet\MainBundle\Entity\RetailProvider;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

class RetailProviderAdminController extends CRUDController
{
    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;
    private RequestContext $requestContext;

    public function __construct(
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage,
        RequestContext $requestContext
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->requestContext = $requestContext;
    }

    public function importAction(Request $request)
    {
        $provRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $id = $request->get($this->admin->getIdParameter());
        /** @var RetailProvider $retailProvider */
        $retailProvider = $this->admin->getObject($id);

        $provider = (new Provider());

        $provider->setName($retailProvider->getName());
        $provider->setShortname($retailProvider->getName());
        $provider->setProgramname($retailProvider->getName());
        $provider->setDisplayname($retailProvider->getName());
        $provider->setCode(strtolower(preg_replace('/[^a-z0-9_]/ims', '', $retailProvider->getCode())));
        $provider->setKind(PROVIDER_KIND_SHOPPING);

        $provider->setLogincaption('');
        $provider->setPasswordrequired(false);

        $provider->setState(PROVIDER_TEST);
        $provider->setIsRetail(true);

        $provider->setAutologin(AUTOLOGIN_DISABLED);
        $provider->setMobileautologin(MOBILE_AUTOLOGIN_DISABLED);
        $provider->setItineraryautologin(ITINERARY_AUTOLOGIN_DISABLED);
        $provider->setCancheck(false);
        $provider->setCancheckbalance(false);
        $provider->setExpirationalwaysknown(false);

        $provider->setSite($retailProvider->getHomepage());
        $provider->setLoginurl($retailProvider->getHomepage());
        $provider->setCreationdate(new \DateTime());
        $provider->setInternalnote(
            date('Y-m-d') . ': imported as retail provider by ' . htmlspecialchars($this->tokenStorage->getUser()->getLogin())
        );
        $provider->setCanReceiveEmail(false);
        $provider->setCurrency($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Currency::class)->find(2));

        $additionalInfo = $retailProvider->getAdditionalInfo();
        $decodedInfo = @json_decode($additionalInfo, true);

        if (!$decodedInfo) {
            $decodedInfo = [];
        }

        if (!StringUtils::isEmpty($regions = $retailProvider->getRegions())) {
            $decodedInfo['regions'] = $retailProvider->getRegions();
        }

        $provider->setAdditionalInfo(json_encode($decodedInfo));

        $keywords = $retailProvider->getKeywords();
        $keywords = array_map('trim', explode(',', $keywords));

        if (!in_array(strtolower($retailProvider->getName()), array_map('strtolower', $keywords))) {
            array_unshift($keywords, $retailProvider->getName());
        }

        $retailProvider->setLastReviewDate(new \DateTime());
        $retailProvider->setReviwerId($this->tokenStorage->getUser());

        $this->entityManager->persist($provider);
        $retailProvider->setImportedProviderId($provider);
        $retailProvider->setState(RetailProvider::STATE_IMPORTED);

        foreach (['Name', 'ShortName'] as $nameKey) {
            $keywords[] = strtolower(trim(preg_replace(["/[^a-zA-Z0-9\-]/ims", "/\s{2,}/"], [" ", " "], htmlspecialchars_decode($provider->{"get" . $nameKey}()))));
        }

        if (
            (false !== ($parsed = parse_url($provider->getSite())))
            && isset($parsed['host'])
        ) {
            $keywords[] = preg_replace('/^www\./ims', '', $parsed['host']);
        }

        $provider->setKeywords($provRep->modifyKeywords($provider->getKeywords(), $keywords));

        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->flush();

            if (isset($decodedInfo['regions'])) {
                $regions = explode(',', $decodedInfo['regions']);
                $countryRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Country::class);

                foreach ($regions as $region) {
                    $countries = $countryRep->findBy(['code' => strtolower(trim($region))]);

                    if (
                        $countries
                        && !StringUtils::isEmpty($retailProvider->getHomepage())
                    ) {
                        $provider->setLogin2caption('Country');
                        $this->entityManager->persist(
                            (new Providercountry())
                                ->setCountryId($countries[0])
                                ->setProviderId($provider)
                                ->setLoginUrl($retailProvider->getHomepage())
                                ->setSite($retailProvider->getHomepage())
                                ->setLoginCaption('')
                        );
                    }
                }

                $this->entityManager->flush();
            }

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            return $this->renderWithExtraParams('@AwardWalletMain/Sonata/CRUD/RetailProvider/import.html.twig', [
                'error' => preg_match('/SQLSTATE\[\d+\]:(.*)/ims', $e->getMessage(), $matches) ? $matches[1] : '',
                'object' => $retailProvider,
            ]);
        }

        return new RedirectResponse(
            $this->requestContext->getScheme() . '://' .
            $this->requestContext->getHost() . '/manager/edit.php?ID=' . $provider->getId() . '&Schema=Provider'
        );
    }
}
