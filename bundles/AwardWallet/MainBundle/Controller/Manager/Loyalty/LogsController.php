<?php

namespace AwardWallet\MainBundle\Controller\Manager\Loyalty;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Resources\AdminLogsRequest;
use Aws\S3\S3Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route ("/manager/loyalty")
 */
class LogsController extends AbstractController
{
    public const TIMEOUT = 30;

    private const RA_CLUSTERS = ['juicymiles', 'ra-awardwallet'];

    private S3Client $s3Client;
    private RouterInterface $router;
    private string $logDir;
    private PasswordDecryptor $passwordDecryptor;
    private ServiceLocator $loyaltyApiCommunicators;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        S3Client $s3Client,
        RouterInterface $router,
        string $checkerLogDir,
        PasswordDecryptor $passwordDecryptor,
        ServiceLocator $loyaltyApiCommunicators,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->s3Client = $s3Client;
        $this->router = $router;
        $this->logDir = $checkerLogDir;
        $this->passwordDecryptor = $passwordDecryptor;
        $this->loyaltyApiCommunicators = $loyaltyApiCommunicators;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/logs/{cluster}/{filename}", name="aw_manager_loyalty_logs_item")
     * @Security("is_granted('ROLE_MANAGE_LOGS') or is_granted('ROLE_MANAGE_LOGS_REWARD_AVAILABILITY')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logsItemAction(Request $request, string $cluster, $filename): Response
    {
        $this->checkCusterAccess($cluster);

        if (substr($filename, -4) !== '.zip') {
            $filename .= '.zip';
        }

        $filePath = $this->logDir . "/" . $filename;

        if (!file_exists($filePath)) {
            if (!file_exists($this->logDir)) {
                if (!mkdir($this->logDir, 0777, true) && !is_dir($this->logDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->logDir));
                }
            }

            $this->downloadLog($cluster, $filename, $filePath);
        }

        return $this->renderLog($request, $filePath);
    }

    /**
     * @Route("/local-log/{file}", name="aw_manager_loyalty_local_log")
     * @Security("is_granted('SITE_DEV_MODE')")
     */
    public function localLog(Request $request, string $file)
    {
        $file = $this->logDir . "/" . $file . ".zip";

        if (!file_exists($file) && preg_match('#account\-(\d+)#ims', $file, $matches)) {
            $file = str_replace($this->logDir, $this->logDir . "/" . sprintf("%03d", round($matches[1]) / 1000), $file);
        }

        if (!file_exists($file)) {
            throw new BadRequestHttpException("Missing file");
        }

        return $this->renderLog($request, $file);
    }

    /**
     * @Route("/logs", name="aw_manager_loyalty_logs")
     * @Security("is_granted('ROLE_MANAGE_LOGS') or is_granted('ROLE_MANAGE_LOGS_REWARD_AVAILABILITY')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logsAction(Request $request)
    {
        $layoutData = [];
        $accountID = $request->get('AccountID');
        $confNo = $request->get('ConfNo');
        $provider = $request->get('Code');
        $login = $request->get('Login');
        $showLatest = $request->get('ShowLatest');
        $requestId = $request->get('RequestID');
        $partner = $request->query->get('Partner') ?? $request->request->get('form')['partner'] ?? ($this->isGranted('ROLE_MANAGE_LOGS') ? 'awardwallet' : 'juicymiles');
        $cluster = $request->get('Cluster') ?? $request->request->get('form')['cluster'] ??
            ($partner === 'juicymiles' ? $partner : null); // if null then define after method
        $method = $request->get('Method') ?? ($this->isGranted('ROLE_MANAGE_LOGS') ? 'CheckAccount' : 'RewardAvailability');

        if (!empty($accountID)) {
            $userData = $accountID;
        } elseif (!empty($confNo)) {
            $userData = $confNo;
            $method = "CheckConfirmation";
        } elseif (!empty($requestId) && $confNo === '') {
            $userData = $confNo;
            $method = "CheckConfirmation";
        }

        switch ($method) {
            case 'reward-availability':
                $method = 'RewardAvailability';

                break;

            case 'reward-availability-hotel':
                $method = 'RaHotel';

                break;

            case 'reward-availability-register':
                $method = 'RegisterAccount';

                break;

            case 'keephotsession':
                $method = 'KeepHotSession';

                break;
        }

        if (null === $cluster) {
            if (in_array($method, ['RewardAvailability', 'RaHotel', 'RegisterAccount', 'KeepHotSession']) && $partner === 'awardwallet') {
                $cluster = 'ra-awardwallet';
            } elseif (in_array($method,
                ['RewardAvailability', 'RaHotel', 'RegisterAccount', 'KeepHotSession']) && $partner === 'juicymiles') {
                $cluster = 'juicymiles';
            } else {
                $cluster = 'awardwallet';
            }
        }

        $this->checkCusterAccess($cluster);

        $allowedMethods = [];

        if ($this->isGranted('ROLE_MANAGE_LOGS')) {
            $allowedMethods = array_merge($allowedMethods, [
                'Check Account' => 'CheckAccount',
                'Retrieve Confirmation' => 'CheckConfirmation',
                'AutoLogin' => 'AutoLogin',
                'AutoLogin With Extension' => 'AutoLoginWithExtension',
            ]);
        }

        $allowedMethods = array_merge($allowedMethods, [
            'RA Flights' => 'RewardAvailability',
            'RA Hotels' => 'RaHotel',
            'RA Register Account' => 'RegisterAccount',
            'RA Keep Hot Session' => 'KeepHotSession',
        ]);

        $form = $this->createFormBuilder()
            ->add('partner', TextType::class, ['data' => $partner, 'attr' => ["onClick" => "$(this).select();"]])
            ->add(
                'method',
                ChoiceType::class,
                ['choices' => $allowedMethods]
            )
            ->add(
                'cluster',
                ChoiceType::class,
                ['choices' =>
                    array_filter(
                        array_combine(
                            array_keys($this->loyaltyApiCommunicators->getProvidedServices()),
                            array_keys($this->loyaltyApiCommunicators->getProvidedServices())
                        ),
                        function (string $cluster) {
                            return $this->isGranted('ROLE_MANAGE_LOGS')
                                || in_array($cluster, self::RA_CLUSTERS);
                        }
                    ),
                ]
            )
            ->add('userData', TextType::class, ['label' => 'Account ID / ConfNo', 'required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('provider', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('login', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('login2', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('login3', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('requestId', TextType::class, ['label' => 'Request ID', 'required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->getForm();

        $response = null;

        if (
            (!empty($provider) || (!empty($userData) && $partner === 'awardwallet') || !empty($requestId))
            && $request->isMethod('GET')
        ) {
            if (empty($userData) || $partner !== 'awardwallet') {
                $userData = '';
            }

            $params = [
                'userData' => $userData,
                'partner' => $partner,
                'method' => $method,
                'cluster' => $cluster,
                'provider' => $provider,
                'login' => $login,
                'showLatest' => $showLatest,
                'requestId' => $requestId,
            ];
            $form->setData($params);
        } elseif ($request->isMethod('POST') && $params = $request->request->get('form')) {
            $form->setData($params);
        }

        if (!empty($params)) {
            /** @var ApiCommunicator $communicator */
            $communicator = $this->loyaltyApiCommunicators->get($params['cluster']);

            $response = $communicator->GetCheckerLogs(new AdminLogsRequest($params));
            $files = [];

            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'date' => $this->prettyDate($file->getUpdatedate()),
                    'filename' => $file->getFilename(),
                    'cluster' => $cluster,
                ];
            }

            if (in_array($params['method'], ['CheckAccount', 'AutoLogin']) && !empty($params['userData'])) {
                $extLogsFiles = $this->getExtensionAccountLogsFromS3(
                    $params['userData'],
                    $params['method'] === 'AutoLogin' ? 'autologin' : 'check'
                );

                foreach ($extLogsFiles as $file) {
                    $files[] = [
                        'date' => $this->prettyDate($file['LastModified']),
                        'filename' => $file['Key'],
                        'cluster' => 'extension',
                    ];
                }
            }

            usort($files, function ($log1, $log2) {
                return strtotime($log2["date"]) - strtotime($log1["date"]);
            });

            if (!empty($params['showLatest']) && count($files) > 0) {
                $file = array_shift($files);
                $redirectUrl = $this->router->generate('aw_manager_loyalty_logs_item', ["cluster" => $file['cluster'], "filename" => $file["filename"]]);

                return $this->redirect($redirectUrl);
            }

            $layoutData['files'] = $files;

            if (!empty($params['requestId'])) {
                $query = sprintf('RequestID=%s', $params['requestId']);

                if (
                    $params['method'] === 'CheckConfirmation'
                ) {
                    $query .= sprintf('&ConfNo=%s', $params['userData']);
                } elseif (
                    $params['method'] === 'RewardAvailability'
                ) {
                    $query .= '&Method=reward-availability&Cluster=' . $params['cluster'];
                } elseif (
                    $params['method'] === 'RaHotel'
                ) {
                    $query .= '&Method=reward-availability-hotel&Cluster=' . $params['cluster'];
                } elseif (
                    $params['method'] === 'RegisterAccount'
                ) {
                    $query .= '&Method=reward-availability-register&Cluster=' . $params['cluster'];
                } elseif (
                    $params['method'] === 'KeepHotSession'
                ) {
                    $query .= '&Method=keephotsession&Cluster=' . $params['cluster'];
                }
            } elseif (!empty($params['userData'])) {
                if ($params['method'] === 'CheckConfirmation') {
                    $query = sprintf('ConfNo=%s', $params['userData']);
                } else {
                    $query = sprintf('AccountID=%s', $params['userData']);
                }
            } elseif (!empty($params['login'])) {
                $query = sprintf('Login=%s', $params['login']);
            }

            if (!empty($query)) {
                $query .= sprintf('&Partner=%s', $params['partner']);

                if (!empty($params['provider'])) {
                    $query .= sprintf('&Code=%s', $params['provider']);
                }
                $layoutData['link'] = $query;
            }
        }

        $layoutData['form'] = $form->createView();

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/logs.html.twig', $layoutData);
    }

    public function formatClassesLogFile(string $s): string
    {
        $blocks = [
            'Account Check Parameters',
            'Load Login Form',
            'Login',
            'Parse',
            'Parse Itineraries',
            'Parse History',
            'Account Check Result',
            'Check Confirmation Result',
            'Loyalty Response',
        ];

        foreach ($blocks as $block) {
            $search = sprintf('<h2 class="awlog-info">%s</h2><br>', $block);
            $class = preg_replace('/\s+/', '', $block);
            $replace = sprintf("</div><div class=\"%s tabcontent\">\n%s", $class, $search);
            $s = str_replace($search, $replace, $s);
        }

        return $s;
    }

    private function renderLog(Request $request, string $file): Response
    {
        require_once __DIR__ . "/../../../../../../web/schema/PasswordVault.php";

        require_once __DIR__ . "/../../../../../../web/manager/passwordVault/common.php";

        $logName = FileName(basename($file));
        $result = new Response();
        $result->headers->set('Referrer-Policy', 'no-referrer');
        $index = $request->query->getInt('Index');

        if ($index < 0) {
            throw new BadRequestHttpException("invalid index");
        }

        $format = $request->query->getAlpha('Format', 'html');
        $pageURL = $request->query->get('pageURL');

        if (!in_array($format, ['source', 'html', 'image', 'pdf'])) {
            throw new BadRequestHttpException("invalid format");
        }

        $jslog = preg_match("/account\-(\d+)\-/ims", $file);
        $zip = new \ZipArchive();

        if (!$zip->open($file)) {
            throw new \Exception("failed to open zip");
        }

        if ($index >= $zip->numFiles) {
            throw new BadRequestHttpException("invalid index");
        }

        $name = $zip->getNameIndex($index);

        // refs #15385
        $whiteListCookies = [
            "AWSALB",
            "CC",
            "Log",
            "PasswordSaved",
            "PHPSESSID",
            "PromoSubscribe",
            "PwdHash",
            "SavePwd",
            "SB",
            "userId",
            "XDEBUG_SESSION",
            "XSRF-TOKEN",
        ];

        if ($name !== 'log.html' && $format === 'html' && !$request->isXmlHttpRequest() && !$request->query->has("frame")) {
            $result->setContent("You should not view saved pages by direct link, to prevent Referer and Origin leak.
            Please open a step link in the main log file.");

            return $result;
        }

        if ($format === 'pdf') {
            $result->setContent($zip->getFromIndex($index));
            $result->headers->set('Content-Type', 'application/pdf');

            return $result;
        }

        foreach ($request->cookies->all() as $key => $value) {
            if (
                !in_array($key, $whiteListCookies)
                && !empty(trim($key))
                && !preg_match('#[' . preg_quote('=,; \t\r\n\013\014', '#') . ']#ims', $key)
            ) {
                $result->headers->setCookie(AwCookieFactory::createLax($key, null, time() - 1000));
                $result->headers->setCookie(AwCookieFactory::createLax($key, null, time() - 1000, "/"));
                $result->headers->setCookie(AwCookieFactory::createLax($key, null, time() - 1000, "/", "." . $request->getHost()));
            }
        }

        // TODO seems no need this block. image load at $this->formatLogFile as <img>
        if (!$jslog && preg_match('/\.(png|jpg|jpeg|gif)$/ims', $name)) {
            $result->headers->set('Content-type', 'image/png');
            $result->setContent($zip->getFromIndex($index));

            return $result;
        }

        if (preg_match('/\.(pdf)$/ims', $name)) {
            $result->headers->set('Content-type', 'application/pdf');
            $result->setContent($zip->getFromIndex($index));

            return $result;
        }

        ob_start();

        global $zipIndexes;
        $zipIndexes = [];

        $links = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $baseName = $zip->getNameIndex($i);
            $zipIndexes[$baseName] = $i;

            if ($jslog) {
                if (preg_match('/log\.html/', $baseName)) {
                    $links[$baseName] = "<a href='?Format=html&Index={$i}&NoScript=1'>" . preg_replace('/-screenshot.+/ims', '', $baseName) . "</a>";
                } else {
                    $step = '';
                    $fileName = $name = preg_replace('/\.(\w+)$/ims', '', $baseName);
                    $ext = FileExtension($baseName);

                    if (preg_match('/step(\d+)-(.+)\.(\w+)$/', $baseName, $m)) {
                        $step = $m[1];
                        $name = $m[2];
                    }

                    if (strpos($ext, 'htm') !== false) {
                        $addLink = "step {$step}: <a href='?Format=html&Index={$i}&NoScript=1' class='open-log-link'>" .
                            $name . "</a>&nbsp;(<a href='?Format=source&Index={$i}'>" . $ext . "</a>)";

                        if (isset($links[$fileName])) {
                            $links[$fileName] = $addLink . $links[$fileName];
                        } else {
                            $links[$fileName] = $addLink;
                        }
                    } else {
                        $addLink = "&nbsp;<a href='?Format=image&Index={$i}&NoScript=1' class='open-log-link'>(screenshot)</a>";

                        if (isset($links[$fileName])) {
                            $links[$fileName] .= $addLink;
                        } else {
                            $links[$fileName] = $addLink;
                        }
                    }
                }
            } elseif (!preg_match('/step|log\.html/', $baseName)) {
                $links[] = "<a href='?Format=" . $this->getFormat(FileExtension($baseName)) . "&Index={$i}&NoScript=1'>" . preg_replace('/-screenshot.+/ims', '', $baseName) . "</a>";
            }
        }

        if (!isset($pageURL)) {
            echo "<div>" . $file . "</div>";

            if (!$jslog) {
                $requestIdRegex = '/(\w+)_(checkaccount|checkconfirmation|rewardavailability|rewardavailabilityhotel|registeraccount|keephotsession)_[a-z0-9]+_([a-z0-9]+)/i';

                if (preg_match($requestIdRegex, $file, $m)) {
                    $partner = $m[1];
                    $requestId = $m[3];
                    $params = ['RequestID' => $requestId, 'Partner' => $partner];

                    switch ($m[2]) {
                        case 'keephotsession':
                            $params['Method'] = 'keephotsession';

                            break;

                        case 'rewardavailability':
                            $params['Method'] = 'reward-availability';

                            break;

                        case 'rewardavailabilityhotel':
                            $params['Method'] = 'reward-availability-hotel';

                            break;

                        case 'registeraccount':
                            $params['Method'] = 'reward-availability-register';

                            break;

                        case 'checkconfirmation':
                            $params['Method'] = 'CheckConfirmation';

                            break;

                        default:
                            break;
                    }
                    $link = '<a href="' . $this->router->generate('aw_manager_loyalty_logs', $params) . '">link</a>';
                    echo "<div>$requestId $link</div>";
                }
            }

            if (empty($pageURL) && !$jslog) {
                echo "<h1>{$name}</h1>";
            }

            if (sizeof($links) > 0) {
                if (!$jslog) {
                    echo "<div> files: " . implode(" | ", $links) . "</div>";
                    echo '<hr>';
                } else {
                    echo "<div> files: " . implode(" <br/> ", $links) . "</div>";
                }
            }

            echo "<style>
            pre{
                margin: 0;
            }
            </style>";
            ?>

            <link rel="stylesheet" type="text/css" href="/assets/awardwalletnewdesign/css/base/logStyles.css">
            <link rel="stylesheet" type="text/css" href="/lib/3dParty/jquery/json-viewer/jquery.json-viewer.css">
            <style type="text/css">
                .ParseItineraries span.miss, .AccountCheckResult span.miss, .CheckConfirmationResult span.miss {
                    background-color: #b3ffff;
                }
                .ParseItineraries span.warn, .AccountCheckResult span.warn, .CheckConfirmationResult span.warn {
                    background-color: #ffff66;
                }
                .ParseItineraries span.err, .AccountCheckResult span.err, .CheckConfirmationResult span.err {
                    background-color: #ffad99;
                }
                span.bracket_itineraries.bracket_odd {
                    background-color: greenyellow;
                }
                span.bracket_itineraries.bracket_even {
                    background-color: darkviolet;
                }
            </style>
            <!-- Table of log contents -->
            <script src="/assets/common/vendors/jquery/dist/jquery.min.js"></script>
            <script src="/assets/awardwalletnewdesign/js/lib/logScripts.js?v=2"></script>
            <script src="/lib/3dParty/jquery/json-viewer/jquery.json-viewer.js"></script>
            <div id="awlog-contents"></div>
            <hr>
            <!-- /Table of log contents -->

            <?php
        }

        $s = $zip->getFromIndex($index);

        if (\preg_match('/^\s*Mobile Log/', $s)) {
            $s = preg_replace('/<br>\s*((?:App Version|Device Model|Platform):)/', '<br><b>$1</b>', $s);
            $s = "<pre>{$s}</pre>";
        }

        if (isset($pageURL) && $format !== 'source') {
            $s = urlToAbsolute($s, $pageURL);
        }

        $noScripts = $request->query->has('NoScript');
        $s = $this->formatLogFile($s, $format, $noScripts, $request->getSchemeAndHttpHost() . $request->getPathInfo());

        if (isset($pageURL)) {
            $result->headers->set("Content-Security-Policy", "script-src 'none'; cookie-scope 'none';");
            $result->headers->set("Referrer-Policy", "no-referrer");
        }

        $s = $this->formatClassesLogFile($s);

        if (preg_match("/\[Login\]\s+=\&gt\;\s+([^\[<]+)/ims", $s, $matches)) {
            $login = trim($matches[1]);
        }

        if (preg_match("/Provider code:\s*(\w+)/ims", $s, $matches)) {
            $providerCode = trim($matches[1]);
        }

        if (isset($login) && isset($providerCode)) {
            $ids = SQLToSimpleArray("SELECT pv.PasswordVaultID FROM PasswordVault pv JOIN Provider p ON pv.ProviderID = p.ProviderID
            WHERE p.Code = '" . addslashes($providerCode) . "' AND Login = '" . addslashes($login) . "'
            order by pv.ExpirationDate desc limit 10", "PasswordVaultID");

            if (!empty($ids)) {
                echo "Passwords available: " . implode(", ", array_map(function ($id) {
                    return "<a href='/manager/passwordVault/get.php?ID=$id'>$id</a>";
                }, $ids)) . "<br/>";
            }
        }

        echo $s;
        $zip->close();

        $s = ob_get_clean();

        if (preg_match("/\[Pass\]\s+=\&gt\;\s+([^\[\n]+)\</ims", $s, $matches)) {
            $pass = trim($matches[1]);
        }

        if (preg_match("/\[Login\]\s+=\&gt\;\s+([^\[\n]+)\</ims", $s, $matches)) {
            $login = trim($matches[1]);
        }

        // wsdl проверка
        if (preg_match("/^account\-(\d+)\-/ims", $logName, $matches)) {
            $accountId = intval($matches[1]);
        } else {
            // json проверка
            if (preg_match("/\[AccountID\]\s+=\&gt\;\s+([^\[\n]+)\</ims", $s, $matches)) {
                $accountId = trim($matches[1]);
            }
        }

        if (isset($accountId)) {
            $q = new \TQuery("select a.Login, a.Pass, p.Kind
            from Account a
            join Provider p on a.ProviderID = p.ProviderID
            where a.AccountID = " . (int) $accountId);

            if (!$q->EOF && ($q->Fields['Pass'] != '')) {
                $pass = $this->passwordDecryptor->decrypt($q->Fields['Pass']);
                $login = $q->Fields['Login'];
                $kind = $q->Fields['Kind'];
            }
        }

        if (isset($pass) && strlen($pass) === 1) {
            unset($pass);
        }

        if (isset($pass) && $request->query->has('ShowPasswords') && (isset($login) || isset($accountId))) {
            // credit card ?
            if (isset($kind) && ($kind == PROVIDER_KIND_CREDITCARD) && schemaAccessAllowed("creditCards")) {
                exit("You are allowed to view CC passwords. Password is: " . $pass);
            }
            // check password access through password vault
            $pv = searchPasswordVault($accountId, $login);

            if (empty($pv) || empty($pv['Approved'])) {
                if (isset($accountId)) {
                    Redirect("/manager/passwordVault/requestPassword.php?ID=" . $accountId);
                } else {
                    throw new BadRequestHttpException("Seems like it is outdated program. Don't know account id for it - can't request password share");
                }
            }

            return new RedirectResponse("/manager/passwordVault/get.php?ID=" . $pv['PasswordVaultID']);
        }

        if (isset($pass) && (isset($login) || isset($accountId))) {
            $values = $request->query->all();
            $values['ShowPasswords'] = '1';

            if (!preg_match("/\*\*PASSWORD\*\*/ims", $s) && !preg_match("/^\<br\s?\/?\>$/", trim($pass))) {
                $s = str_ireplace($pass, "<a class='pass' href=\"?" . ImplodeAssoc("=", "&", $values, true) . "\">****</a>", $s);
            }
        }

        if (\preg_match('/\.(txt)$/ims', $name)) {
            $s = "<pre>{$s}</pre>";
        }

        $result->setContent($s);

        return $result;
    }

    private function prettyDate($date)
    {
        return str_replace(["T", "+00:00", ".000Z"], [" ", "", ""], $date);
    }

    /*
     * $method = 'autologin' | 'check'
     */
    private function getExtensionAccountLogsFromS3($accountId, $method)
    {
        $iterator = $this->s3Client->getIterator('ListObjects', ['Bucket' => 'awardwallet-logs', 'Prefix' => "account-{$accountId}-{$method}-"]);

        $result = [];

        foreach ($iterator as $object) {
            $result[] = $object;
        }

        return $result;
    }

    private function formatLogFile(string $s, string $format, bool $noScripts, ?string $url): string
    {
        // TBaseBrowser format
        $s = preg_replace_callback("/(GET|POST):\s*([^<]+)<br>(.+?)\s*saved (step\d\d\.html)/ims", [$this, "formatLogFileCallback"], $s);
        // HttpBrowser format
        $s = preg_replace_callback("/saved ([^<]+)<!\-\- url:([^ ]*) \-\->/ims", [$this, "formatHttpLogFileCallback"], $s);

        $s = preg_replace_callback("/(\b)(\d{10})(,)?([\s\n<]+)/", function ($match) {
            if (((string) (int) $match[2] === $match[2]) && strpos($match[2], '1') === 0) {
                return $match[1] . $match[2] . $match[3] . ' <span style="color: gray;">// ' . date('H:i d M Y', $match[2]) . '</span>' . $match[4];
            } else {
                return $match[1] . $match[2] . $match[3] . $match[4];
            }
        }, $s);

        if ($url !== null) {
            $s = preg_replace_callback('#src="aw-frame-saved://([^/]+)/([^"]+)"#ims', function (array $matches) use ($url) {
                global $zipIndexes;
                $zipIndex = $zipIndexes[$matches[1]];

                return 'src="' . $url . '?' . http_build_query(["Format" => "html", "Index" => $zipIndex, "pageURL" => $matches[2], "frame" => "yes"]) . '"';
            }, $s);
        }

        if ($noScripts) {
            $s = preg_replace('/window\.location\.href\s*=\s*["\'][^"\']*NoCookie[^"\']*["\']/ims', 'nocookie = "nocookie cut by awardwallet"', $s);
            $s = preg_replace('/if\s*\([^;]*NoCookie[^;]*;/ims', 'nocookie = "nocookie cut by awardwallet";', $s);
            $s = preg_replace('/document.location.href\s*=\s*[\'"].*cancel.*[\'"]/ims', 'nocookie = "nocookie cut by awardwallet";', $s);
            //		$script = ''.'<script type="text/javascript">
            //			function AwardWalletPreventReload() { return "### AwardWallet Log File ###\nPage want to redirect you! Or you want to close it."; }
            //			window.onbeforeunload = AwardWalletPreventReload;
            //			window.onunload = AwardWalletPreventReload;
            //		</script>';
            //		$s = $script . $s;
        }

        if ($format === 'source') {
            $s = mb_convert_encoding($s, "utf-8", "utf-8"); // some logs were converted to empty string in htmlspecialchars below withput this
            $s = "<pre><code>" . htmlspecialchars($s) . "</code></pre>";
        }

        if ($format === 'image') {
            $s = '<img style="width: 100%" src="data:image/png;base64,' . base64_encode($s) . '">';
        }

        return $s;
    }

    private function formatLogLinks($baseUrl, $file)
    {
        global $zipIndexes;

        $pageUrl = '&pageURL=' . urlencode($baseUrl);
        $zipIndex = $zipIndexes[$file];

        if (preg_match('/\.html$/ims', $file)) {
            // html
            // open log pages on about:blank, to prevent Referer / Origin leakage
            $s = "<a target='_blank' href=\"?Index={$zipIndex}&NoScript=1&Format=html$pageUrl\" class=\"open-log-link\">$file</a>";

            //            // no script
            //            $s .= " (<a href=\"?Index={$zipIndex}&NoScript=1$pageUrl\" class=\"open-log-link\">no scripts</a>)";
            //
            // screenshot
            $screenshotFilename = str_replace('.html', '-screenshot.png', $file);
            $screenshotIndex = ArrayVal($zipIndexes, $screenshotFilename);

            if ($screenshotIndex) {
                $s .= " (<a href=\"?Index=$screenshotIndex&Format=image\">screenshot</a>)";
            }

            // parsed
            $parsedDOMFilename = str_replace('.html', '-parsed.html', $file);
            $parsedDOMIndex = ArrayVal($zipIndexes, $parsedDOMFilename);

            if ($parsedDOMIndex) {
                $s .= " (<a href=\"?Index=$parsedDOMIndex&Format=source\">XPath's DOM</a>)";
            }

            // source
            $s .= " (<a target='_blank' href=\"?Index={$zipIndex}&Format=source$pageUrl\">source</a>)";
        } else {
            // source
            $s = " (<a href=\"?Index={$zipIndex}&Format=source$pageUrl\">$file</a>)";
        }

        return $s;
    }

    private function formatLogFileCallback($match)
    {
        $link = $this->formatLogLinks($match[2], $match[4]);

        return "{$match[1]}: {$match[2]}<br>
    {$match[3]}<br>
    saved $link";
    }

    private function formatHttpLogFileCallback($match)
    {
        return "saved " . $this->formatLogLinks($match[2], $match[1]);
    }

    private function downloadLog(string $cluster, string $filename, string $filePath): void
    {
        if ($cluster === 'extension') {
            $this->s3Client->getObject([
                'Bucket' => 'awardwallet-logs',
                'Key' => $filename,
                'SaveAs' => $filePath,
            ]);

            return;
        }

        // hotfixing s3 access errors
        if ($cluster === 'juicymiles') {
            $this->s3Client->getObject([
                'Bucket' => 'aw-loyalty-logs',
                'Key' => $filename,
                'SaveAs' => $filePath,
            ]);

            return;
        }

        /** @var ApiCommunicator $apiCommunicator */
        $apiCommunicator = $this->loyaltyApiCommunicators->get($cluster);
        file_put_contents($filePath, $apiCommunicator->GetLog($filename));
    }

    private function getFormat(string $fileExtension): string
    {
        $fileExtension = strtolower($fileExtension);

        if ($fileExtension === 'pdf') {
            return 'pdf';
        }

        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'image';
        }

        return 'html';
    }

    private function checkCusterAccess(string $cluster)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_MANAGE_LOGS') && !in_array($cluster, self::RA_CLUSTERS)) {
            throw new UserErrorException("You could not access logs from this cluster: $cluster");
        }
    }
}
