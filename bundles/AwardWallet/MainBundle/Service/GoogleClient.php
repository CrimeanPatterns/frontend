<?php

namespace AwardWallet\MainBundle\Service;

class GoogleClient extends \Google_Client
{
    private string $refreshToken;

    // If modifying these scopes, delete your previously saved credentials
    // at CREDENTIALS_PATH
    private $scopes = [
        \Google_Service_Gmail::GMAIL_READONLY,
    ];

    /**
     * create it with:
     * https://github.com/AwardWallet/email/blob/87eee54b702a334f55015fa3fb65d6005645c605/doc/requests/connectGoogleMailbox.http
     * see doc:
     * https://awardwallet.com/api/email#method-email_scanner_7
     * then grab refreshToken from email database and delete mailbox.
     */
    public function __construct(string $googleClientId, string $googleClientSecret, string $mailboxRefreshToken, array $config = [])
    {
        parent::__construct($config);
        $this->refreshToken = $mailboxRefreshToken;
        $this->setScopes($this->scopes);
        $this->setAccessType('offline');
        $this->setClientId($googleClientId);
        $this->setClientSecret($googleClientSecret);
    }

    public function fetchAccessTokenWithRefreshToken($refreshToken = null)
    {
        return parent::fetchAccessTokenWithRefreshToken($this->refreshToken);
    }
}
