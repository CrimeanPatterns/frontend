<?php

namespace AwardWallet\MainBundle\Controller\Admin;

use AwardWallet\MainBundle\Service\Backup\BackupCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugProxyController extends AbstractController
{
    /**
     * @Route("/admin/save-debug-proxy-token")
     * @Security("is_granted('SITE_DEV_MODE')")
     */
    public function saveTokenAction(Request $request, \HttpDriverInterface $httpDriver)
    {
        if ($request->query->get("state") === "local") {
            $server = "Local";
        } else {
            $server = "Remote";
        }

        $response = $httpDriver->request(new \HttpDriverRequest(($server === 'Local' ? 'http://awardwallet.docker' : 'https://awardwallet.com') . '/api/oauth2/token.php', 'POST', [
            'client_id' => 'local',
            'client_secret' => BackupCommand::DEVELOPER_PASSWORD,
            'code' => $request->query->get('code'),
            'scope' => 'debugProxy',
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://awardwallet.docker/admin/save-debug-proxy-token',
        ]));

        $token = json_decode($response->body, true);

        if ($response->httpCode != 200 || !is_array($token) || !isset($token['access_token'])) {
            throw new \Exception("invalid response: " . $response->httpCode . " " . $response->body);
        }

        file_put_contents(__DIR__ . '/../../../../../app/config/debugProxyToken' . $server . '.json', $response->body);

        return new Response('Token saved app/config/debugProxyToken' . $server . '.json. <a href="/admin/debugProxy.php">Return to debugProxy</a>');
    }
}
