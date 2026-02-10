<?php

namespace Codeception\Module;

use Codeception\TestCase;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\Mail\Protocol\Imap;
use Zend\Mail\Storage\Exception\InvalidArgumentException;
use Zend\Mail\Storage\Message;

use function PHPUnit\Framework\assertNotEmpty;

class Mail extends \Codeception\Module
{
    public const SOURCE_IMAP = 'imap';
    public const SOURCE_SWIFT = 'swift';

    protected $requiredFields = ['server', 'user', 'password', 'ssl', 'port', 'source'];

    /**
     * @var \Zend\Mail\Protocol\Imap
     */
    protected $mailProto;

    /**
     * @var \Zend\Mail\Storage\Imap
     */
    protected $mailStorage;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var \Swift_Plugins_MessageLogger
     */
    private $messageLogger;

    /**
     * @var array
     */
    private $rawEmails = [];

    public function _before(TestCase $test)
    {
        parent::_before($test);

        $this->source = $this->config['source'];
        $this->clearMessages();

        global $Config;
        $Config[CONFIG_EMAIL_HANDLER] = function ($to, $subject, $body, $headers, $params) {
            $this->rawEmails[] = ["to" => $to, "subject" => $subject, "body" => $body, "headers" => $headers, "params" => $params];

            return mail($to, $subject, $body, $headers, $params);
        };
    }

    public function _after(TestCase $test)
    {
        if (!empty($this->messageLogger)) {
            $this->messageLogger->clear();
        }
        $this->messageLogger = null;
        $this->rawEmails = [];

        parent::_after($test);
    }

    public function grabMailMessagesCount()
    {
        if ($this->source == self::SOURCE_SWIFT) {
            return $this->messageLogger->countMessages();
        } else {
            $this->_connect();

            return (int) $this->mailStorage->countMessages();
        }
    }

    public function grabMailMessageHeaderByNumber($number)
    {
        $this->_connect();

        return $this->grabMailMessageByNumber($number)->getHeaders();
    }

    public function grabMailMessageByNumber($number)
    {
        $this->_connect();

        return $this->mailStorage->getMessage($number);
    }

    public function grabLastMailMessageHeader()
    {
        return $this->grabMailMessageHeaderByNumber($this->grabMailMessagesCount());
    }

    /**
     * @return \Swift_Message
     */
    public function grabLastMail()
    {
        $messages = $this->messageLogger->getMessages();

        if (empty($messages)) {
            return new \Swift_Message();
        } else {
            return $messages[count($messages) - 1];
        }
    }

    /**
     * @return \Swift_Message[]
     */
    public function grabLastMails(): array
    {
        return $this->messageLogger->getMessages();
    }

    public function grabMailMessageBodyByNumber($number)
    {
        if ($this->source == self::SOURCE_SWIFT) {
            $messages = $this->messageLogger->getMessages();

            if (!isset($messages[$number - 1])) {
                return "";
            }
            /** @var \Swift_Mime_SimpleMessage $message */
            $message = $messages[$number - 1];

            return quoted_printable_decode($message->toString());
        } else {
            $this->_connect();

            return quoted_printable_decode($this->mailStorage->getRawContent($number));
        }
    }

    public function grabLastMailMessageBody()
    {
        return $this->grabMailMessageBodyByNumber($this->grabMailMessagesCount());
    }

    /**
     * @param $subject - text
     * @param int $timeout
     * @deprecated use seeEmailTo()
     */
    public function waitForSubject($subject, $timeout = 5, $udate = 'now')
    {
        $timeout = intval($timeout);
        $time = strtotime($udate);
        $exist = false;

        for ($i = 0; $i < $timeout; $i++) {
            $last = $this->grabLastMailMessageHeader();

            if ($last->has('Subject') && $last->has('Date') && preg_match('/' . preg_quote($subject, '#') . '/i', $last->get('Subject')->getFieldValue())) {
                $dateDiff = strtotime($last->get('Date')->getFieldValue()) - $time;

                if ($dateDiff >= 0) {
                    $exist = true;

                    break;
                } else {
                    $this->debug("message exists, but too old, diff: $dateDiff, message date: " . $last->get('Date')->getFieldValue() . ", expected: " . date("Y-m-d H:i:s", $time));
                }
            }
            sleep(1);
        }
        \PHPUnit_Framework_Assert::assertTrue($exist);
    }

    public function seeEmailTo($to, $subject = null, $content = null, $timeout = 5, $update = 'now')
    {
        $this->assertTrue(call_user_func_array([$this, $this->source . 'emailExists'], [$to, $subject, $content, $timeout, $update]), sprintf('Email message not found to "%s", subject "%s"', $to, $subject));
    }

    public function dontSeeEmailTo($to, $subject = null, $content = null, $timeout = 5, $update = 'now')
    {
        $this->assertFalse(call_user_func_array([$this, $this->source . 'emailExists'], [$to, $subject, $content, $timeout, $update]), sprintf('Email message found to "%s", subject "%s"', $to, $subject));
    }

    /**
     * @param null $subject
     * @param null $content
     * @param int $timeout
     * @param string $update
     * @return \Zend\Mail\Storage\Message[]|\Swift_Message[]
     */
    public function grabEmailsTo($to, $subject = null, $content = null, $timeout = 5, $update = 'now')
    {
        $emails = call_user_func_array([$this, 'get' . ucfirst($this->source) . 'Emails'], [$to, $subject, $content, $timeout, $update]);
        assertNotEmpty($emails, sprintf('Email message not found to "%s", subject "%s"', $to, $subject));

        return $emails;
    }

    public function _cleanup()
    {
        if ($this->mailStorage) {
            $this->_close();
        }
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * returns emails sent by mailTo function.
     *
     * @return array - [['to' => 'some@address.com', 'subject' => 'blah...', 'body' => 'hello', 'headers' => [..], 'params' => [...]], [...;
     */
    public function grabRawMails(): array
    {
        return $this->rawEmails;
    }

    /**
     * @return \Swift_Message[]
     */
    protected function collectSwiftEmails()
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        $collector = new MessageDataCollector($symfony2->_getContainer());
        $collector->collect(new Request(), new Response());

        if (!$collector->getMailers()) {
            return [];
        }

        return $collector->getMessages();
    }

    protected function wrapArray($var)
    {
        return null === $var ? [] : [$var];
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $content
     * @return bool
     */
    protected function swiftEmailExists($to, $subject, $content)
    {
        return (bool) $this->getSwiftEmail($to, $subject, $content);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $content
     * @return \Swift_Message[]
     */
    protected function getSwiftEmail($to, $subject, $content)
    {
        foreach ($this->collectSwiftEmails() as $message) {
            $headers = $message->getHeaders();

            $addresses = [];

            foreach (array_merge(
                $this->wrapArray($headers->get('To')),
                $this->wrapArray($headers->get('X-Swift-Aw-To')),
                $this->wrapArray($headers->get('X-Original-To')),
                $this->wrapArray($headers->get('X-Swift-To'))
            ) as $header) {
                /** @var \Swift_Mime_Header $header */
                $header = $header->getFieldBody();

                if (preg_match_all('/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i', $header, $matches, PREG_SET_ORDER)) {
                    $addresses = array_merge($addresses, array_map(function (array $set) { return $set[0]; }, $matches));
                }
            }
            $isHeadersMatch = in_array($to, $addresses, true);

            if (
                $isHeadersMatch
                && (!isset($subject) || preg_match('/' . preg_quote($subject, '#') . '/i', $message->getSubject()))
                && (!isset($content) || preg_match('/' . preg_quote($content, '#') . '/i', $message->getBody()))
            ) {
                return [$message];
            }
        }

        return [];
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param int $timeout
     * @param string $update
     * @return bool
     */
    protected function imapEmailExists($to, $subject, $content, $timeout, $update)
    {
        return (bool) $this->getImapEmails($to, $subject, $content, $timeout, $update);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param int $timeout
     * @param string $update
     * @return \Zend\Mail\Storage\Message[]
     */
    protected function getImapEmails($to, $subject, $content, $timeout, $update)
    {
        $timeout = intval($timeout);
        $time = strtotime($update);

        $endTime = time() + $timeout;
        $_tN = 0;

        while (time() < $endTime) {
            $this->_connect();
            $_tN++;
            $messages = $this->findByEmail($to, $time);

            $this->debug(sprintf("Try %d, %d message(s) found", $_tN, count($messages)));

            if ($messages && null === $subject && null === $content) {
                $this->debug('  No criteria specified, abstaining from matching');

                /** @var \Zend\Mail\Storage\Message[] $messagesResult */
                $messagesResult = [];

                foreach ($messages as $messageId) {
                    try {
                        $messagesResult[] = $this->mailStorage->getMessage((int) $messageId);
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                return $messagesResult;
            }

            foreach ($messages as $_mN => $messageId) {
                $message = $this->mailStorage->getMessage((int) $messageId);
                $headers = $message->getHeaders();
                $dateHeader = $headers->get('Date');

                if (!empty($dateHeader)) {
                    $dateHeader = $dateHeader->getFieldValue();
                }
                $this->debug(sprintf("\tmessage %d of %d: date: {$dateHeader}", $_mN + 1, count($messages)));

                $result = false;

                if (null !== $subject) {
                    $messageSubject = $headers->get('Subject');

                    if (null !== $messageSubject) {
                        $match = preg_match('/' . preg_quote($subject, '#') . '/i', $messageSubject->getFieldValue());
                        $result = $result || $match;
                        $this->debug(sprintf("\t\tsubject \"%s\" %s", $messageSubject->getFieldValue(), $match ? 'matches' : 'mismatches'));
                    } else {
                        $this->debug("\t\tsubject not found in headers");
                    }
                }

                if ($result && null !== $content) {
                    $rawContent = $this->getContentDecoded($message);
                    $match = preg_match('/' . preg_quote($content, '#') . '/i', $rawContent);
                    $result = $result && $match;
                    $this->debug("\t\tcontent " . ($match ? 'matches' : 'mismatches'));
                }

                if ($result) {
                    if (!$dateHeader) {
                        continue;
                    }

                    $messageDate = strtotime($dateHeader);

                    if (false !== $messageDate && abs($messageDate - $time) <= $timeout * 10) {
                        return [$message];
                    }

                    $this->debug(sprintf("\t\tFailed date check: abs(%d - %d) <= %d", $messageDate, $time, $timeout * 10));
                }
            }
            usleep(500000);
        }

        return [];
    }

    protected function findByEmail($email, $since)
    {
        $searchPatterns = [
            'SINCE "%1$s" HEADER X-Swift-Aw-To "%2$s"',
            'SINCE "%1$s" HEADER X-Original-To "%2$s"',
            'SINCE "%1$s" HEADER X-Swift-To "%2$s"',
            'SINCE "%1$s" TO "%2$s"',
        ];

        $formattedDate = date('j-M-Y', $since - SECONDS_PER_DAY * 2);
        $lowerEmail = strtolower($email);
        $searchQueries = [];

        foreach ($searchPatterns as $searchPattern) {
            $searchQueries[] = sprintf($searchPattern, $formattedDate, $email);

            if ($email !== $lowerEmail) {
                $searchQueries[] = sprintf($searchPattern, $formattedDate, $lowerEmail);
            }
        }

        $this->debug('IMAP search queries: ' . json_encode($searchQueries));
        $startTimer = (int) (microtime(true) * 1000);
        $result = [];

        foreach ($searchQueries as $searchQuery) {
            $result = array_merge($result, (array) $this->mailProto->search([$searchQuery]));
        }

        $this->debug('IMAP search result: ' . json_encode($result));
        $this->debug('IMAP search took: ' . ((int) (microtime(true) * 1000) - $startTimer) . ' ms');

        return $result;
    }

    protected function _connect()
    {
        $this->_cleanup();

        $protocol = new Imap($this->config['server'], $this->config['port'], $this->config['ssl']);
        $this->assertFalse(false === $protocol->login($this->config['user'], $this->config['password']), 'unable to connect to IMAP');
        $protocol->select('INBOX');
        $this->mailStorage = new \Zend\Mail\Storage\Imap($protocol);
        $this->mailProto = $protocol;
    }

    protected function _close()
    {
        if ($this->mailStorage) {
            $this->mailStorage->close();
            $this->mailStorage = null;
            $this->mailProto = null;
        }
    }

    private function getContentDecoded(Message $message)
    {
        $content = $message->getContent();

        try {
            $contentTransferEncoding = $message->contentTransferEncoding;
        } catch (InvalidArgumentException $e) {
            return $content;
        }

        switch ($contentTransferEncoding) {
            case 'base64':
                $content = base64_decode($content);

                break;

            case 'quoted-printable':
                $content = quoted_printable_decode($content);

                break;
        }

        return $content;
    }

    private function clearMessages()
    {
        if ($this->hasModule('Symfony')) {
            /** @var Symfony $symfony2 */
            $symfony2 = $this->getModule('Symfony');
            $container = $symfony2->_getContainer();
            $mailers = $container->getParameter('swiftmailer.mailers');

            foreach ($mailers as $name => $mailer) {
                if ($container->has($loggerName = sprintf('swiftmailer.mailer.%s.plugin.messagelogger', $name))) {
                    /** @var \Swift_Plugins_MessageLogger $service */
                    $this->messageLogger = $container->get($loggerName);
                    $this->messageLogger->clear();

                    $symfony2->persistService($loggerName);
                }
            }
        }
    }
}
