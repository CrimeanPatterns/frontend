<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Module\Aw;
use Ramsey\Uuid\Uuid;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

/**
 * @group mobile
 * @group frontend-functional
 */
class CardImageCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const FRONT = 'cardImages/front.png';
    public const BACK = 'cardImages/back.png';
    public const INVALID = 'cardImages/invalid.png';
    public const BUCKET = 'dev-cardimagebucket';
    /**
     * @var int
     */
    protected $providerId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'cardimg-', Aw::DEFAULT_PASSWORD, [], false);

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.19.0+100500');
        $this->providerId = $I->createAwProvider(
            'testprovider' . $I->grabRandomString(5),
            'testprovid' . $I->grabRandomString(5)
        );
    }

    public function validAccountCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getAccountUrl($accountId), self::FRONT, ['AccountID' => $accountId]);
    }

    public function validCouponCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->userId, 'login', 'value');
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getCouponUrl($couponId), self::FRONT, ['ProviderCouponID' => $couponId]);
    }

    public function validSubaccountCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $subaccountId = $I->haveInDatabase('SubAccount', ['AccountID' => $accountId, 'Code' => 'Code']);
        $this->validCardImageShouldBeUploadedAndSaved($I, $this->getSubaccountUrl($accountId, $subaccountId), self::FRONT, ['SubAccountID' => $subaccountId]);
    }

    public function invalidCardImageContentShouldNotBeUploaded(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $I->sendPOST(
            $this->getAccountUrl($accountId),
            ['kind' => 'Front'],
            [
                'upload' => [
                    'name' => 'image.png',
                    'tmp_name' => $filename = $this->generateFileName(self::INVALID),
                    'size' => filesize($filename),
                    'type' => 'image/png',
                    'error' => 0,
                ],
            ]
        );
        $I->seeResponseContainsJson(['error' => 'Invalid image format.']);
        $I->dontSeeInDatabase('CardImage', ['AccountID' => $accountId]);
    }

    public function invalidCardImageFileNameShouldNotBeUploaded(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $I->sendPOST(
            $this->getAccountUrl($accountId),
            ['kind' => 'Back'],
            [
                'upload' => [
                    'name' => '_{}^..image.png',
                    'tmp_name' => $filename = $this->generateFileName(self::BACK),
                    'size' => filesize($filename),
                    'type' => 'image/png',
                    'error' => 0,
                ],
            ]
        );
        $I->seeResponseContainsJson(['error' => 'Invalid image name.']);
        $I->dontSeeInDatabase('CardImage', ['AccountID' => $accountId]);
    }

    public function uploadedCardImageShouldBeDownloaded(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $key = "v1_{$this->userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'AccountID' => $accountId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);

        $I->sendGET($this->getCardImageUrl($cardImageId));
        $I->seeHttpHeader('Content-Length', $size);

        assertTrue($uploadedContent === $I->grabResponse(), 'downloaded image <> uploaded image');
    }

    public function unauthorizedUserShouldNotSeeUploadedImage(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser(
            'carimage' . $I->grabRandomString(5),
            Aw::DEFAULT_PASSWORD
        );
        $key = "v1_{$userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'UserID' => $userId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);
        $I->sendGET($this->getCardImageUrl($cardImageId));
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['error' => 'Not Found']);
    }

    public function unauthorizedUserShouldNotDeleteCardImage(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser(
            'carimage' . $I->grabRandomString(5),
            Aw::DEFAULT_PASSWORD
        );
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $key = "v1_{$userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $I->haveS3Object(self::BUCKET, $key, $uploadedContent);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'AccountID' => $accountId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);

        $I->sendDELETE($this->getAccountUrl($accountId), ['kind' => 'Back']);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['error' => 'Not Found']);
        $I->seeS3Object(self::BUCKET, $key, $uploadedContent);
    }

    public function userShouldSeeCardImageLinkedToSharedAccount(\TestSymfonyGuy $I)
    {
        $userId = $this->userSteps->createAwUser(
            'carimage' . $I->grabRandomString(5),
            Aw::DEFAULT_PASSWORD,
            [],
            true
        );
        $I->createAwProvider($providerCode = 'ci' . $I->grabRandomString(5), $providerCode);
        $sharedAccountId = $this->accountSteps->createAwAccount($userId, $providerCode, 'test', 'test');
        $storageKey = "v1_{$this->userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $I->haveS3Object(self::BUCKET, $storageKey, $uploadedContent, true);
        $cardImageId = $I->haveInDatabase('CardImage', [
            'StorageKey' => $storageKey,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'AccountID' => $sharedAccountId,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);
        $this->userSteps->createConnection($this->userId, $userId);
        $this->accountSteps->shareAwAccountByConnection(
            $sharedAccountId,
            $this->userSteps->createConnection($userId, $this->userId)
        );
        $I->sendGET($this->getCardImageUrl($cardImageId));
        $I->seeResponseCodeIs(200);

        assertTrue($uploadedContent === $I->grabResponse(), 'downloaded image <> uploaded image');
    }

    public function cardImagesShouldBeSavedOnAccountForm(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.22.0+100500');
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'login', 'password');
        $key = "v1_{$this->userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $attachedCardImageId = $I->haveInDatabase('CardImage', [
            'AccountID' => $accountId,
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'Kind' => 1,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);

        $key = "v2_{$this->userId}_" . hash('sha256', $uploadedContent = file_get_contents($this->generateFileName(self::BACK)));
        $newCardImageId = $I->haveInDatabase('CardImage', [
            'StorageKey' => $key,
            'FileSize' => $size = strlen($uploadedContent),
            'FileName' => 'image.png',
            'Height' => 1,
            'Width' => 1,
            'UserID' => $this->userId,
            'UUID' => Uuid::uuid4(),
            'Format' => 'png',
        ]);

        $formData = $this->accountSteps->loadAccountForm($accountEditUrl = AccountSteps::getUrl('edit', $accountId));
        assertEquals($attachedCardImageId, $formData['cardImages']['Front']['CardImageId']);
        $formData['cardImages']['Back']['CardImageId'] = $newCardImageId;
        $I->sendPUT($accountEditUrl, $formData);
        $I->seeResponseContainsJson(['account' => ['ID' => $accountId]]);
        $I->seeInDatabase('CardImage', ['CardImageID' => $newCardImageId, 'UserID' => null, 'AccountID' => $accountId, 'Kind' => 2]);
    }

    protected function validCardImageShouldBeUploadedAndSaved(\TestSymfonyGuy $I, $uploadUrl, $kind, array $containerCriteria)
    {
        $expectedContent = file_get_contents($filename = $this->generateFileName($kind));
        $fileParts = explode('/', $kind);
        $I->sendPOST(
            $uploadUrl,
            ['kind' => (self::FRONT === $kind) ? 'Front' : 'Back'],
            [
                'upload' => [
                    'name' => $clientName = end($fileParts),
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

    protected function getAccountUrl($accountId)
    {
        return "/m/api/cardImage/account/{$accountId}";
    }

    protected function getCouponUrl($couponId)
    {
        return "/m/api/cardImage/coupon/{$couponId}";
    }

    protected function getSubaccountUrl($accountId, $subaccountId)
    {
        return "/m/api/cardImage/account/{$accountId}/{$subaccountId}";
    }

    protected function getCardImageUrl($id)
    {
        return "/m/api/cardImage/{$id}";
    }

    protected function generateFileName($file)
    {
        return codecept_data_dir($file);
    }
}
