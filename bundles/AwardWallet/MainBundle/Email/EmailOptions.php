<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;

class EmailOptions
{
    public const SILENT_SUFFIX = "+silent";
    public const UPDATE_ONLY_SUFFIX = "+updateonly";
    public const GMAIL_FILTER_SUFFIX = "+f";

    /** @var \PlancakeEmailParser */
    public $parser;

    /** @var bool */
    public $full = false;

    /** @var bool */
    public $forwardToUser;

    /** @var array */
    public $recipientParts;

    /** @var string */
    public $messageId;
    /**
     * silent parsing, do not send any notifications to user.
     *
     * @var bool
     */
    public $silent = false;
    /**
     * do not create new reservations, only update existing ones.
     *
     * @var bool
     */
    public $updateOnly = false;

    public bool $forwardedWithFilter = false;

    /** @var ParsedEmailSource */
    public $source;

    public function __construct(ParseEmailResponse $data, bool $fromScanner)
    {
        $message = base64_decode($data->email);
        $userData = @json_decode($data->userData, true);

        if (empty($message)) {
            throw new InvalidDataException('missing email body');
        }
        $this->parser = new \PlancakeEmailParser($this->cutEmail($message));
        $this->full = false;
        $this->parseRecipient();
        $this->messageId = sha1($data->email);
        $this->forwardToUser = empty($data->metadata->nested) && !$fromScanner;
        $isGpt = $data->parsingMethod === 'ai';

        if ($fromScanner && isset($userData['email'])) {
            $this->source = new ParsedEmailSource(ParsedEmaiLSource::SOURCE_SCANNER, $userData['email'], $data->requestId, $isGpt);
        } elseif (!empty($address = $this->getToAddress()) && (isset($userData['aw-dto']) || isset($userData['source']) && $userData['source'] == 'plans')) {
            $this->source = new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, $address, $data->requestId, $isGpt);
        } else {
            $this->source = new ParsedEmailSource(ParsedEmailSource::SOURCE_UNKNOWN, null, $data->requestId);
        }
    }

    /**
     * @param string $source base64 encoded source straight from response
     * @return void
     */
    public function refreshEmail(string $source)
    {
        if ($this->full) {
            return;
        }
        $source = base64_decode($source);

        if (!empty($source)) {
            $this->parser = new \PlancakeEmailParser($source);
            $this->full = true;
        }
    }

    public function isCycled()
    {
        $from = Util::clearHeader($this->parser->getHeader("from"));

        return strcasecmp($from, "do.not.reply@awardwallet.com") == 0;
    }

    private function cutEmail(string $source)
    {
        $parts = explode("\n\n", $source);

        return $parts[0] . "\n\nbody";
    }

    private function parseRecipient()
    {
        $this->recipientParts = [];

        if ($to = $this->getToAddress()) {
            $mailbox = preg_replace("/@.+$/ims", "", $to);

            if (stripos($mailbox, self::SILENT_SUFFIX) !== false) {
                $this->silent = true;
                $mailbox = str_ireplace(self::SILENT_SUFFIX, "", $mailbox);
            }

            if (stripos($mailbox, self::UPDATE_ONLY_SUFFIX) !== false) {
                $this->updateOnly = true;
                $mailbox = str_ireplace(self::UPDATE_ONLY_SUFFIX, "", $mailbox);
            }

            if (stripos($mailbox, self::GMAIL_FILTER_SUFFIX) !== false) {
                $this->forwardedWithFilter = true;
                $mailbox = str_ireplace(self::GMAIL_FILTER_SUFFIX, "", $mailbox);
            }

            $parts = explode(".", $mailbox);

            if (preg_match("/^b\.\d+/", $mailbox) && isset($parts[1])) {
                $parts[1] = "b." . $parts[1];
                array_shift($parts);
            }
            $this->recipientParts = $parts;
        }
    }

    /**
     * get address from "To" or "Delivered-To" headers
     * "Delivered-To" is used when "To" does not match @ awardwallet.com
     * this happens when message was forwarded from gmail.
     *
     * @return null on error
     */
    private function getToAddress()
    {
        foreach (["AW-Origin-To", "AW-Origin-Delivered-To", "AW-Origin-X-Forwarded-To", "To", "Delivered-To", "X-Forwarded-To"] as $header) {
            // weird behaviour, not sure when it returns array or string
            $headers = $this->parser->getHeaderArray($header);

            if (!is_array($headers)) {
                $headers = [$headers];
            }

            foreach ($headers as $value) {
                $email = Util::clearHeader($value);

                if ($this->validTo($email)) {
                    return $email;
                }
            }
        }

        return null;
    }

    private function validTo($email)
    {
        return preg_match('/^[^,]+@(email[.])?awardwallet\.com$/ims', $email) && !preg_match('/^testplans@awardwallet\.com$/ims', $email);
    }
}
