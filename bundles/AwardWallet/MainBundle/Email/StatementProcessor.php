<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class StatementProcessor
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManager */
    private $em;
    /** @var ProviderRepository */
    private $pr;
    /** @var AccountRepository */
    private $ac;
    /** @var StatementMatcher */
    private $matcher;
    /** @var StatementSaver */
    private $saver;

    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        ProviderRepository $pr,
        AccountRepository $ac,
        StatementMatcher $matcher,
        StatementSaver $saver)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->pr = $pr;
        $this->ac = $ac;
        $this->matcher = $matcher;
        $this->saver = $saver;
    }

    public function process(ParseEmailResponse $data, Owner $owner, EmailOptions $options)
    {
        /** @var Provider $provider */
        $provider = $this->pr->findOneBy(['code' => $data->providerCode]);

        if ($options->source->getSource() != ParsedEmailSource::SOURCE_SCANNER) {
            return CallbackProcessor::SAVE_MESSAGE_FAIL;
        }

        if ('aa' === $data->providerCode) {
            $aaRegEx = '/@aa[.]com$/';
            $this->logger->info('updating with aa statement');

            if (!empty($owner->getEmail()) && preg_match($aaRegEx, $owner->getEmail())) {
                $this->logger->info('update rejected by owner address');

                return CallbackProcessor::SAVE_MESSAGE_MISSED;
            }

            foreach ($data->metadata->to as $address) {
                if (!empty($address->email) && preg_match($aaRegEx, $address->email) > 0) {
                    $this->logger->info('update rejected by recipient address');

                    return CallbackProcessor::SAVE_MESSAGE_MISSED;
                }
            }

            if ($owner->getUser()->hasRole('ROLE_DO_NOT_COMMUNICATE')) {
                $this->logger->info('update rejected by DNC role');

                return CallbackProcessor::SAVE_MESSAGE_MISSED;
            }
            $report = $this->matcher->matchCustomAa($owner, $data->loyaltyAccount);

            if (null === $report->acc) {
                return CallbackProcessor::SAVE_MESSAGE_MISSED;
            }
        } else {
            $report = $this->matcher->match($owner, $provider, $data->loyaltyAccount);
        }
        $account = $report->acc;
        $discovered = false;

        if (null === $report->acc && (0 === $report->cnt || $this->allowDuplicateDiscovered($owner->getUser()))) {
            $account = $this->saver->createDiscoveredAccount($owner, $provider, $data->loyaltyAccount, $options->source->getUserEmail());
            $discovered = true;
        }

        if ($account) {
            if (!$discovered && !$this->matcher->checkEmailDates($data->loyaltyAccount, $data->metadata->receivedDateTime, $account)) {
                $this->logger->info('skipping due to invalid date');

                return CallbackProcessor::SAVE_MESSAGE_MISSED;
            }
            $account->setEmailparsedate(new \DateTime());
            $this->em->flush();
            $emailDate = ($date = $data->metadata->receivedDateTime) ? new \DateTime($date) : null;
            $success = $this->saver->save($account, $data->loyaltyAccount, $emailDate);

            if (!$success && !$discovered) {
                $this->saver->saveEmailExclusive($account, $data->loyaltyAccount);
            }

            return CallbackProcessor::SAVE_MESSAGE_SUCCESS;
        } else {
            $this->logger->info('skipping statement');

            return CallbackProcessor::SAVE_MESSAGE_MISSED;
        }
    }

    private function allowDuplicateDiscovered(Usr $user): bool
    {
        return in_array($user->getLogin(), [
            'ksolovyeva',
        ]);
    }
}
