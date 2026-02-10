<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

use AwardWallet\Strings\Strings;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class MailerHandler extends AbstractProcessingHandler
{
    private Mailer $mailer;
    private string $appTitle = 'frontend';

    public function __construct(Mailer $mailer, FormatterInterface $formatter)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->formatter = $formatter;
    }

    public function handleBatch(array $records)
    {
        $messages = [];

        foreach ($records as $record) {
            if ($record['level'] < $this->level) {
                continue;
            }
            $messages[] = $this->processRecord($record);
        }

        if (!empty($messages)) {
            $this->send((string) $this->getFormatter()->formatBatch($messages), $messages);
        }
    }

    protected function write(array $record)
    {
        $this->send((string) $record['formatted'], [$record]);
    }

    protected function send($content, array $records)
    {
        // prevent 552 5.3.4 Error: message file too big
        $content = Strings::cutInMiddle($content, 500000);
        $this->mailer->send([$this->buildMessage($content, $records)], [Mailer::OPTION_SKIP_STAT => true, Mailer::OPTION_FIX_BODY => false]);
    }

    protected function getHighestRecord(array $records)
    {
        $highestRecord = null;

        foreach ($records as $record) {
            if ($highestRecord === null || $highestRecord['level'] < $record['level']) {
                $highestRecord = $record;
            }
        }

        return $highestRecord;
    }

    /**
     * Gets the formatter for the Swift_Message subject.
     *
     * @param  string             $format The format of the subject
     * @return FormatterInterface
     */
    protected function getSubjectFormatter($format)
    {
        return new LineFormatter($format);
    }

    /**
     * Creates instance of Swift_Message to be sent.
     *
     * @param  string         $content formatted email body to be sent
     * @param  array          $records Log records that formed the content
     */
    private function buildMessage($content, array $records): Message
    {
        $message = $this->createMessage($content, $records);

        if ($records) {
            $subjectFormatter = $this->getSubjectFormatter($message->getSubject());
            $message->setSubject($subjectFormatter->format($this->getHighestRecord($records)));
        }

        $message->setBody($content);

        if (version_compare(\Swift::VERSION, '6.0.0', '>=')) {
            $message->setDate(new \DateTimeImmutable());
        } else {
            $message->setDate(time());
        }

        return $message;
    }

    private function createMessage(string $content, array $records): Message
    {
        $result = $this->mailer->getMessage();
        $result
            ->setFrom('error@awardwallet.com')
            ->setTo('error@awardwallet.com')
            ->setSubject($this->getSubject($records))
            ->setContentType('text/html')
        ;

        //        // при миграции на symfony4 у метода Swift_Message::setDate() изменен интерфейс
        //        if (interface_exists(\Swift_Mime_Message::class) && $result instanceof \Swift_Mime_Message) {
        //            $result->setDate(time());
        //        } else {
        //            $result->setDate(new \DateTime());
        //        }

        return $result;
    }

    private function getSubject(array $records)
    {
        $maxLevel = 0;
        $defaultTitle = $this->appTitle . ": An Error Occurred!";
        $result = $defaultTitle;

        foreach ($records as $record) {
            // Sending Dev Notifications
            if (isset($record['context']['DevNotification']) && $maxLevel <= Logger::WARNING && $record['level'] > $maxLevel) {
                $title = substr($record['context']['Title'] ?? '', 0, 250);
                $result = sprintf($this->appTitle . ' [Dev Notification]: %s', $title);
                $maxLevel = Logger::WARNING;

                continue;
            }

            // replace subject with critical error for better email grouping
            if (isset($record['level'], $record['message']) && $record['level'] >= Logger::WARNING && $record['level'] > $maxLevel) {
                $result = $defaultTitle . ': ' . substr($record['message'], 0, 250);
                $maxLevel = $record['level'];
            }
        }

        return $result;
    }
}
