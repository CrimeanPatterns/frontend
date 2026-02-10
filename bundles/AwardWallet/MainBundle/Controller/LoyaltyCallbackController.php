<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 25.03.16
 * Time: 12:44.
 */

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AddPasswordVaultEvent;
use AwardWallet\MainBundle\Loyalty\Resources\PasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoyaltyCallbackController
{
    public const ACCOUNT_METHOD = 'account';
    public const CONFIRMATION_METHOD = 'confirmation';

    /** @var ProducerInterface */
    private $producer;
    /** @var LoggerInterface */
    private $logger;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var string */
    private $callbackPassword;
    /** @var EntityManagerInterface */
    private $em;
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(ProducerInterface $producer, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em, SerializerInterface $serializer, $callbackPassword)
    {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
        $this->serializer = $serializer;

        $this->callbackPassword = $callbackPassword;
    }

    /**
     * @Route("/api/awardwallet/loyalty/callback-v2/{type}/{priority}", name="aw_loyalty_callback", methods={"POST"})
     * @param int $priority
     * @return Response
     */
    public function callbackAction(Request $request, $type, $priority = 2)
    {
        if (!in_array($type, [self::ACCOUNT_METHOD, self::CONFIRMATION_METHOD])) {
            return new Response('Unavailable URL', 404);
        }

        $access = $this->checkAccess($request->getUser(), $request->getPassword());

        if (!$access) {
            return new Response('access denied', 403);
        }

        $content = $request->getContent();
        // for debug
        $this->logger->info(
            "receive callback package size",
            ['bodySha1' => sha1($content), 'bodySize' => strlen($content), "contentLength" => $request->headers->get('content-length')]
        );

        $this->producer->publish($content, '', ['priority' => $priority]);

        return new Response('OK');
    }

    /**
     * @Route("/api/awardwallet/loyalty/callback/{type}/{priority}", name="aw_loyalty_callback_v1", methods={"POST"})
     */
    public function callbackV1Action(Request $request, $type, $priority = 2)
    {
        // discard old callbacks
        return new Response('OK');
    }

    /**
     * @Route("/api/awardwallet/loyalty/password-request", name="aw_loyalty_callback_password_request", methods={"POST"})
     * @return Response
     */
    public function PasswordRequestCallbackAction(Request $httpRequest)
    {
        $this->checkAccess($httpRequest->getUser(), $httpRequest->getPassword());

        /** @var PasswordRequest $request */
        $request = $this->serializer->deserialize($httpRequest->getContent(), PasswordRequest::class, 'json');

        if (!$request instanceof PasswordRequest) {
            return new Response(
                Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = new Response('OK');
        /** @var ProviderRepository $userRepo */
        $providerRepo = $this->em->getRepository(Provider::class);
        $provider = $providerRepo->findOneBy(['code' => $request->getProvider()]);

        if (!$provider instanceof Provider) {
            $this->logger->critical('PasswordRequest result Unavailable provider', ['id' => $request->getId()]);

            return $result;
        }
        /** @var UsrRepository $userRepo */
        $userRepo = $this->em->getRepository(Usr::class);
        $usr = $userRepo->find($request->getUserId());

        if (!$usr instanceof Usr) {
            $this->logger->critical('PasswordRequest result Unavailable userId', ['id' => $request->getId()]);

            return $result;
        }

        $this->eventDispatcher->dispatch(
            new AddPasswordVaultEvent(
                $provider->getCode(),
                $request->getLogin(),
                $request->getPassword(),
                $request->getLogin2(),
                $request->getLogin3(),
                $usr->getUserid(),
                $request->getPartner(),
                [],
                null,
                $request->getNote()
            ),
            AddPasswordVaultEvent::NAME
        );

        return $result;
    }

    private function checkAccess($user, $pass)
    {
        $result = $user === 'awardwallet' && $pass === $this->callbackPassword;

        if (!$result) {
            $this->logger->notice("access denied for " . $user);
        }

        return $result;
    }
}
