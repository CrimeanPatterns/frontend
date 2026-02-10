<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\MainBundle\Service\CapitalcardsHelper;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class OAuthTest extends BaseUserTest
{
    /**
     * @dataProvider authDataProvider
     */
    public function testAuthInfo(string $name, int $version, array $oldAuthInfo, ?array $newAuthInfo, callable $parseMethod)
    {
        $providerId = $this->aw->createAwProvider(null, null, [], [
            'Parse' => $parseMethod,
        ]);

        if ($version === 0) {
            $oldAuthInfoStr = $this->encodeOldAuthInfo($oldAuthInfo);
        } else {
            $oldAuthInfoStr = CapitalcardsHelper::encodeAuthInfo($oldAuthInfo);
        }

        $newAuthInfoStr = null;

        if ($newAuthInfo !== null) {
            if ($version === 0) {
                if ($newAuthInfo === $oldAuthInfo) {
                    // encoding will add random values, so copy encoded
                    $newAuthInfoStr = $oldAuthInfoStr;
                } else {
                    $newAuthInfoStr = $this->encodeOldAuthInfo($newAuthInfo);
                }
            } else {
                $newAuthInfoStr = CapitalcardsHelper::encodeAuthInfo($newAuthInfo);
            }
        }

        $accountId = $this->aw->createAwAccount(
            $this->user->getId(),
            $providerId,
            "some",
            "",
            ["AuthInfo" => $oldAuthInfoStr]
        );
        $this->aw->checkAccount($accountId);
        $this->assertEquals(
            $newAuthInfoStr,
            $this->db->grabFromDatabase("Account", "AuthInfo", ["AccountID" => $accountId])
        );
    }

    public function authDataProvider()
    {
        return [
            [
                "name" => "keep auth on v0",
                "version" => 0,
                "oldAuthInfo" => ["access_token" => "token1", "refresh_token" => "token2"],
                "newAuthInfo" => ["access_token" => "token1", "refresh_token" => "token2"],
                "parseMethod" => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalanceNA();
                },
            ],
            [
                "name" => "reset auth on v0",
                "version" => 0,
                "oldAuthInfo" => ["access_token" => "token1", "refresh_token" => "token2"],
                "newAuthInfo" => null,
                "parseMethod" => function () {
                    /** @var \TAccountChecker $this */
                    $this->InvalidAnswers["refresh_token"] = "none";
                    $this->SetBalanceNA();
                },
            ],
            [
                "name" => "keep auth on v1",
                "version" => 1,
                "oldAuthInfo" => ["rewards" => "tokens1", "tx" => "tokens2"],
                "newAuthInfo" => ["rewards" => "tokens1", "tx" => "tokens2"],
                "parseMethod" => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalanceNA();
                },
            ],
            [
                "name" => "reset rewards on v1",
                "version" => 1,
                "oldAuthInfo" => ["rewards" => "tokens1", "tx" => "tokens2"],
                "newAuthInfo" => ["rewards" => null, "tx" => "tokens2"],
                "parseMethod" => function () {
                    /** @var \TAccountChecker $this */
                    $this->InvalidAnswers["rewards"] = "none";
                    $this->SetBalanceNA();
                },
            ],
            [
                "name" => "reset tx on v1",
                "version" => 1,
                "oldAuthInfo" => ["rewards" => "tokens1", "tx" => "tokens2"],
                "newAuthInfo" => ["rewards" => "tokens1", "tx" => null],
                "parseMethod" => function () {
                    /** @var \TAccountChecker $this */
                    $this->InvalidAnswers["tx"] = "none";
                    $this->SetBalanceNA();
                },
            ],
        ];
    }

    private function encodeOldAuthInfo(array $params): string
    {
        return base64_encode(AESEncode(json_encode($params), $this->container->getParameter("local_passwords_key")));
    }
}
