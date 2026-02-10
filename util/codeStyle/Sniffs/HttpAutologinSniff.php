<?php

namespace AwardWallet\CS;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class HttpAutologinSniff implements Sniff
{

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(int)
     */
    public function register()
    {
        return array(T_CONSTANT_ENCAPSED_STRING);

    }

    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $functionPtr = $phpcsFile->findPrevious([T_FUNCTION], $stackPtr);
        if ($functionPtr !== false) {
            $functionNamePtr = $phpcsFile->findNext([T_STRING], $functionPtr);
            if ($functionNamePtr !== false && $tokens[$functionNamePtr]["content"] === "LoadLoginForm") {
                $this->openConnection();
                $providerCode = $this->extractProviderCode($phpcsFile);
                $provider = $this->loadProviderInfo($providerCode);
                if ($provider !== null && $this->serverAutoLogin($provider)) {
                    $link = substr($tokens[$stackPtr]['content'], 1, -1);
                    $httpProvider = $this->isHttpProvider($provider);
                    if ($this->isHttpLink($link)) {
                        if ($httpProvider) {
                            if ($this->isRedirectToHttps($link)) {
                                $error = 'http:// link redirecting to https in LoadLoginForm; %s';
                                $data = array($link);
                                $phpcsFile->addError($error, $stackPtr, 'Found', $data);
                            }
                        } else {
                            $error = 'http:// url in LoadLoginForm, while LoginURL is https; %s';
                            $data = array(trim($tokens[$stackPtr]['content']));
                            $phpcsFile->addError($error, $stackPtr, 'Found', $data);
                        }
                    }
                    if ($this->isHttpsLink($link) && $httpProvider) {
                        $error = 'https:// url in LoadLoginForm, while LoginURL is http; %s';
                        $data = array(trim($tokens[$stackPtr]['content']));
                        $phpcsFile->addError($error, $stackPtr, 'Found', $data);
                    }
                }
            }
        }
    }

    private function openConnection()
    {
        if ($this->connection !== null) {
            return;
        }

        $this->connection = new \PDO('mysql:dbname=awardwallet;host=mysql', 'awardwallet', 'awardwallet', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);
    }

    private function extractProviderCode(File $phpcsFile) : string
    {
        return basename(dirname($phpcsFile->path));
    }

    private function loadProviderInfo(string $providerCode) : ?array
    {
        $q = $this->connection->prepare("select LoginURL, State, AutoLogin from Provider where Code = ?");
        $q->execute([$providerCode]);
        $result = $q->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    private function serverAutoLogin(array $provider) : bool
    {
        return
            $provider !== false
            && $provider["State"] > 0
            && in_array($provider["AutoLogin"], [AUTOLOGIN_SERVER, AUTOLOGIN_MIXED]);
    }

    private function isHttpProvider(array $provider) : bool
    {
        return $this->isHttpLink($provider["LoginURL"]);
    }

    private function isHttpLink($url) : bool
    {
        return strtolower(substr($url, 0, 7)) === 'http://';
    }

    private function isRedirectToHttps(string $link) : bool
    {
        $curl = curl_init($link);
        try {
            curl_setopt_array($curl, [
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            curl_exec($curl);
            $info = curl_getinfo($curl);
            if (isset($info['url']) && $this->isHttpsLink($info['url'])) {
                return true;
            }
        }
        finally {
            curl_close($curl);
        }
        return false;
    }

    private function isHttpsLink($url) : bool
    {
        return strtolower(substr($url, 0, 8)) === 'https://';
    }

}