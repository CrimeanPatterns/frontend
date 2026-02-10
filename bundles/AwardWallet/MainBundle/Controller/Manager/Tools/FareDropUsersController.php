<?php

namespace AwardWallet\MainBundle\Controller\Manager\Tools;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Service\User\Async\FareDropUsersExecutor;
use AwardWallet\MainBundle\Service\User\Async\FareDropUsersTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FareDropUsersController
{
    private Connection $unbufConnection;

    public function __construct(
        Connection $unbufConnection
    ) {
        $this->unbufConnection = $unbufConnection;
    }

    /**
     * @Route("/manager/tools/faredrop-users", name="aw_manager_tools_faredrop_users")
     * @Template("@AwardWalletMain/Manager/Tools/fareDropUsers.html.twig")
     */
    public function indexAction(
        Request $request,
        Process $asyncProcess,
        AwTokenStorage $tokenStorage,
        Client $sockClicent,
        FareDropUsersExecutor $fareDropUsersExecutor
    ) {
        $channel = UserMessaging::getChannelName(
            'faredropusers' . bin2hex(random_bytes(3)),
            $tokenStorage->getUser()->getId()
        );
        $response = [
            'title' => '',
            'channel' => $channel,
            'centrifuge_config' => $sockClicent->getClientData(),
        ];

        /** @var UploadedFile $file */
        $file = $request->files->get('hashFile');

        if (!empty($file)) {
            $hashesFile = $file->getPath() . '/' . $file->getFilename();

            if (file_exists($hashesFile)) {
                $hashes = explode("\n", file_get_contents($hashesFile));
                $task = new FareDropUsersTask($channel, $hashes);

                $response['loading'] = true;
                $asyncProcess->execute($task);
                // $fareDropUsersExecutor->execute($task);

                unlink($hashesFile);
            }
        }

        return $response;
    }
}
