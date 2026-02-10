<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StackTraceUtils;
use AwardWallet\MainBundle\Service\Billing\AppleStoreCallbackTask;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\UnknownPlatformException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderInterface;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderRegistry;
use AwardWallet\MainBundle\Service\InAppPurchase\PurchaseInterface;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTaskExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Parameter;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Service;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Herrera\Version\Parser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InAppPurchaseController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $paymentLogger,
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
        $this->logger = $paymentLogger;
    }

    /**
     * @Route("/inAppPurchase/confirm/{device}", name="aw_mobile_purchase_confirm_old", methods={"POST"})
     * @Route("/inAppPurchase/confirm", name="aw_mobile_purchase_confirm", methods={"POST"})
     * @Route("/inAppPurchase/refund", name="aw_mobile_purchase_refund", methods={"POST"})
     * @JsonDecode()
     */
    public function confirmAction(
        Request $request,
        $device = null,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $awTokenStorage,
        ProviderRegistry $registry,
        CallbackTaskExecutor $awSyncExecutorCallback
    ) {
        $user = $this->isAuthorized($authorizationChecker) ? $awTokenStorage->getUser() : null;

        $requestData = $request->request->all();
        $requestContent = $request->getContent();
        $apiVersion = $request->headers->get(MobileHeaders::MOBILE_VERSION);

        try {
            $provider = $registry->detectProvider($request, true);
            $platformId = $provider->getPlatformId();
            $userId = $user ? $user->getId() : null;
            $awSyncExecutorCallback->execute(new CallbackTask(
                function (
                    /** @Service("monolog.logger.payment") */
                    LoggerInterface $paymentLogger,
                    ApiVersioningService $apiVersioningService,
                    ProviderRegistry $providerRegistry,
                    Billing $billing,
                    EntityManagerInterface $entityManager,
                    CallbackTaskExecutor $callbackTaskExecutor
                ) use ($platformId, $userId, $requestData, $requestContent, $apiVersion) {
                    $logCritical = fn (string $module, \Throwable $e) => self::logCritical($module, $e, $paymentLogger);
                    $delayedRetry = fn (\Exception $e) => $callbackTaskExecutor->expBackoffDelayTask(5, $e, 4);
                    $apiVersioningService
                        ->setVersionsProvider(new MobileVersions('ios'))
                        ->setVersion(Parser::toVersion($apiVersion));
                    /** @var ProviderInterface $provider */
                    $provider = $providerRegistry->getProvider($platformId);
                    /** @var Usr $user */
                    $user = isset($userId) ? $entityManager->getRepository(Usr::class)->find($userId) : null;

                    try {
                        $paymentLogger->info(
                            \sprintf("device: %s", $provider->getPlatformId()),
                            \array_merge(LoggerContext::get($user), [
                                'Content' => $requestContent,
                            ])
                        );
                        $purchases = $provider->validate($requestData, $user);

                        $paymentLogger->info(\sprintf("purchases: %d", \count($purchases)), LoggerContext::get($user));

                        foreach ($purchases as $purchase) {
                            $billing->processing($purchase);
                        }
                    } catch (VerificationException $e) {
                        $paymentLogger->warning(\sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());

                        if ($e->isTemporary()) {
                            $paymentLogger->info("temporary verification error, skip");
                            $delayedRetry($e);
                        }

                        throw $e;
                    } catch (\Throwable $e) {
                        $logCritical('confirm', $e, $paymentLogger);

                        throw $e;
                    }
                },
                [
                    'logger' => new Service("monolog.logger.payment"),
                    'entityManager' => new Service("doctrine.orm.entity_manager"),
                ]
            ));
        } catch (UnknownPlatformException $e) {
            $this->logger->warning($e->getMessage());

            return $this->errorJsonResponse($e->getMessage(), [
                'confirmed' => false,
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            return $this->jsonResponse([
                'confirmed' => false,
            ]);
        }

        return $this->jsonResponse([
            'confirmed' => true,
        ]);
    }

    /**
     * @Route("/inAppPurchase/product", name="aw_mobile_purchase_subscription", methods={"GET"})
     */
    public function getSubscriptionAction(Request $request, ProviderRegistry $providerRegistry)
    {
        try {
            /** @var AbstractProvider $provider */
            $provider = $providerRegistry->detectProvider($request, true);

            return $this->jsonResponse([
                'productId' => $provider->getPlatformProductId(
                    AbstractSubscription::getAvailableSubscription($this->getCurrentUser())
                ),
            ]);
        } catch (UnknownPlatformException $e) {
            $this->logger->warning($e->getMessage());

            return $this->errorJsonResponse($e->getMessage());
        }
    }

    /**
     * @Route("/inAppPurchase/consumables", name="aw_mobile_purchase_consumables", methods={"GET"})
     */
    public function getConsumablesAction(Request $request, ProviderRegistry $providerRegistry)
    {
        try {
            /** @var AbstractProvider $provider */
            $provider = $providerRegistry->detectProvider($request, true);
            $user = $this->getCurrentUser();
            $count = $user !== false && $user instanceof Usr ? $user->getBalanceWatchCredits() : 0;

            return $this->jsonResponse([
                'consumables' => $provider->getConsumablesForSale(),
                'count' => $count,
            ]);
        } catch (UnknownPlatformException $e) {
            $this->logger->warning($e->getMessage());

            return $this->errorJsonResponse($e->getMessage());
        }
    }

    /**
     * @Route("/inAppPurchase/status", name="aw_mobile_purchase_status", methods={"POST"})
     * @JsonDecode()
     */
    public function statusAction(
        Request $request,
        Producer $producer,
        $mobileIapAppleSecret
    ) {
        if (!$request->request->has("notification_type")) {
            return $this->successJsonResponse();
        }

        $params = [];

        foreach ($request->request as $name => $param) {
            if ($name == "password") {
                continue;
            }
            $params[$name] = $param;
        }

        $this->logger->info("ios status update notification", ["appleStoreRequest" => json_encode($params), "notificationType" => $request->request->get("notification_type", '')]);

        if ($request->request->get("notification_type") == "INITIAL_BUY") {
            $this->logger->info("Initial buy");

            return $this->successJsonResponse();
        }

        if ($request->request->get("password") !== $mobileIapAppleSecret) {
            $this->logger->info("Wrong password");

            return $this->errorJsonResponse("Wrong password");
        }

        if ($request->request->get("environment") !== "PROD") {
            $this->logger->info("Bad environment");

            return $this->errorJsonResponse("Bad environment");
        }

        $transactionReceipt = $request->request->get('latest_receipt') ?? $request->request->get('latest_expired_receipt');

        if ($transactionReceipt === null && $request->request->has('unified_receipt') && array_key_exists('latest_receipt', $request->request->get('unified_receipt'))) {
            $transactionReceipt = $request->request->get('unified_receipt')['latest_receipt'];
        }

        if ($transactionReceipt === null && $request->request->has('unified_receipt') && array_key_exists('latest_expired_receipt', $request->request->get('unified_receipt'))) {
            $transactionReceipt = $request->request->get('unified_receipt')['latest_expired_receipt'];
        }

        if (empty($transactionReceipt)) {
            $this->logger->info("Empty receipt");

            return $this->errorJsonResponse("Empty receipt");
        }

        $task = new AppleStoreCallbackTask($transactionReceipt);
        $producer->publish($task);

        return $this->successJsonResponse();
    }

    /**
     * @Route("/inAppPurchase/restore", name="aw_mobile_purchase_restore", methods={"POST"})
     * @JsonDecode()
     */
    public function restoreAction(
        Request $request,
        Process $process
    ) {
        $requestContent = $request->getContent();
        $this->logger->info("ios restore receipts", [
            "restore" => 1,
            "request" => $requestContent,
        ]);

        $appStoreReceipt = $request->request->get("appStoreReceipt");
        $receiptForTransaction = $request->request->get("receiptForTransaction");
        $user = $this->getCurrentUser();
        $userId = null;
        $updateUser = false;

        if ($user !== false && $user instanceof Usr) {
            $userId = $user->getUserid();
            $user->setIosRestoredReceipt(true);
            $this->getDoctrine()->getManager()->flush();
        } else {
            $updateUser = true;
        }

        if ($appStoreReceipt) {
            $process->execute(new CallbackTask(
                function (
                    /** @Service("monolog.logger.payment") */
                    LoggerInterface $paymentLogger,
                    Provider $provider,
                    Billing $billing,
                    EntityManagerInterface $entityManager,
                    CallbackTaskExecutor $callbackTaskExecutor,
                    /** @Parameter("kernel.project_dir") */
                    string $kernelProjectDir
                ) use ($appStoreReceipt, $requestContent, $updateUser, $userId) {
                    // define helpers
                    $logCritical = fn (string $module, \Throwable $e) => self::logCritical($module, $e, $paymentLogger);
                    $delayedRetry = fn (\Exception $e) => $callbackTaskExecutor->expBackoffDelayTask(5, $e, 4);
                    $updateIosRestoredReceipt = fn (array $purchases) => self::updateIosRestoredReceipt($purchases, $entityManager);
                    $provider->setUseLatestMobileVersion(true);
                    /** @var Usr $user */
                    $user = null;

                    if (isset($userId)) {
                        $user = $entityManager->getRepository(Usr::class)->find($userId);
                    }

                    try {
                        $paymentLogger->info(
                            \sprintf("device: %s", $provider->getPlatformId()),
                            \array_merge(LoggerContext::get($user), [
                                'Content' => $requestContent,
                            ])
                        );
                        $purchases = $provider->validate([
                            'type' => 'ios-appstore',
                            'appStoreReceipt' => $appStoreReceipt,
                        ], $user);

                        $paymentLogger->info(\sprintf("purchases: %d", \count($purchases)), LoggerContext::get($user));

                        foreach ($purchases as $purchase) {
                            $billing->processing($purchase, true);
                        }

                        if ($updateUser && \count($purchases) > 0) {
                            $updateIosRestoredReceipt($purchases, $entityManager);
                        }
                    } catch (VerificationException $e) {
                        $paymentLogger->warning(\sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());

                        if ($e->isTemporary()) {
                            $paymentLogger->info("temporary verification error, skip");
                            $delayedRetry($e);
                        }

                        throw $e;
                    } catch (\Throwable $e) {
                        $logCritical("restore", $e, $paymentLogger);

                        throw $e;
                    }
                },
                [
                    'logger' => new Service("monolog.logger.payment"),
                    'entityManager' => new Service("doctrine.orm.entity_manager"),
                ]
            ));
        }

        if ($receiptForTransaction && is_array($receiptForTransaction)) {
            foreach ($receiptForTransaction as $transactionId => $appStoreReceipt) {
                $process->execute(new CallbackTask(
                    function (
                        /** @Service("monolog.logger.payment") */
                        LoggerInterface $paymentLogger,
                        Provider $provider,
                        Billing $billing,
                        EntityManagerInterface $entityManager,
                        CallbackTaskExecutor $callbackTaskExecutor
                    ) use ($transactionId, $appStoreReceipt, $requestContent, $updateUser, $userId) {
                        $logCritical = fn (string $module, \Throwable $e) => self::logCritical($module, $e, $paymentLogger);
                        $delayedRetry = fn (\Exception $e) => $callbackTaskExecutor->expBackoffDelayTask(5, $e, 4);
                        $updateIosRestoredReceipt = fn (array $purchases) => self::updateIosRestoredReceipt($purchases, $entityManager);
                        /** @var $this CallbackTaskExecutor */
                        $provider->setUseLatestMobileVersion(true);
                        /** @var Usr $user */
                        $user = null;

                        if (isset($userId)) {
                            $user = $entityManager->getRepository(Usr::class)->find($userId);
                        }

                        try {
                            $paymentLogger->info(
                                \sprintf("device: %s", $provider->getPlatformId()),
                                \array_merge(LoggerContext::get($user), [
                                    'Content' => $requestContent,
                                ])
                            );
                            $purchases = $provider->validate([
                                'type' => 'ios-appstore',
                                'id' => $transactionId,
                                'transactionReceipt' => $appStoreReceipt,
                            ], $user);

                            $paymentLogger->info(\sprintf("purchases: %d", \count($purchases)), LoggerContext::get($user));

                            foreach ($purchases as $purchase) {
                                $billing->processing($purchase, true);
                            }

                            if ($updateUser && \count($purchases) > 0) {
                                $updateIosRestoredReceipt($purchases, $entityManager);
                            }
                        } catch (VerificationException $e) {
                            $paymentLogger->warning(\sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());

                            if ($e->isTemporary()) {
                                $paymentLogger->info("temporary verification error, skip");
                                $delayedRetry($e);
                            }

                            throw $e;
                        } catch (\Throwable $e) {
                            $logCritical("restore", $e, $paymentLogger);

                            throw $e;
                        }
                    },
                    [
                        'logger' => new Service("monolog.logger.payment"),
                        'entityManager' => new Service("doctrine.orm.entity_manager"),
                    ]
                ));
            }
        }

        return $this->successJsonResponse();
    }

    /**
     * @param PurchaseInterface[] $purchases
     */
    public static function updateIosRestoredReceipt(array $purchases, EntityManagerInterface $entityManager): void
    {
        $entityManager->getConnection()->executeUpdate('
            UPDATE Usr SET IosRestoredReceipt = 1 WHERE UserID IN (?)
        ', [array_filter(array_unique(array_map(function ($purchase) {
            /** @var PurchaseInterface $purchase */
            if ($purchase->getUser()) {
                return $purchase->getUser()->getUserid();
            }

            return null;
        }, $purchases)))], [Connection::PARAM_INT_ARRAY]);
    }

    public static function logCritical(string $module, \Throwable $e, LoggerInterface $logger): void
    {
        $logger->critical(
            \sprintf(
                "In-App Purchase exception (%s): %s (%s) at %s line %s",
                $module,
                TraceProcessor::filterMessage($e),
                \get_class($e),
                $e->getFile(),
                $e->getLine()
            ),
            ['trace' => StackTraceUtils::flattenExceptionTraces($e)]
        );
    }
}
