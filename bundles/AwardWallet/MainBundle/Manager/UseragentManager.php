<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\Image\Image;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UseragentManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UseragentManager constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return bool|void|null
     * @throws \Doctrine\ORM\OptimisticLockException|\RuntimeException|\InvalidArgumentException|\Exception
     */
    public function saveUploadedAvatarFile(Useragent $agent, UploadedFile $uploadedFile)
    {
        $data = file_get_contents($uploadedFile->getPathname());

        if (empty($data)) {
            return null;
        }

        try {
            $avatarImage = new Image($data, 'userAgent');
        } catch (\Exception $e) {
            if (!$e instanceof \RuntimeException && !$e instanceof \InvalidArgumentException) {
                $this->logger->warning('Unexpected exception "' . $e->getMessage() . '"');
            }

            return;
        }

        $avatarImage->createImage($agent->getUseragentid())->destroyImage();
        $agent->setPictureext($avatarImage->getExtension());
        $agent->setPicturever($avatarImage->getVersion());

        $this->em->flush($agent);
    }
}
