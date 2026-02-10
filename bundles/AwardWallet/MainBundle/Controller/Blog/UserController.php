<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\BlogUserReport;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\Blog\BlogApi;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private BlogApi $blogApi;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        BlogApi $blogApi
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->blogApi = $blogApi;
    }

    /**
     * @Route("/api/blog/user-state", methods={"POST"}, name="aw_blog_api_user_state")
     */
    public function userState(Request $request): JsonResponse
    {
        $jsonResponse = function (int $userId, string $refCode, bool $isAwPlus, bool $isBlogPostAds): JsonResponse {
            return new JsonResponse([
                'userId' => $userId,
                'refCode' => $refCode,
                'isAwPlus' => $isAwPlus,
                'isPA' => $isBlogPostAds || !$isAwPlus,
            ]);
        };

        if (!($this->tokenStorage->getUser() instanceof Usr)) {
            if ($request->request->has('userId') && $request->request->has('isNative')) {
                $userId = $request->request->getInt('userId');
                $userRow = $this->entityManager->getConnection()->fetchAssociative(
                    'SELECT RefCode, AccountLevel, IsBlogPostAds FROM Usr WHERE UserID = ? LIMIT 1',
                    [$userId],
                    [\PDO::PARAM_INT]
                );

                if (!empty($userRow)) {
                    return $jsonResponse(
                        $userId,
                        $userRow['RefCode'],
                        ACCOUNT_LEVEL_AWPLUS === (int) $userRow['AccountLevel'],
                        1 === (int) $userRow['IsBlogPostAds'],
                    );
                }
            }

            return new JsonResponse(['userId' => null]);
        }

        return $jsonResponse(
            $this->tokenStorage->getUser()->getId(),
            $this->tokenStorage->getUser()->getRefcode(),
            $this->tokenStorage->getUser()->isAwPlus(),
            $this->tokenStorage->getUser()->isBlogPostAds(),
        );
    }

    /**
     * @Route("/api/blog/user-info", methods={"GET"}, name="aw_blog_api_user_info")
     * @return JsonResponse
     */
    public function getUserInfoAction(AccountListManager $accountListManager, OptionsFactory $optionsFactory)
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();

        if (empty($user)) {
            return new JsonResponse(["user_id" => null]);
        }

        $options = $optionsFactory
            ->createDefaultOptions()
            ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
            ->set(Options::OPTION_FILTER,
                " AND p.Code = 'aa' AND a.ErrorCode = " . ACCOUNT_CHECKED . " and a.Disabled = 0")
            ->set(Options::OPTION_COUPON_FILTER, " AND 0 = 1")
            ->set(Options::OPTION_LOAD_PHONES, false)
            ->set(Options::OPTION_INDEXED_BY_HID, true)
            ->set(Options::OPTION_LOAD_SUBACCOUNTS, false);

        $accounts = $accountListManager->getAccountList($options)->getAccounts();
        $accounts = array_filter(
            $accounts,
            function ($account) {
                return $account['Access']['read_number'];
            });
        $accounts = array_map(
            function ($account) {
                return [
                    'account_id' => $account['ID'],
                    'number' => $account['MainProperties']['Number']['Number'] ?? $account['Login'],
                    'owner' => $account['UserName'],
                    'balance' => $account['Balance'],
                    'expiration' => $account['ExpirationDate'],
                ];
            },
            $accounts);

        $response = [
            "user_id" => $user->getUserid(),
            'refCode' => $user->getRefcode(),
            "accounts" => array_values($accounts),
        ];

        if (empty(trim($user->getFirstname())) || empty(trim($user->getLastname()))) {
            $response['isRequiredName'] = true;
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/blog/update-notifications", methods={"POST"}, name="aw_blog_update_notifications")
     * @return JsonResponse
     */
    public function updateBlogNotifications(Request $request, LoggerInterface $emailLogger)
    {
        $this->blogApi->checkAuth($request);

        $user = $this->tokenStorage->getUser();

        if (null === $user) {
            $email = $request->request->get("email");
            $user = $this->entityManager->getRepository(Usr::class)->findOneBy(['email' => $email]);
        }

        if (!empty($user)) {
            $emailLogger->info(
                "enabling blog posts notifications",
                ["UserID" => $user->getUserid(), "email" => $user->getEmail()]
            );
            $user->setWpNewBlogPosts(true);
            $user->setMpNewBlogPosts(true);
            $user->setEmailNewBlogPosts(true);
            $this->entityManager->flush();

            return new JsonResponse(["status" => "success"]);
        } else {
            return new JsonResponse(["status" => "not_found"]);
        }
    }

    /**
     * @Route("/api/blog/reader", name="aw_blog_reader")
     */
    public function readerUserReport(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $report = $request->request->get('report', []);

        if (empty($report)) {
            return new JsonResponse(['success' => false]);
        }

        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();
        $isAuth = !empty($user);

        if (empty($user) && !empty($report)) {
            if (1 === count($report) && !array_key_exists('none', $report)) {
                $refCode = array_keys($report)[0];
            } elseif (1 < count($report)) {
                unset($report['none']);
                $refCode = array_keys($report)[0];
            }

            if (!empty($refCode)) {
                $user = $this->entityManager->getRepository(Usr::class)->findOneBy(['refcode' => $refCode]);
            }
        }

        if (empty($user)) {
            return new JsonResponse(['success' => false]);
        }

        $connection = $this->entityManager->getConnection();

        $last = $connection->fetchAssoc('SELECT UNIX_TIMESTAMP(InTime) AS InTime, UNIX_TIMESTAMP(OutTime) AS OutTime FROM BlogUserReport WHERE UserID = ' . $user->getId() . ' ORDER BY OutTime DESC, InTime DESC LIMIT 1');
        $min = $last['OutTime'] ?? $last['InTime'] ?? 0;

        $normalizeData = static function ($data): array {
            $result = [];
            $index = 0;

            foreach ($data as $postId => $timeList) {
                foreach ($timeList as $stamp) {
                    $type = substr($stamp, 0, 1);
                    $time = (int) substr($stamp, 1);

                    if ('i' === $type) {
                        ++$index;
                        $result[$index] = ['postId' => $postId, 'in' => $time, 'out' => null];
                    } elseif ('o' === $type) {
                        $result[$index]['out'] = $time;
                    }
                }
            }

            return $result;
        };

        $persistData = function ($data, $tzOffset) use (&$min, $user, $doctrine, $isAuth) {
            $future = time() + 86400;

            foreach ($data as $reading) {
                $in = (int) $reading['in'];
                $out = (int) $reading['out'];

                if ($in > $out || $min >= $out || $out > $future) {
                    continue;
                }

                if ($out - $in > 60 * 15) {
                    $out = $in + (60 * 15);
                }

                $blogUserReport = (new BlogUserReport())
                    ->setUser($user)
                    ->setBlogPostId($reading['postId'])
                    ->setInTime(new \DateTime('@' . $in))
                    ->setOutTime(new \DateTime('@' . $out))
                    ->setTimeZoneOffset($tzOffset)
                    ->setIsAuthorized($isAuth);

                $min = $out;
                $this->entityManager->persist($blogUserReport);

                try {
                    $this->entityManager->flush();
                } catch (UniqueConstraintViolationException $e) {
                    $doctrine->resetManager();
                }
            }
            // $this->entityManager->clear();
        };

        $tzOffset = $request->request->get('tzOffset', 0);

        if (!empty($tzOffset)) {
            $tzOffset = (int) $tzOffset;
        }

        if (is_array($report)) {
            if (array_key_exists('none', $report)) {
                $data = $normalizeData($report['none']);
                $persistData($data, $tzOffset);
            }

            if (array_key_exists($user->getRefcode(), $report)) {
                $data = $normalizeData($report[$user->getRefcode()]);
                $persistData($data, $tzOffset);
            }

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false]);
    }

    /**
     * Adding a new visit for the specified blog page (ajax requests only).
     *
     * @Route("/api/blog/visits", name="aw_blog_visits_create", methods={"POST"}, options={"expose"=true}, condition="request.isXmlHttpRequest()")
     * @JsonDecode()
     */
    public function visitCreate(Request $request, PageVisitLogger $pageVisitLogger): JsonResponse
    {
        if (null === $this->tokenStorage->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid request data']);
        }

        $pageName = $request->request->get('pageName');

        if (StringUtils::isEmpty($pageName)) {
            return new JsonResponse(['success' => false, 'message' => '"PageName" cannot be blank.']);
        }

        $pageVisitLogger->log($pageName);

        return new JsonResponse(['success' => true]);
    }
}
