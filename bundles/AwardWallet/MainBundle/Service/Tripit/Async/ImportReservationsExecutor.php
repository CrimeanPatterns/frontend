<?php

namespace AwardWallet\MainBundle\Service\Tripit\Async;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ImportReservationsExecutor implements ExecutorInterface
{
    private LoggerInterface $logger;
    private UsrRepository $usrRepository;
    private EntityManagerInterface $entityManager;
    private TripitHelper $tripitHelper;

    public function __construct(
        LoggerInterface $logger,
        UsrRepository $usrRepository,
        EntityManagerInterface $entityManager,
        TripitHelper $tripitHelper
    ) {
        $this->logger = $logger;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
        $this->tripitHelper = $tripitHelper;
    }

    /**
     * @param ImportReservationsTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        if (!empty($task->getRequestBody())) {
            parse_str($task->getRequestBody(), $output);

            if (!isset($output['oauth_token_key'])) {
                $this->logger->error('TripIt notifications: incorrect oauth token');

                return new Response();
            }

            $user = $this->usrRepository->findByTripitAccessToken($output['oauth_token_key']);

            if ($user) {
                $tripitUser = new TripitUser($user, $this->entityManager);
                $this->tripitHelper->list($tripitUser);

                $user->setTripitLastSync(new \DateTime());
                $this->entityManager->flush();
            } else {
                $this->logger->info('TripIt notifications: user is not found');
            }
        }

        return new Response();
    }
}
