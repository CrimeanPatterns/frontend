<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class GroupUserLink extends AbstractDbEntity
{
    public function __construct(int $siteGroupId, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'SiteGroupID' => $siteGroupId,
        ]));
    }
}
