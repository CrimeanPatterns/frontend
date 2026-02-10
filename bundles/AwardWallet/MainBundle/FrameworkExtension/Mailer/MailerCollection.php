<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

/**
 * @deprecated use Mailer
 */
class MailerCollection
{
    protected $totalSends = 0;

    protected $limit;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    public function setLimit($limit)
    {
        $this->limit = (int) $limit;

        return $this;
    }

    public function getMailer()
    {
        return $this->mailer;
    }

    public function getTotalSends()
    {
        return $this->totalSends;
    }

    public function send(bool $dryRunSend = false, bool $dryRunPrePost = false)
    {
        $this->totalSends = 0;

        while ($this->dataProvider->next()) {
            if (!$this->dataProvider->canSendEmail()) {
                continue;
            }

            try {
                $message = $this->dataProvider->getMessage($this->mailer);
            } catch (SkipException $exception) {
                continue;
            }

            $options = $this->dataProvider->getOptions();
            $this->dataProvider->preSend($this->mailer, $message, $options, $dryRunPrePost);
            $result = $dryRunSend ? true : $this->mailer->send($message, $options);
            $this->dataProvider->postSend($this->mailer, $message, $options, $result, $dryRunPrePost);
            $this->totalSends++;

            if (isset($this->limit) && ($this->totalSends >= $this->limit)) {
                break;
            }
        }
    }
}
