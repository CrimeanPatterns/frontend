<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Command\ClearUnlinkedCardImagesCommand;
use AwardWallet\MainBundle\Globals\StringUtils;
use Codeception\Module\AwsS3;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 */
class ClearUnlinkedCardImagesCommandTest extends BaseContainerTest
{
    public const BUCKET = 'dev-cardimagebucket';
    /**
     * @var AwsS3
     */
    protected $s3;
    /**
     * @var CommandTester
     */
    protected $commandTester;

    public function _before()
    {
        parent::_before();

        $app = new Application($this->container->get('kernel'));
        $app->add($this->container->get(ClearUnlinkedCardImagesCommand::class));

        /** @var ClearUnlinkedCardImagesCommand $command */
        $command = $app->find('aw:clear-unlinked-card-images');
        $this->commandTester = new CommandTester($command);
        $this->s3 = $this->getModule('AwsS3');
    }

    /**
     * TODO Если в таблице есть запись, указывающая на несуществующий файл, то команда попытается его удалить и упадет
     * fix: truncate CardImage.
     */
    public function testClearUnlinkedCardImages()
    {
        $userId = $this->aw->createAwUser('clrcicmnd' . StringUtils::getRandomCode(10));
        $accountId = $this->aw->createAwAccount($userId, null, 'login', 'password');
        $filesToRemove = [];

        foreach (range(1, 3) as $i) {
            $this->s3->haveS3Object(
                self::BUCKET,
                $key = "v1_{$userId}_" . hash('sha256', StringUtils::getRandomCode(10) . '_' . $i),
                file_get_contents(codecept_data_dir('cardImages/front.png'))
            );

            if (3 === $i) {
                break;
            }

            $filesToRemove[$this->createCardImageInDatabase(['StorageKey' => $key])] = $key;
        }

        $validCardImageId = $this->createCardImageInDatabase([
            'StorageKey' => $validStorageKey = $key,
            'AccountID' => $accountId,
        ]);
        $this->execute();

        foreach ($filesToRemove as $rowId => $storageKey) {
            $this->db->dontSeeInDatabase('CardImage', ['CardImageID' => $rowId]);
            $this->s3->dontSeeS3Object(self::BUCKET, $storageKey);
        }

        $this->db->seeInDatabase('CardImage', ['CardImageID' => $validCardImageId]);
        $this->s3->seeS3Object(self::BUCKET, $validStorageKey);
    }

    protected function createCardImageInDatabase(array $mixin = [])
    {
        return $this->db->haveInDatabase('CardImage', array_merge(
            [
                'FileSize' => 1,
                'Width' => 1,
                'Height' => 1,
                'Kind' => 1,
                'UUID' => $this->aw->grabRandomString(18),
                'FileName' => 'image.png',
                'Format' => 'image/png',
                'UploadDate' => (new \DateTime('-4 hours'))->format('Y-m-d H:i:s'),
            ],
            $mixin
        ));
    }

    protected function execute()
    {
        $this->commandTester->execute([]);
    }
}
