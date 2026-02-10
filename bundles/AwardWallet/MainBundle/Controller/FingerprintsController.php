<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\Strings\Strings;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sinergi\BrowserDetector\Browser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FingerprintsController
{
    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/save-fingerprints", name="aw_save_fingerprints", methods={"POST"}, options={"expose"=true})
     */
    public function saveAction(Request $request, Connection $connection, LoggerInterface $logger, AwTokenStorage $tokenStorage): Response
    {
        $fp = @json_decode($request->getContent(), true);

        if (!is_array($fp) || !isset($fp['fp2']['userAgent'])) {
            $logger->warning("invalid fingerprints received: " . Strings::cutInMiddle($request->getContent(), 2048));

            return new Response('invalid fp, will ignore');
        }

        unset($fp['proof']);
        $browser = new Browser($fp['fp2']['userAgent']);

        $params = [
            'UserID' => $tokenStorage->getUser()->getId(),
            'Hash' => $this->createHash($fp),
            'BrowserFamily' => strtolower($browser->getName()),
            'BrowserVersion' => $browser->getVersion() ? (int) preg_replace('#\..+$#ims', '', $browser->getVersion()) : null,
            'Platform' => $fp['platform'] ?? null,
            'IsMobile' => UserAgentUtils::isMobileBrowser($fp['fp2']['userAgent']) ? 1 : 0,
            'Fingerprint' => json_encode($fp),
        ];

        $connection->executeUpdate("
            insert into Fingerprint(
                UserID,
                Hash,
                BrowserFamily,
                BrowserVersion,
                Platform,
                IsMobile,
                Fingerprint
            )
            values(
                :UserID,
                :Hash,
                :BrowserFamily,
                :BrowserVersion,
                :Platform,
                :IsMobile,   
                :Fingerprint
            )
            on duplicate key update 
                BrowserFamily = :BrowserFamily,
                BrowserVersion = :BrowserVersion,
                Platform = :Platform,
                IsMobile = :IsMobile,                    
                Fingerprint = :Fingerprint,
                LastSeen = current_timestamp()            
            ",
            $params
        );

        $logger->info("saved fingerprint", $params);

        return new Response("ok, saved");
    }

    private function createHash(array $fp): string
    {
        return sha1($fp['fp2']['userAgent']);
    }
}
