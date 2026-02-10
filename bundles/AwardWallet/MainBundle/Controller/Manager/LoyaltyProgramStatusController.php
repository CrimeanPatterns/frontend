<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Provider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoyaltyProgramStatusController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Updates the date of last fix at the specified provider.
     *
     * @Route("/manager/loyalty/last-fix-date", name="aw_manager_loyalty_last_fix_date", methods={"POST"}, condition="request.isXmlHttpRequest()")
     * @IsGranted("ROLE_MANAGE_PROVIDERSTATUS")
     * @JsonDecode()
     */
    public function updateLastFixDate(Request $request): JsonResponse
    {
        $providerId = $request->request->get('providerId');
        $provider = $this->entityManager->getRepository(Provider::class)->find($providerId);

        if ($provider === null) {
            return new JsonResponse(['success' => false, 'message' => 'Provider does not exist.']);
        }

        $fieldName = $request->request->get('fieldName');
        $dateTime = new \DateTime();

        switch ($fieldName) {
            case 'clientSideLastFixDate':
                $provider->setClientSideLastFixDate($dateTime);

                break;

            case 'serverSideLastFixDate':
                $provider->setServerSideLastFixDate($dateTime);

                break;

            default:
                return new JsonResponse(['success' => false, 'message' => '"FieldName" is not valid.']);
        }

        $this->entityManager->flush();
        $this->logger->info('LP Status - last fix date has been updated: ' . json_encode(['providerId' => $providerId, 'type' => $fieldName]));

        return new JsonResponse(['success' => true]);
    }
}
