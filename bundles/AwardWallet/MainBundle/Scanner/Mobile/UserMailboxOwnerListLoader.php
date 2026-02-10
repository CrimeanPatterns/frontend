<?php

namespace AwardWallet\MainBundle\Scanner\Mobile;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Scanner\MailboxOwnerHelper;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UserMailboxOwnerListLoader
{
    private CacheManager $cacheManager;
    private MailboxOwnerHelper $mailboxOwnerHelper;
    private EmailScannerApi $emailScannerApi;
    private UsrRepository $usrRepository;
    private UseragentRepository $useragentRepository;
    private OwnerRepository $ownerRepository;

    public function __construct(
        CacheManager $cacheManager,
        MailboxOwnerHelper $mailboxOwnerHelper,
        EmailScannerApi $emailScannerApi,
        UsrRepository $usrRepository,
        UseragentRepository $useragentRepository
    ) {
        $this->cacheManager = $cacheManager;
        $this->mailboxOwnerHelper = $mailboxOwnerHelper;
        $this->emailScannerApi = $emailScannerApi;
        $this->usrRepository = $usrRepository;
        $this->useragentRepository = $useragentRepository;
    }

    /**
     * @return array<Owner>
     */
    public function load(Usr $user): array
    {
        return
            it($this->doLoadFromEmailAPICached($user))
            ->flatMap(function (array $ownerData) {
                [$userId, $userAgentId] = $ownerData;
                $user = $this->usrRepository->find($userId);

                if (!$user) {
                    return;
                }

                yield OwnerRepository::getOwner(
                    $user,
                    isset($userAgentId) ? $this->useragentRepository->find($userAgentId) : null
                );
            })
            ->toArray();
    }

    /**
     * @return array<array{int, ?int}>
     */
    private function doLoadFromEmailAPICached(Usr $user): array
    {
        $userId = $user->getId();

        return $this->cacheManager->load(new CacheItemReference(
            Tags::getUserMailboxesOwnersKey($userId),
            Tags::addTagPrefix(Tags::getUserMailboxesTags($userId)),
            fn () => $this->doloadFromEmailAPI($user)
        ));
    }

    /**
     * @return array<array{int, ?int}>
     */
    private function doloadFromEmailAPI(Usr $user): array
    {
        return
            it($this->emailScannerApi->listMailboxes(["user_" . $user->getId()]))
            ->map(function (Mailbox $mailbox) {
                $ownerData = $this->mailboxOwnerHelper->getOwnerIdsFromUserData($mailbox->getUserData());
                [$userId, $userAgentId] = $ownerData;

                return [
                    /* for uniqueness */ $userId . '_' . $userAgentId,
                    $ownerData,
                ];
            })
            ->fromPairs()
            /* for uniqueness */
            ->collectWithKeys()
            /* get rid of unique key */
            ->values()
            ->toArray();
    }
}
