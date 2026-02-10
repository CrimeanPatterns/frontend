<?php

require dirname(__FILE__) . '/../../web/kernel/public.php';

class GMailParser
{
    /**
     * @var \Zend\Mail\Storage\Imap
     */
    public $storage;
    /**
     * @var \Zend\Mail\Storage\Part
     */
    public $current;
    public $count;
    public $messageId;

    public $notNDR = false;

    private $data = [
        'message' => null,
    ];

    public function connect($email, $password)
    {
        $this->writeLog(
            "Start NDR Finder\n" .
            "============================================="
        );
        $this->storage = new \Zend\Mail\Storage\Imap(
            [
                'host' => 'imap.gmail.com',
                'user' => $email,
                'password' => $password,
                'port' => '993',
                'ssl' => 'SSL',
            ]
        );
        $this->writeLog("connected");
    }

    public function writeLog($str)
    {
        $str = date("Y-m-d H:i:s ") . $str . "\n";
        echo $str;
    }

    public function isNDRSubject(Zend\Mail\Storage\Part $message)
    {
        $subject = $this->getHeaderField($message, 'Subject');

        if (!empty($subject)) {
            $subjectArr = [
                'Undelivered Mail Returned to Sender',
                'Returned mail: see transcript for details',
                'Delivery Status Notification (Failure)',
                'Mail System Error - Returned Mail',
                'Message Undeliverable',
                'deliver',
                'fail',
            ];

            foreach ($subjectArr as $subj) {
                if (stristr($subject, $subj)) {
                    $this->writeLog("NDR subject detected");

                    return true;
                }
            }
        }

        return false;
    }

    public function countMessages()
    {
        $this->writeLog("Get message count...");
        $this->count = $this->storage->countMessages();
        $this->writeLog("Message count: {$this->count}");
    }

    public function getMessage($index = null)
    {
        $this->current = $this->storage->getMessage(!isset($index) ? 1 : $index);
        $this->getMessageId($index);
    }

    public function save()
    {
        if (!empty($this->data['notifications'])) {
            $this->writeLog('This is notifications@ email, move to notifications');
            $this->storage->moveMessage(1, 'notifications');

            return;
        }

        if ($this->data['message'] == 'notNDR') {
            $this->writeLog('This is not NDR email, REMOVE (move to trashNDR)');
            $this->storage->moveMessage(1, 'trashNDR');

            return;
        }

        if (empty($this->data['email'])) {
            $this->writeLog("email address not found in email");
            $file = sys_get_temp_dir() . "/ndrEmailNotFound.eml";
            $this->writeLog("emailNotFound message saved to $file");
            file_put_contents($file, $this->storage->getRawHeader(1) . $this->storage->getRawContent(1));
            $this->storage->moveMessage(1, 'emailNotFound');

            return;
        }

        if (!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL) || stripos($this->data['email'], '@awardwallet.com') !== false) {
            $this->writeLog("invalid email: {$this->data['email']}");
            $this->storage->moveMessage(1, 'emailNotFound');

            return;
        }

        $this->recordNdr();

        if ($this->data['message'] == 'abuse') {
            $this->storage->moveMessage(1, 'abuse');
        } else {
            $this->storage->moveMessage(1, 'isNDR');
        }
    }

    public function getMessageId($index = null)
    {
        $this->messageId = $this->storage->getUniqueId($index ?? 1);

        return $this->messageId;
    }

    public function getIndexByMessageId($messageId)
    {
        $this->writeLog("Get index by ID: {$messageId}");

        return $this->storage->getNumberByUniqueId($messageId);
    }

    public function scannerGo()
    {
        $this->countMessages();

        if ($this->count > 0) {
            $this->writeLog("Start parse: " . date("d.m.Y H:i:s") . "\n");

            for ($i = 1; $i <= $this->count; $i++) {
                set_time_limit(119);
                $this->data = ['message' => null];

                try {
                    $this->getMessage();
                    $this->writeLog("> " . $this->current->getHeaderField('From') . ": " . $this->getHeaderField($this->current, 'Subject'));
                    $this->parseMessage($this->current);
                } catch (Zend_Exception $e) {
                    $this->writeLog("error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    $file = "/tmp/ndrError{$i}.eml";
                    $this->writeLog("faulty message saved to $file");
                    file_put_contents($file, $this->storage->getRawHeader(1) . $this->storage->getRawContent(1));
                } catch (\Zend\Mail\Exception\InvalidArgumentException $e) {
                    $this->writeLog("error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    $file = "/tmp/ndrError{$i}.eml";
                    $this->writeLog("faulty message saved to $file");
                    file_put_contents($file, $this->storage->getRawHeader(1) . $this->storage->getRawContent(1));
                } catch (\Zend\Mime\Exception\RuntimeException $e) {
                    $this->writeLog("error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    $file = "/tmp/ndrError{$i}.eml";
                    $this->writeLog("faulty message saved to $file");
                    file_put_contents($file, $this->storage->getRawHeader(1) . $this->storage->getRawContent(1));
                }
                $this->save();
            }
            $this->writeLog("End of parsing");
        } else {
            $this->writeLog("Emails not found");
        }
    }

    public function isHeader($header)
    {
        $h = $this->current->getHeader($header, "string");

        return is_string($h);
    }

    public function parseRaw($rawMessage)
    {
        $message = new \Zend\Mail\Storage\Part(['raw' => $rawMessage]);
        $this->writeLog("> " . $message->getHeaderField('From') . ": " . $message->getHeaderField('Subject'));
        $this->parseMessage($message);

        if ($this->data['message'] == 'notNDR') {
            $this->writeLog('This is not NDR email, REMOVE (move to trashNDR)');
        } else {
            $this->recordNdr();
        }
    }

    private function getHeaderField(\Zend\Mail\Storage\Part $message, string $field)
    {
        try {
            return $message->getHeaderField($field);
        } catch (\ErrorException $e) {
            return '';
        }
    }

    private function parseMessage(Zend\Mail\Storage\Part $message)
    {
        $this->parseParts($message);
        $this->writeLog("parsed data: " . var_export($this->data, true));

        if (!$this->isNDRSubject($message) && empty($this->data['message'])) {
            $this->data['message'] = 'notNDR';
            $this->writeLog("not NDR - subject not match");
        }
    }

    private function parseParts(Zend\Mail\Storage\Part $message, $parseMainBody = true)
    {
        try {
            $partsCount = $message->countParts();
        } catch (\Zend\Mime\Exception\RuntimeException $e) {
            $this->writeLog("runtime exception while countPart: " . $e->getMessage());

            return;
        } catch (\Zend\Mail\Exception\InvalidArgumentException $e) {
            $this->writeLog("runtime exception while countPart: " . $e->getMessage());

            return;
        }
        $this->writeLog("parts count: " . $partsCount);

        for ($n = 1; $n <= $partsCount; $n++) {
            $this->parsePart($message->getPart($n));
        }

        if ($partsCount == 0 && $parseMainBody) {
            $this->parsePart($message);
        }
    }

    private function parsePart(Zend\Mail\Storage\Part $part)
    {
        try {
            $contentType = $part->getHeaderField('Content-Type');
        } catch (\Zend\Mime\Exception\RuntimeException $e) {
            $contentType = null;
        } catch (\Zend\Mail\Storage\Exception\RuntimeException $e) {
            $contentType = null;
        } catch (\Zend\Mail\Exception\InvalidArgumentException $e) {
            $contentType = null;
        }

        $this->writeLog("part: " . $contentType);

        if ($contentType == 'message/rfc822') {
            try {
                $subMessage = new \Zend\Mail\Storage\Part(['raw' => trim($part->getContent())]);
            } catch (\Zend\Mail\Exception\InvalidArgumentException $e) {
                $this->writeLog("exception while parsing part: " . $e->getMessage());

                return;
            } catch (\Zend\Mime\Exception\RuntimeException $e) {
                $this->writeLog("exception while parsing part: " . $e->getMessage());

                return;
            }
            $this->parseParts($subMessage);

            return;
        }

        if (stripos($contentType, 'multipart/') === 0) {
            $this->parseParts($part, false);

            return;
        }
        $content = $this->decodePart($part);

        if ($contentType == 'message/feedback-report') {
            $report = [];

            foreach (explode("\n", $content) as $line) {
                $pair = explode(":", $line);

                if (count($pair) > 1) {
                    $report[$pair[0]] = trim($pair[1]);
                }
            }

            if (!empty($report['Feedback-Type']) && $report['Feedback-Type'] == 'abuse') {
                $this->data['message'] = 'abuse';
                $this->writeLog("abuse report found");

                if (!empty($this->data['email']) && isset($report['Original-Rcpt-To'])) {
                    $this->writeLog("email extracted from Original-Rcpt-To: " . $report['Original-Rcpt-To']);
                    $this->data['email'] = $report['Original-Rcpt-To'];
                }
            } else {
                $this->writeLog("incomplete abuse: " . $part->getContent());
            }
        }

        if (empty($this->data['email']) && preg_match("#/user/logo\.php\?ID=(\d+)&hash=(\w+)#ims", $content, $matches)) {
            $this->writeLog("searching user by logo: {$matches[1]}, {$matches[2]}");
            /** @var \AwardWallet\MainBundle\Entity\Usr $user */
            $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($matches[1]);

            if ($user && $user->getEmailVerificationHash() == $matches[2]) {
                $this->writeLog("hash matched");
                $q = new TQuery("select Email from Usr where UserID = " . intval($matches[1]));

                if (!$q->EOF) {
                    $this->writeLog("user found: {$q->Fields['Email']}");
                    $this->data['email'] = $q->Fields['Email'];
                }
            } else {
                $this->writeLog("hash not matched");
            }
        }

        if (empty($this->data['email']) && preg_match("#/unsubscribe\?email=([^&]+)#ims", $content, $matches)) {
            $matches[1] = urldecode($matches[1]);
            $this->writeLog("email found in unsubscribe link: {$matches[1]}");
            $this->data['email'] = $matches[1];
        }

        if (stripos($content, 'notifications@awardwallet.com')) {
            $this->data['message'] = 'notNDR';
            $this->data['notifications'] = true;
            $this->writeLog("notifications@awardwallet.com found, ignore");
        }

        if (
            preg_match("/receiving\s+mail\s+at\s+a\s+rate\s+that/ims", $content)
            || preg_match("/delivery\s+temporarily\s+suspended/ims", $content)
            || preg_match("/try\s+again\s+later/ims", $content)
        ) {
            $this->data['message'] = 'notNDR';
        } elseif (
            preg_match("/The mail system\s*<([^>]+)>:(.*)Content-Description: Delivery report/ims", $content, $matches)
            || preg_match("/The delivery status notification errors\s*[\-]+\s*<([^>]+)>:(.*)Content-Description: Delivery report/ims", $content, $matches)
            || preg_match("/The following addresses had permanent fatal errors\s*[\-]+\s*<([^>]+)>(.*)Content\-Type: message\/delivery\-status/ims", $content, $matches)
            || preg_match("/The following addresses had permanent fatal errors\s*[\-]+\s*([^\s]+)(.*)Content\-Type: message\/delivery\-status/ims", $content, $matches)
            || preg_match("/Delivery to the following recipients failed\.\s*([^\s]+)/ims", $content, $matches)
            || preg_match("/The following message to <([^>]+)> was undeliverable\.\s*(.*)content\-type: message\/delivery\-status/ims", $content, $matches)
            || preg_match("/Delivery to the following recipient failed permanently:\s*([^\s]+)\s+(.*)/ims", $content, $matches)
            || preg_match("/cannot be delivered to (.*) because the ([^\.]+)\./ims", $content, $matches)
            || (preg_match("/_is_blocked/ims", $content) && preg_match("/The mail system\s*<([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))>/ims", $content, $matches))
            || preg_match("/The mail system\s*<([^>]+)>:(.*e-mail\sand\sdestroy\sall\scopies\sof\sthe\sdocument)/ims", $content, $matches)
            || preg_match("/no such recipient\s*\-\s*([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))/ims", $content, $matches)
            || preg_match("/<([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))>\s*:\s*Recipient address rejected/ims", $content, $matches)
            || preg_match("/The mail system\s*<([^>]+)>:(.*(Host not found|Host or domain name not found))/ims", $content, $matches)
            || preg_match("/Original\-Recipient:\s+rfc822;\s*([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))/ims", $content, $matches)
            || preg_match("/Final\-Recipient:\s+rfc822;\s*([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))/ims", $content, $matches)
            || preg_match("/generated\s+from\s+([_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+))\)\s*mailbox is full/ims", $content, $matches)
        ) {
            $this->writeLog("email found by regexp: " . $matches[0]);
            $this->data['email'] = $matches[1];
            $this->data['message'] = $matches[0];

            if ($this->data['email'] == 'notifications@awardwallet.com') {
                // $this->writeLog($content);
                if (preg_match("/To:\s(.[^@]+@[^\s]+)\s/ims", $content, $matches)) {
                    $this->data['email'] = $matches[1];
                } else {
                    $this->data['email'] = null;
                }
            }
        } elseif (
            preg_match("/This Message was undeliverable due to the following reason:\s*([^<]+)<([^>]+)>/ims", $content, $matches)
        ) {
            $this->data['email'] = $matches[2];
            $this->data['message'] = $matches[1];
        }
    }

    private function decodePart(Zend\Mail\Storage\Part $part)
    {
        $result = $part->getContent();

        try {
            $encoding = $part->getHeaderField('Content-Transfer-Encoding');
        } catch (\Zend\Mail\Exception\InvalidArgumentException $e) {
            $encoding = null;
        } catch (\Zend\Mime\Exception\RuntimeException $e) {
            $encoding = null;
        } catch (\Zend\Mail\Storage\Exception\RuntimeException $e) {
            $encoding = null;
        }
        $this->writeLog("encoding: " . $encoding);

        if ($encoding == 'quoted-printable') {
            $result = quoted_printable_decode($result);
        }

        if ($encoding == 'base64') {
            $result = base64_decode($result);
        }

        return $result;
    }

    private function recordNdr()
    {
        getSymfonyContainer()->get(\AwardWallet\MainBundle\Manager\NDRManager::class)->recordNDR(
            $this->data['email'],
            $this->messageId,
            $this->data['message'] == 'abuse', $this->data['message']
        );
    }
}

// start
$container = getSymfonyContainer();
$gMailParser = new GMailParser();
$options = getopt("if:");

if (isset($options['f'])) {
    $gMailParser->parseRaw(file_get_contents($options['f']));
}

if (isset($options['i'])) {
    $gMailParser->connect($container->getParameter("ndr_email_address"), $container->getParameter("ndr_email_password"));
    $gMailParser->scannerGo();
}

echo "OK\n";
