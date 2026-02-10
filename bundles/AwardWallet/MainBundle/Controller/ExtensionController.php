<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Extensionstat;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Service\LinkTargetHostResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sinergi\BrowserDetector\Browser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ExtensionController extends AbstractController
{
    /**
     * @Route("/engine/{providerCode}/extension.js", name="aw_extension_js", requirements={"providerCode" = "\w+"})
     */
    public function extensionJsAction(
        string $providerCode,
        Request $request,
        EntityManagerInterface $em,
        KernelInterface $kernel,
        \Memcached $memcached,
        LoggerInterface $logger,
        LoggerInterface $loggerStat,
        LinkTargetHostResolver $targetHostResolver
    ) {
        $srcDir = $kernel->getProjectDir() . '/web';
        $fileName = $srcDir . '/../engine/' . $providerCode . '/extension.js';

        if (!file_exists($fileName)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($fileName);
        $content .= "\n\n// utilities \n\n" . file_get_contents($srcDir . '/extension/util.js');

        if (!$this->isGranted('ROLE_USER')) {
            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        /** @var Provider $provider */
        $provider = $em->getRepository(Provider::class)->findOneBy(["code" => $providerCode]);

        if (empty($provider)) {
            return new Response('Provider not found', 400);
        }

        if (!preg_match('#hosts\s*:\s*(\{[^\}]*\})#ims', $content, $matchesHosts)) {
            $logger->warning('could not parse hosts in ' . $fileName);

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        $matchesHosts[1] = preg_replace('#\/\/[^n]*\n#ims', '', $matchesHosts[1]);
        // problem with decoding json, the difference of versions PHP associated with keys in single quotes
        $hosts = json_decode((function () use ($matchesHosts) {
            $regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;

            return preg_replace_callback($regex, function ($matches) {
                return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
            }, $matchesHosts[1]);
        })(), true);

        if (is_empty($hosts)) {
            $logger->warning("empty hosts in $providerCode");

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        if (!preg_match('#(cashbackLink\s*:\s*)(\'\')(,)#ims', $content, $matchesLink)) {
            $logger->warning("could not find cashbackLink in $providerCode");

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        /** @var Usr $user */
        $user = $this->getUser();

        if ($user && $user->isAwPlus() && $user->isLinkAdsDisabled()) {
            $logger->info('User with disabled ADS link');

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        if (!empty($link = $provider->getClickurl())) {
            $targetHost = $targetHostResolver->getTargetHostForLink($link, $hosts);

            if (!empty($targetHost)) {
                $refCode = ($user instanceof Usr ? $user->getRefcode() : 'awardwallet');
                $isMobile = UserAgentUtils::isMobileBrowser($_SERVER['HTTP_USER_AGENT']);
                $link = str_ireplace("AWREFCODE", $refCode . ($isMobile ? '-m' : '-d'), $link);
                $loggerStat->info('partner autologin', [
                    'accountId' => 0,
                    'provider' => $providerCode,
                    'targetHost' => $targetHost,
                    'ua' => $_SERVER['HTTP_USER_AGENT'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'isMobile' => $isMobile,
                    'clickUrl' => $link,
                ]);
            }
        }

        if (!empty($targetHost)) {
            $hosts[$targetHost] = true;
        }

        if (!empty($link)) {
            $content = str_replace($matchesLink[0], $matchesLink[1] . "'" . $link . "'" . $matchesLink[3], $content);
            $content = str_replace($matchesHosts[0], "hosts: " . json_encode($hosts), $content);
        }

        if ($request->query->has('v')) {
            $browser = new Browser($request->headers->get('User-Agent'));
            $logger->info('extension version', [
                'version' => $request->query->get('version'),
                'UserAgent' => $request->headers->get('User-Agent'),
                'Browser' => $browser->getName(),
            ]);
        }

        return new Response($content, 200, ['Content-Type' => 'text/javascript']);
    }

    /**
     * @Route("/extension-install", name="aw_extension_install", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Extension/extensionInstall.html.twig")
     */
    public function extensionInstallAction(Request $request, Environment $twigEnv)
    {
        $userAgent = $request->headers->get('User-Agent');

        if (stripos($userAgent, 'Macintosh') !== false) {
            $os = 'mac';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'linux';
        } else {
            $os = 'windows';
        }

        $twigEnv->addGlobal('webpack', true);

        return [
            'os' => $os,
            'staff' => $this->isGranted('ROLE_STAFF'),
        ];
    }

    /**
     * @Route("/extension/extensionStats.php", name="aw_extension_stats")
     * @Security("is_granted('ROLE_USER')")
     */
    public function extensionStatsAction(
        Request $request,
        Connection $connection,
        ProviderRepository $providerRepository
    ) {
        if (strpos($request->headers->get('content-type'), 'application/json') !== false) {
            $requestData = JsonRequestHandler::parse($request);

            if (null === $requestData) {
                return new Response('');
            }

            if (
                isset($requestData['mobileKind'])
                && !StringHandler::isEmpty($requestData['mobileKind'])
                && isset($requestData['providerCode'])
                && is_string($requestData['providerCode'])
            ) {
                $platform = 'mobile-' . $requestData['mobileKind'];
            } else {
                return new Response('');
            }
        } else {
            $requestData = $_POST;
            $platform = 'desktop';
        }

        $success = ArrayVal($requestData, 'success');

        if (!in_array($success, [0, 1])) {
            return new Response('');
        }

        $providerCode = $requestData['providerCode'] ?? '';

        if (isset($requestData['errorMessage']) && !is_string($requestData['errorMessage'])) {
            $requestData['errorMessage'] = json_encode($requestData['errorMessage']);
        }

        $error = htmlspecialchars($requestData['errorMessage'] ?? '');
        $errorCode = $requestData['errorCode'] ?? '';
        $accountId = !empty($requestData['accountId']) ? $requestData['accountId'] : null;

        if (!StringHandler::isEmpty($providerCode)) {
            $provider = $providerRepository->findOneBy(['code' => $providerCode]);

            if (!$provider) {
                return new Response('');
            }

            $connection->executeStatement("
                insert into `ExtensionStat` (
                    ProviderID,
                    AccountID, 
                    Status,
                    ErrorText, 
                    ErrorCode, 
                    ErrorDate, 
                    Platform
                )
                values (?, ?, ?, ?, ?, now(), ?)
                on duplicate key update Count = Count + 1",
                [
                    $provider->getId(),
                    $accountId,
                    $success ? Extensionstat::STATUS_SUCCESS : Extensionstat::STATUS_FAIL,
                    substr($error, 0, 250),
                    $errorCode,
                    $platform,
                ],
                [
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                ]
            );
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/extension/version-report", name="aw_extension_version_report", methods={"POST"}, options={"expose": true})
     */
    public function versionReportAction(Request $request, LoggerInterface $logger)
    {
        $browser = new Browser($request->headers->get('User-Agent'));

        $logger->info('extension version', [
            'version' => $request->query->get('version'),
            'UserAgent' => $request->headers->get('User-Agent'),
            'Browser' => $browser->getName(),
        ]);

        return $this->json('ok');
    }
}
