<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class PingController extends AbstractController
{
    use JsonTrait;

    /**
     * @Route("/ping", name="aw_ping", options={"expose" = true})
     */
    public function indexAction(Request $request, LoggerInterface $loggerStat)
    {
        if ($request->isMethod('POST')) {
            $data = @json_encode($postData = $request->request->all());

            if ($data !== false && sizeof($postData) && strlen($data) < 30000) {
                $loggerStat->debug('Form data', [
                    'form_data' => $postData,
                ]);
            }
        }

        return $this->successJsonResponse();
    }
}
