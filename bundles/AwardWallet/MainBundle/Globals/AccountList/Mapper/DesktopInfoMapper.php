<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Review;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ProviderRating;

class DesktopInfoMapper extends DesktopListMapper
{
    public function alterTemplate(MapperContext $mapperContext)
    {
        parent::alterTemplate($mapperContext);

        $mapperContext->alterDataTemplateBy([
            'UserName' => null,
            'UserID' => null,
            'UserAgentID' => null,
            'comment' => null,
            'ProviderID' => null,
            'LastChangeDate' => null,
            'LastChangeDateTs' => null,
            'LastChangeDateFrendly' => null,
            'ExpirationDateFrendly' => null,
            'ChangedPositive' => null,
            'SuccessCheckDate' => null,
            'SuccessCheckDateTs' => null,
            'SuccessCheckDateFrendly' => null,
            'LastUpdatedDate' => null,
            'LastUpdatedDateTs' => null,
            'LastUpdatedDateFrendly' => null,
            'Properties' => null,
            'MainProperties' => null,
            'AvatarSrc' => null,
            'Rating' => null,
            'RatingUrl' => null,
            'RatingCount' => null,
            'PromotionsBlogPost' => null,
            'ExpirationBlogPost' => null,
        ]);
    }

    public function filter(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::filter($mapperContext, $accountID, $accountFields);
        $accountFields = $this->blogPosts($accountFields);
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        // Avatar for details popup
        $useAgent = false;

        if ($accountFields['UserID'] == $user->getUserid()) {
            if (!empty($accountFields['UserAgentID'])) {
                $useAgent = true;
            }
        } else {
            if (!empty($accountFields['UserAgentPictureVer'])) {
                $useAgent = true;
            }
        }

        if ($useAgent) {
            $accountFields['PictureVer'] = $accountFields['UserAgentPictureVer'];
            $accountFields['PictureExt'] = $accountFields['UserAgentPictureExt'];
            $accountFields['PictureID'] = $accountFields['UserAgentID'];
            $accountFields['PictureDir'] = 'userAgent';
        } else {
            $accountFields['PictureVer'] = $accountFields['UserPictureVer'];
            $accountFields['PictureExt'] = $accountFields['UserPictureExt'];
            $accountFields['PictureID'] = $accountFields['UserID'];
            $accountFields['PictureDir'] = 'user';
        }

        if (!empty($accountFields['PictureVer'])) {
            $accountFields['AvatarSrc'] = PicturePath(
                "/images/uploaded/" . $accountFields['PictureDir'],
                "small",
                $accountFields['PictureID'],
                $accountFields['PictureVer'],
                $accountFields['PictureExt'],
                "file"
            );
        }

        if (!empty($accountFields['FamilyMemberName'])) {
            $accountFields['UserName'] = $accountFields['FamilyMemberName'];
        }

        // Last change date (Time ago format)
        if (isset($accountFields['LastChangeDateTs'])) {
            $accountFields['LastChangeDateFrendly'] = $this->intervalFormatter->longFormatViaDateTimes(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['LastChangeDateTs'])
            );
        }

        // Expiration date (Time ago format)
        $accountFields['ExpirationDateFrendly'] = $accountFields['ExpirationDate'];

        if ($accountFields['ExpirationDate'] && !in_array($accountFields['ExpirationStateType'], [
            ExpirationDateResolver::EXPIRE_STATE_TYPE_UNKNOWN,
            ExpirationDateResolver::EXPIRE_STATE_TYPE_NOT_EXPIRE,
        ])) {
            $accountFields['ExpirationDateFrendly'] = $this->intervalFormatter->shortFormatViaDates(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['ExpirationDateTs'])
            );
            $accountFields['ExpirationDate'] = $this->localizer->formatDate(new \DateTime('@' . $accountFields['ExpirationDateTs']));
        }

        if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
            foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                $subAccount['ExpirationDateFrendly'] = $subAccount['ExpirationDate'];

                if ($subAccount['ExpirationDate'] && !in_array($subAccount['ExpirationStateType'], [
                    ExpirationDateResolver::EXPIRE_STATE_TYPE_UNKNOWN,
                    ExpirationDateResolver::EXPIRE_STATE_TYPE_NOT_EXPIRE,
                ])) {
                    $subAccount['ExpirationDateFrendly'] = $this->intervalFormatter->shortFormatViaDateTimes(
                        $this->clock->current()->getAsDateTime(),
                        new \DateTime('@' . $subAccount['ExpirationDateTs'])
                    );
                    $subAccount['ExpirationDate'] = $this->localizer->formatDate(new \DateTime('@' . $subAccount['ExpirationDateTs']));
                }
            }
        }

        // Check date (Time ago format)
        if (isset($accountFields['SuccessCheckDateTs'])) {
            $accountFields['SuccessCheckDateFrendly'] = $this->intervalFormatter->longFormatViaDateTimes(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['SuccessCheckDateTs'])
            );
        }

        // Last Updated, the nearest date from SuccessCheckDate and LastChangeDate
        if (isset($accountFieldsp['LastUpdatedDateTs'])) {
            $accountFields['LastUpdatedDate'] = $this->localizer->formatDateTime(
                $this->localizer->correctDateTime(new \DateTime('@' . $accountFields['LastUpdatedDateTs'])),
                'full',
                'short',
                $locale
            );
            $accountFields['LastUpdatedDateFrendly'] = $this->intervalFormatter->longFormatViaDateTimes(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['LastUpdatedDateTs'])
            );
        }

        // Rating
        if (!empty($accountFields['ProviderID'])) {
            $review = $this->em->getRepository(Review::class)->getProviderRating($accountFields['ProviderID']);
            $accountFields['Rating'] = $this->localizer->formatNumber($review['rating']);
            $accountFields['RatingCount'] = $this->localizer->formatNumber($review['count']);
            $accountFields['RatingUrl'] = ProviderRating::urlName($accountFields['DisplayName']);
        }

        return $accountFields;
    }

    protected function mapCoupon(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapCoupon($mapperContext, $accountID, $accountFields);

        if (isset($accountFields['ConnectedAccount'], $accountFields['AccountStatus'])) {
            $extendedStatus = $accountFields['AccountStatus'];

            if (
                isset($accountFields['LoginFieldFirst'], $accountFields['LoginFieldLast'])
                && $accountFields['LoginFieldLast'] !== $accountFields['LoginFieldFirst']
            ) {
                $extendedStatus .= " ({$accountFields['LoginFieldLast']})";
            }

            $accountFields['DisplayName'] = $extendedStatus;
            $accountFields['DisplayNameFormated'] = preg_replace(
                "/^([^\(]+)(\(.+)$/ims",
                "<span style='white-space:nowrap'>$1</span><wbr><span style='white-space:nowrap'>$2</span>",
                $accountFields['DisplayName']
            );
        }

        return $accountFields;
    }

    private function blogPosts(array $accountFields): array
    {
        if (!empty($accountFields['Blogs']['BlogIdsPromos'])) {
            $accountFields['PromotionsBlogPost'] = array_map(static function ($item) {
                $item['postURL'] = StringUtils::replaceVarInLink(
                    $item['postURL'],
                    ['cid' => 'acct-details-promos', 'mid' => 'web'],
                    true
                );

                return $item;
            }, $accountFields['Blogs']['BlogIdsPromos']);
        }

        if (!empty($accountFields['Blogs']['BlogIdsMileExpiration'])) {
            if ($this->isPassport($accountFields) && !$this->isPassport($accountFields, Country::UNITED_STATES)) {
                return $accountFields;
            }

            $accountFields['ExpirationBlogPost'] = array_map(static function ($item) {
                $item['postURL'] = StringUtils::replaceVarInLink(
                    $item['postURL'],
                    ['cid' => 'acct-details-exp', 'mid' => 'web'],
                    true
                );

                return $item;
            }, $accountFields['Blogs']['BlogIdsMileExpiration']);

            $accountFields['ExpirationBlogPost'] = $accountFields['ExpirationBlogPost'][0];
        }

        return $accountFields;
    }
}
