<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CaptchaController extends AbstractController
{
    /**
     * @Route("/captcha/recognize", name="aw_captcha_recognize", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     **/
    public function recognizeAction(
        Request $request,
        AntiBruteforceLockerService $securityAntibruteforceCaptcha,
        AwTokenStorageInterface $tokenStorage
    ) {
        // do not block updater ticks while we are waiting for captcha
        $session = $request->getSession();
        $session->save();

        if (
            !empty(
                $error = $securityAntibruteforceCaptcha->checkForLockout(
                    $request->getClientIp()
                )
            ) || !empty(
                $error = $securityAntibruteforceCaptcha->checkForLockout(
                    $tokenStorage->getBusinessUser()->getLogin()
                )
            )
        ) {
            return $this->json([
                'success' => false,
                'error' => $error,
            ]);
        }

        $imageUrl = $request->get('captcha');
        $extension = $request->get('extension');
        $postDataExtended = $request->get('postDataExtended', []);
        $reCaptcha = false;

        if (empty($imageUrl) || empty($extension)) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid captcha',
            ]);
        }

        $checker = new \TAccountChecker();
        $account = new \Account(intval($session->get('ExtensionAccountID')));
        $accountInfo = $account->getAccountInfo();
        $checker->SetAccount($accountInfo); // for captcha stats

        $checker->InitBrowser();
        // convert img from base64
        $marker = "data:image/png;base64,";
        $marker2 = "data:image/gif;base64,";

        if (strpos($imageUrl, $marker) === 0) {
            $captcha = substr($imageUrl, strlen($marker));
            $file = tempnam(sys_get_temp_dir(), 'captcha') . '.png';
            file_put_contents($file, base64_decode($captcha));
        } elseif (strpos($imageUrl, $marker2) === 0) {
            $captcha = substr($imageUrl, strlen($marker2));
            $file = tempnam(sys_get_temp_dir(), 'captcha') . '.gif';
            file_put_contents($file, base64_decode($captcha));
        } else {// google reCaptcha v.2
            if (strstr($imageUrl, 'https://www.google.com/recaptcha/api/fallback')) {
                $reCaptcha = true;
                $checker->http->GetURL($imageUrl);

                if (!$checker->http->ParseForm(null, 1)) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Invalid url',
                    ]);
                }

                if ($checker->http->FindPreg("/(?:Select all images with|Select all the|Выберите все|Выберите всю)/")) {
                    $checker->http->Log("Skip the selection of images");

                    return $this->json([
                        'success' => false,
                        'error' => 'Needed one image',
                    ]);
                }
                $formURL = $checker->http->FormURL;
                $form = $checker->http->Form;

                if ($imageLocation = $checker->http->FindSingleNode("//img/@src")) {
                    $checker->http->Log("Get IMG");
                    $checker->http->NormalizeURL($imageLocation);
                    $file = $checker->http->DownloadFile($imageLocation, "jpg");
                } else {
                    return $this->json([
                        'success' => false,
                        'error' => 'IMG not found',
                    ]);
                }
            } else {
                $file = $checker->http->DownloadFile($imageUrl, $extension);
            }
        }// else {// google reCaptcha v.2

        $recognizer = $checker->getCaptchaRecognizer();

        try {
            $captcha = trim($recognizer->recognizeFile($file, $postDataExtended));
        } catch (\CaptchaException $e) {
            $checker->http->Log("exception: " . $e->getMessage());

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        // google reCaptcha v.2
        if ($reCaptcha && isset($form, $formURL)) {
            $checker->http->FormURL = $formURL;
            $checker->http->Form = $form;
            unset($checker->http->Form['reason']);
            $checker->http->SetInputValue('response', $captcha);

            if (!$checker->http->PostForm()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Captcha not found',
                ]);
            }
            $captcha = $checker->http->FindSingleNode("//textarea");
        }

        return $this->json([
            'success' => true,
            'recognized' => $captcha,
            'source' => $imageUrl,
        ]);
    }
}
