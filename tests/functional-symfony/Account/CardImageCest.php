<?php

namespace Account;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\UserSteps;
use Codeception\Module\Aw;
use Ramsey\Uuid\Uuid;

/**
 * functional-symfony/Mobile/Account/CardImageCest.php.
 *
 * @group frontend-functional
 */
class CardImageCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const FRONT = 'cardImages/front.png';
    public const BACK = 'cardImages/back.png';
    public const INVALID = 'cardImages/invalid.png';
    public const BUCKET = 'dev-cardimagebucket';

    protected $providerId = 636;
    protected $userId;
    protected $username;
    protected $accountId;
    /**
     * @var UserSteps
     */
    protected $userSteps;
    /**
     * @var AccountSteps
     */
    protected $accountSteps;

    public function _before(\TestSymfonyGuy $I)
    {
        $scenario = $I->grabScenarioFrom($I);
        $this->userSteps = new UserSteps($scenario);
        $this->accountSteps = new AccountSteps($scenario);

        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);

        $csrf = $I->getContainer()->get('security.csrf.token_manager')->getToken('cardImage')->getValue();
        $I->haveHttpHeader('X-CSRF-TOKEN', $csrf);
        $I->amOnPage('/account/list?_switch_user=' . $this->username);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userId = null;
        $this->username = null;
        $this->providerId = null;
    }

    public function validAccountCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $I->wantTo('upload valid image for ACCOUNT');
        $this->accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getAccountUrl($I, $this->accountId), self::FRONT, ['AccountID' => $this->accountId]);
    }

    public function validSubaccountCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $I->wantTo('upload valid image for SUBACCOUNT');
        $this->accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $subaccountId = $I->haveInDatabase('SubAccount', ['AccountID' => $this->accountId, 'Code' => 'Code']);
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getSubaccountUrl($I, $this->accountId, $subaccountId), self::FRONT, ['SubAccountID' => $subaccountId]);
    }

    public function validCouponCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $I->wantTo('upload valid image for COUPON');
        $couponId = $I->createAwCoupon($this->userId, 'login', 'value');
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getCouponUrl($I, $couponId), self::FRONT, ['ProviderCouponID' => $couponId]);
    }

    public function invalidCardImageContentShouldNotBeUploaded(\TestSymfonyGuy $I)
    {
        $this->accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $I->sendPOST($this->getAccountUrl($I, $this->accountId), ['kind' => 'Front'], [
            'upload' => [
                'name' => pathinfo(self::INVALID, PATHINFO_BASENAME),
                'tmp_name' => $filename = $this->generateFileName(self::INVALID),
                'size' => filesize($filename),
                'type' => 'image/png',
                'error' => 0,
            ],
        ]
        );
        $I->seeResponseContainsJson(['success' => false]);
        $I->dontSeeInDatabase('CardImage', ['AccountID' => $this->accountId]);
    }

    public function invalidCardImageFileNameShouldNotBeUploaded(\TestSymfonyGuy $I)
    {
        $this->accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $I->sendPOST($this->getAccountUrl($I, $this->accountId), ['kind' => 'Back'], [
            'upload' => [
                'name' => '_{}^..image.png',
                'tmp_name' => $filename = $this->generateFileName(self::BACK),
                'size' => filesize($filename),
                'type' => 'image/png',
                'error' => 0,
            ],
        ]
        );
        $I->seeResponseContainsJson(['success' => false]);
        $I->dontSeeInDatabase('CardImage', ['AccountID' => $this->accountId]);
    }

    public function uploadedCardImageShouldBeDownloaded(\TestSymfonyGuy $I)
    {
        $this->accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $key = 'v1_' . $this->userId . '_' . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::FRONT)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'AccountID' => $this->accountId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => $this->accountId . 'Front.png',
            'UUID' => Uuid::uuid4(),
            'Width' => 100,
            'Height' => 100,
            'Format' => 'png',
        ]);

        $I->sendGET($this->getCardImageUrl($I, $cardImageId) . '?response_streaming=1');
        $I->seeHttpHeader('Content-Length', $size);
        // assertTrue($uploadedContent === $I->grabResponse(), 'downloaded image <> uploaded image');
    }

    public function unauthorizedUserShouldNotSeeUploadedImage(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser('cardimage' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $key = 'v1_' . $userId . '_' . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::FRONT)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'UserID' => $userId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'UUID' => Uuid::uuid4(),
            'Width' => 100,
            'Height' => 100,
            'Format' => 'png',
        ]);
        $I->sendGET($this->getCardImageUrl($I, $cardImageId) . '?response_streaming=1');
        $I->seeResponseCodeIs(404);
        // $I->seeResponseContainsJson(['error' => 'Not Found']);
    }

    public function unauthorizedUserShouldNotDeleteCardImage(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser('carimage' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $key = 'v1_' . $userId . '_' . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::FRONT)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'AccountID' => $accountId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'UUID' => Uuid::uuid4(),
            'Width' => 100,
            'Height' => 100,
            'Format' => 'png',
        ]);

        $I->sendDELETE($this->getAccountUrl($I, $accountId), ['kind' => 'Front']);
        $I->seeResponseCodeIs(404);
        // $I->seeResponseContainsJson(['error' => 'Not Found']);
        $I->seeS3Object(self::BUCKET, $key, $uploadedContent, true);
    }

    public function userShouldSeeCardImageLinkedToSharedAccount(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser('carimage' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $sharedAccountId = $this->accountSteps->createAwAccount($userId, $this->providerId, 'test', 'test');
        $storageKey = 'v1_' . $this->userId . '_' . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::FRONT)));
        $I->haveS3Object(self::BUCKET, $storageKey, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'StorageKey' => $storageKey,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'AccountID' => $sharedAccountId,
            'UUID' => Uuid::uuid4(),
            'Width' => 100,
            'Height' => 100,
            'Format' => 'png',
        ]);
        $this->userSteps->createConnection($this->userId, $userId);
        $this->accountSteps->shareAwAccountByConnection($sharedAccountId, $this->userSteps->createConnection($userId, $this->userId));
        $I->sendGET($this->getCardImageUrl($I, $cardImageId) . '?response_streaming=1');
        $I->seeResponseCodeIs(200);

        // assertTrue($uploadedContent === $I->grabResponse(), 'downloaded image <> uploaded image');
    }

    protected function validCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I, $uploadUrl, $kind, array $containerCriteria)
    {
        $place = (self::FRONT === $kind ? 'Front' : 'Back');
        $expectedContent = file_get_contents($filename = $this->generateFileName($kind));

        if (isset($containerCriteria['AccountID'])) {
            $clientName = $this->accountId;
        } elseif (isset($containerCriteria['SubAccountID'])) {
            $clientName = $this->accountId . 'sub' . $containerCriteria['SubAccountID'];
        } elseif (isset($containerCriteria['ProviderCouponID'])) {
            $clientName = $containerCriteria['ProviderCouponID'];
        }
        $clientName .= $place . '.' . pathinfo($filename, PATHINFO_EXTENSION);

        $I->sendPOST($uploadUrl, ['kind' => $place], [
            'upload' => [
                'name' => $clientName,
                'tmp_name' => $filename,
                'size' => $size = filesize($filename),
                'type' => 'image/png',
                'error' => 0,
            ],
        ]
        );

        foreach (['SubAccountID', 'AccountID', 'ProviderCouponID', 'UserID'] as $column) {
            if (!array_key_exists($column, $containerCriteria)) {
                $containerCriteria[$column] = null;
            }
        }

        $storageKey = $I->grabFromDatabase('CardImage', 'StorageKey', array_merge(
            [
                'UserID' => $this->userId,
                'FileSize' => $size,
                'FileName' => $clientName,
                'Kind' => (self::FRONT === $kind) ? CardImage::KIND_FRONT : CardImage::KIND_BACK,
            ],
            $containerCriteria
        ));

        $I->seeS3Object(self::BUCKET, $storageKey, $expectedContent, true);
    }

    protected function getAccountUrl($I, $accountId)
    {
        return $I->getContainer()->get('router')->generate('aw_card_image_account_handle', ['accountId' => $accountId]);
    }

    protected function getSubaccountUrl($I, $accountId, $subaccountId)
    {
        return $I->getContainer()->get('router')->generate('aw_card_image_subaccount_handle', ['accountId' => $accountId, 'subAccountId' => $subaccountId]);
    }

    protected function getCouponUrl($I, $couponId)
    {
        return $I->getContainer()->get('router')->generate('aw_card_image_coupon_handle', ['couponId' => $couponId]);
    }

    protected function getCardImageUrl($I, $id)
    {
        return $I->getContainer()->get('router')->generate('aw_card_image_download', ['cardImageId' => $id]);
    }

    protected function generateFileName($file)
    {
        return codecept_data_dir($file);
    }
}
