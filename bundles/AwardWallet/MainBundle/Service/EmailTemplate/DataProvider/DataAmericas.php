<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataAmericas extends AbstractFailTolerantDataProvider
{
    public function getDescription(): string
    {
        return 'North, Central and South America';
    }

    public function getTitle(): string
    {
        return 'Americas';
    }

    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = [
                /* north */ 'us', 'ca', 'bm', 'gl', 'pm',
                /* south */ 'mx', 'ar', 'bo', 'br', 'cl', 'co', 'ec', 'fk', 'gf', 'gy', 'py', 'pe', 'sr', 'uy', 've',
                /* central */ 'ai', 'ag', 'aw', 'bs', 'bb', 'bz', 'bq', 'ky', 'cr', 'cu', 'cw', 'dm', 'do', 'sv', 'gd', 'gp', 'gt', 'ht', 'jm', 'mq', 'ms', 'ni', 'pa', 'pr', 'kn', 'lc', 'vc', 'sx', 'tt', 'tc', 'vg', 'vi',
                /* 'bv', 'gs', 'bl', 'mf' */
            ];
        });

        return $options;
    }

    public function getSortPriority(): int
    {
        return 80;
    }
}
