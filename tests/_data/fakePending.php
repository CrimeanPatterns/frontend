<?php

return call_user_func(
    function () {
        return function ($accountId, $providerId = 17) {
            return [
                'UserEmailID' => 999999,
                'ProviderID' => $providerId,
                'AccountID' => $accountId,
                'ParsedJson' => '{"Properties":{"Balance":"61069","MedallionMilesYTD":"24,873","MedallionSegmentsYTD":"17","Name":"Alexi Vereschaga","Number":"2483512030","Login":"2483512030","Level":"Silver Medallion","ProviderCode":"delta"},"FromProvider":null}',
                'EmailDate' => new \DateTime(),
                'ParsedType' => 1,
                'EmailSubject' => 'Your test STATEMENT',
            ];
        };
    }
);
