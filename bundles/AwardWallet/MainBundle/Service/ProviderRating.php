<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Review;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderRating
{
    public const RATING_TRANSLATE_KEY = [
        0 => "rating.no_rating_submitted",
        1 => "rating.poor",
        2 => "rating.below_average",
        3 => "rating.average",
        4 => "rating.above_average",
        5 => "rating.outstanding",
    ];

    private LocalizeService $localizer;

    private TranslatorInterface $translator;

    private AwTokenStorage $tokenStorage;

    private EntityManagerInterface $em;

    private UserAvatar $userAvatar;

    private AppBot $appBot;

    private RouterInterface $router;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        LocalizeService $localizer,
        TranslatorInterface $translator,
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        UserAvatar $userAvatar,
        AppBot $appBot,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->localizer = $localizer;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->userAvatar = $userAvatar;
        $this->appBot = $appBot;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getReviewList(int $providerId): array
    {
        return $this->em->getConnection()->fetchAll('
            SELECT
                    r.*, UNIX_TIMESTAMP(r.UpdateDate) as _updatedate,
                    u.FirstName, u.PictureVer , u.PictureExt,
                    COUNT(ruu.ReviewUserUsefulID) AS _usefulCount
            FROM Review r
            LEFT JOIN Usr u ON (u.UserID = r.UserID)
            LEFT JOIN ReviewUserUseful ruu ON (ruu.ReviewID = r.ReviewID)
            WHERE
                    r.ProviderID = :providerId
            GROUP BY r.ReviewID
            ORDER BY r.UpdateDate DESC, r.CreationDate DESC
        ', ['providerId' => $providerId], [\PDO::PARAM_INT]);
    }

    public function getReviewData($providerIdOrUrlName): array
    {
        if (is_int($providerIdOrUrlName)) {
            $provider = ['providerid' => $providerIdOrUrlName];
        } elseif (is_string($providerIdOrUrlName)) {
            $provider = $this->getProviderByUrlName($providerIdOrUrlName);

            if (empty($provider)) {
                return [];
            }
        } else {
            throw new \Exception('Unsupported provider type');
        }

        $reviews = $this->getReviewList($provider['providerid']);
        $userId = $this->tokenStorage->getUser() instanceof Usr ? $this->tokenStorage->getUser()->getId() : null;
        $userReview = [];

        $filterReviews = [];

        foreach ($reviews as $review) {
            $review['Review'] = trim($review['Review']);

            if (empty($review['Review']) && $userId !== (int) $review['UserID']) {
                continue;
            }

            $review = $this->getTransformReviewFields($review);

            if ($userId === (int) $review['UserID']) {
                $userReview = $review;
            } elseif (empty($review['Approved'])) {
                $review['Review'] = $this->translator->trans('review-awaiting-moderation');
            }
            $review['avatar'] = !empty($review['PictureVer'])
                ? $this->userAvatar->getUserUrlByParts($review['UserID'], $review['PictureVer'])
                : null;
            $filterReviews[] = $review;
        }

        $scores = [];
        $providerRating = [];

        foreach (Review::SCORES_FIELDS as $field) {
            $scores[$field] = array_filter(array_column($reviews, $field));

            if (!empty($scores[$field])) {
                $providerRating[$field] = round(array_sum($scores[$field]) / count($scores[$field]));
            }
        }

        return [
            'reviewsList' => $filterReviews,
            'userReview' => $userReview,
            'provider' => $provider,
            'providerRating' => [
                'total' => count($providerRating) ? round(array_sum($providerRating) / count($providerRating)) : 0,
                'count' => count($reviews),
                'rating' => $providerRating,
            ],
        ];
    }

    /**
     * Get calculated and formatted fields of review.
     */
    public function getTransformReviewFields(array $review): array
    {
        $ratingValues = array_filter(
            $review,
            static function ($key) use ($review) {
                return in_array($key, Review::SCORES_FIELDS) && $review[$key] > 0;
            },
            ARRAY_FILTER_USE_KEY
        );

        $review['totalRating'] = empty($ratingValues) ? 0 : array_sum($ratingValues) / count($ratingValues);
        $review['totalRatingCaption'] = $this->translator->trans(self::RATING_TRANSLATE_KEY[round($review['totalRating'])]);
        $review['useful'] = ($review['Votes'] > 0) ? $review['YesVotes'] / $review['Votes'] : 0;
        $review['Review'] = htmlspecialchars_decode($review['Review']);
        $review['updatedateFormat'] = $this->localizer->formatDate(new \DateTime('@' . strtotime($review['UpdateDate'])));

        return $review;
    }

    /**
     * Get review by id.
     */
    public function getReview(int $reviewId): ?array
    {
        $reviewE = $this->em->getConnection()->fetchAssociative(
            'SELECT * FROM Review WHERE ReviewID = ?',
            [$reviewId],
            [\PDO::PARAM_INT]
        );

        if (empty($reviewE)) {
            return null;
        }

        $review = $this->getTransformReviewFields($reviewE);

        $provider = $this->em->getConnection()->fetchAssociative(
            'SELECT ProviderID, DisplayName, Name, ProgramName FROM Provider WHERE ProviderID = ?',
            [$reviewE['ProviderID']],
            [\PDO::PARAM_INT]
        );

        return [
            'review' => $review,
            'provider' => $provider,
        ];
    }

    /**
     * Add or update review.
     */
    public function addReview(array $userReview): array
    {
        if (!empty($userReview['ReviewID'])) {
            // update
            $review = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Review::class)->findOneBy(
                [
                    'providerid' => (int) $userReview['ProviderID'],
                    'userid' => $this->tokenStorage->getUser()->getId(),
                ]
            );
        } else {
            // insert
            /** @var Provider $provider */
            $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find((int) $userReview['ProviderID']);
            $review = new Review();
            $review->setUserid($this->tokenStorage->getUser());
            $review->setProviderid($provider);
            $review->setCreationdate(new \DateTime());

            $this->appBot->send(
                Slack::CHANNEL_AW_BLOG,
                '<' . $this->router->generate('aw_manager_list', ['Schema' => 'Review', 'Approved' => 0], UrlGeneratorInterface::ABSOLUTE_URL) . '|*New Review for _' . $provider->getDisplayname() . '_*>'
            );
        }

        foreach (Review::SCORES_FIELDS as $field) {
            $review->{'set' . $field}($userReview[$field] ?? 0);
        }

        $review->setApproved(empty($userReview['Review']));
        $review->setUpdatedate(new \DateTime());
        $review->setReview(htmlspecialchars($this->censorship($userReview['Review'] ?? '')));

        $this->em->persist($review);
        $this->em->flush();

        $userReview['ReviewID'] = $review->getReviewid();
        $userReview['Review'] = htmlspecialchars_decode($review->getReview());

        return $userReview;
    }

    public function voteUseful(int $reviewId, int $userId): bool
    {
        try {
            $this->em->getConnection()->insert(
                'ReviewUserUseful',
                [
                    'ReviewID' => $reviewId,
                    'UserID' => $userId,
                ],
                [\PDO::PARAM_INT, \PDO::PARAM_INT]
            );

            return true;
        } catch (UniqueConstraintViolationException $e) {
            return true;
        }

        return false;
    }

    /**
     * Get url name from origin name.
     */
    public static function urlName(string $name): string
    {
        $sLink = preg_replace("/[^a-zA-Zа-яёА-ЯЁ\.]/ums", "-", $name);
        $sLink = preg_replace("/\-{2,}/ims", "-", $sLink);
        $sLink = preg_replace("/^\-|\-$/ims", "", $sLink);

        return $sLink;
    }

    /**
     * Get provider id using name from url.
     */
    private function getProviderByUrlName(string $providerUrlName): array
    {
        $providerUrlName = explode('?', $providerUrlName)[0];
        $partProviderUrlName = explode('-', $providerUrlName)[0];

        $stateFilter = $this->authorizationChecker->isGranted('ROLE_STAFF')
            ? '(1 = 1)'
            : '(p.state > 0 OR p.state IS NULL)';

        $providers = $this->em->createQueryBuilder()
            ->select('p.providerid, p.name, p.displayname, p.programname, p.shortname, p.kind, p.blogTagsId, p.blogPostId')
            ->from(Provider::class, 'p')
            ->where('p.displayname like :name')
            ->andWhere($stateFilter)
            ->setParameter('name', $partProviderUrlName . "%")
            ->getQuery()->getArrayResult();

        foreach ($providers as $prov) {
            if (self::urlName($prov['displayname']) === $providerUrlName) {
                $provider = $prov;
                $provider['urlname'] = $providerUrlName;

                break;
            }
        }

        return $provider ?? [];
    }

    /**
     * Find bad words and replace them with ###.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function censorship(string $sText): string
    {
        $badwords = array_column($this->em->getConnection()->executeQuery("
            select word
            from BadWord"
        )->fetchAll(), 'word');

        if (empty($badwords)) {
            return $sText;
        }

        $sRegExp = "/\b(" . implode("|", array_map('preg_quote', array_values($badwords))) . ")\b/i";

        $sText = preg_replace_callback(
            $sRegExp,
            function ($arMatches) {
                return str_repeat("#", strlen($arMatches[0]));
            },
            $sText);

        return trim($sText);
    }

    private function translationMessage()
    {
        // for automatic extraction of translation; used in the template with variables in names
        $this->translator->trans(/** @Desc("How convenient does the award program make it to earn points?  For example, do they have special incentives for using the web or partners that will add points to your account when you purchase goods or services?") */
            'rating.abilitytoearn.desc');
        $this->translator->trans(/** @Desc("Does the award program make it easy for you to redeem your miles?  For example, can you redeem point for travel, hotels stays, etc. online?  Are there blackout dates?  Are there adequate seats reserved for award travel and hotel stays?") */
            'rating.easeofredemption.desc');
        $this->translator->trans(/** @Desc("Does the program allow you to use points with partners or only with the company associated with the program?  Can you use points for other things beside airfare or hotel stays, like merchandise?") */
            'rating.flexibility.desc');
        $this->translator->trans(/** @Desc("Does the award program have many or few partners when compared to other award programs?  This would include other airlines, hotels, etc.") */
            'rating.partners.desc');
        $this->translator->trans(/** @Desc("Once you get to the Elite levels of the award program how well do they take care of you?  For example, do they frequently upgrade you without having to ask, use points or pay?  Do they provide you with extra amenities in your room?") */
            'rating.elitebenefits.desc');
        $this->translator->trans(/** @Desc("How would you rate their online service?  Is it easy to book hotel stays or air travel?  Is their web site intuitive and easy to navigate?") */
            'rating.onlineservices.desc');
        $this->translator->trans(/** @Desc("How would you rate the award program customer service?  Are the customer service representatives courteous and helpful?") */
            'rating.customerservice.desc');

        $this->translator->trans(/** @Desc("Ability to earn points") */
            'rating.abilitytoearn');
        $this->translator->trans(/** @Desc("Ease of points redemption") */
            'rating.easeofredemption');
        $this->translator->trans(/** @Desc("Flexibility when redeeming points") */
            'rating.flexibility');
        $this->translator->trans(/** @Desc("Partners in Program") */
            'rating.partners');
        $this->translator->trans(/** @Desc("Elite Level Benefits") */
            'rating.elitebenefits');
        $this->translator->trans(/** @Desc("Online Services") */
            'rating.onlineservices');
        $this->translator->trans(/** @Desc("Customer Service") */
            'rating.customerservice');

        $this->translator->trans(/** @Desc("No rating submitted") */
            'rating.no_rating_submitted');
        $this->translator->trans(/** @Desc("Poor") */
            'rating.poor');
        $this->translator->trans(/** @Desc("Below Average") */
            'rating.below_average');
        $this->translator->trans(/** @Desc("Average") */
            'rating.average');
        $this->translator->trans(/** @Desc("Above Average") */
            'rating.above_average');
        $this->translator->trans(/** @Desc("Outstanding") */
            'rating.outstanding');
        $this->translator->trans(/** @Desc("This review is awaiting moderation") */
            'review-awaiting-moderation');
    }
}
