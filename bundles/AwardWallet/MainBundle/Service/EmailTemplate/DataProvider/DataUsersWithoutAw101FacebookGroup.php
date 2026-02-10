<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use Aws\S3\S3Client;

class DataUsersWithoutAw101FacebookGroup extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();

        if ($this->container->get('kernel')->getEnvironment() === 'prod') {
            $fullnamesData = (string) $this->container->get(S3Client::class)->getObject([
                'Bucket' => 'aw-frontend-data',
                'Key' => 'awtravel101_users_refs15698.list',
            ])['Body'];
        } else {
            $fullnamesData = '';
        }

        $fullnames = [];

        foreach (explode("\n", $fullnamesData) as $nameLine) {
            if (StringUtils::isEmpty($nameLine = trim($nameLine))) {
                continue;
            }

            $nameParts = explode(' ', $nameLine);

            if (($namePartsCount = count($nameParts)) > 1) {
                $fullnames[] = mb_strtolower("{$nameParts[0]} {$nameParts[$namePartsCount - 1]}");
            } else {
                $fullnames[] = mb_strtolower($nameLine);
            }
        }

        array_walk($options, function ($option) use ($fullnames) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
            $option->hasNotUserFullname = $fullnames;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users from US or from unknown country (detected by last logon ip (if any) or registration ip) <br/>
                whose full name DOES NOT exists in list exported (last export: April 16, 2018) from facebook aw101 group';
    }

    public function getTitle(): string
    {
        return 'US users NOT included in Facebook Aw101 group (date: April 16, 2018)';
    }

    public function getGroup(): string
    {
        return Group::FACEBOOK_AW101;
    }
}
