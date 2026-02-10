<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Email\ApiException;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Manager\EmailQueueWatcher;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

require_once __DIR__ . "/../../../../../web/lib/3dParty/plancake/PlancakeEmailParser.php";

/**
 * @Route("/manager/email/parser")
 * @Security("is_granted('ROLE_MANAGE_MANUALPARSER')")
 */
class ManualParserController extends AbstractController
{
    public const PAGE_SIZE = 50;
    private static $kinds = ['parse' => 1, 'scan' => 2];

    private $optionsCookieName = 'eq_options';
    private $queueOptions = [
        'jsonViewer' => [
            'name' => 'jv',
            'desc' => 'JSON viewer',
            'options' => ['0' => 'disabled', '1' => 'enabled'],
            'default' => '1',
        ],
        'providerList' => [
            'name' => 'pl',
            'desc' => 'Provider list',
            'options' => ['0' => 'By name', '1' => 'By code'],
            'default' => '0',
        ],
        'show' => [
            'name' => 'sh',
            'desc' => 'Show select',
            'options' => ['0' => 'Select one', '1' => 'Select checkbox'],
            'default' => '0',
        ],
        'providerReload' => [
            'name' => 'rProv',
            'desc' => 'Reload page on provider select',
            'options' => ['0' => 'Off', '1' => 'On'],
            'default' => '0',
        ],
        'partnerReload' => [
            'name' => 'rPar',
            'desc' => 'Reload page on partner select',
            'options' => ['0' => 'Off', '1' => 'On'],
            'default' => '0',
        ],
        'order' => [
            'name' => 'order',
            'desc' => 'Colum order',
            'options' => ['dsftptpdu' => 'Date first', 'sftdptpdu' => 'Subject first'],
            'default' => 'dsftptpdu',
        ],
    ];

    private Api $emailApi;
    private SessionInterface $session;
    private \Memcached $memcached;
    private Connection $connection;
    private $emailApiConfig;

    public function __construct(
        Api $emailApi,
        SessionInterface $session,
        \Memcached $memcached,
        Connection $connection,
        $emailApiConfig
    ) {
        $this->emailApi = $emailApi;
        $this->session = $session;
        $this->memcached = $memcached;
        $this->connection = $connection;
        $this->emailApiConfig = $emailApiConfig;
    }

    /**
     * @Route("/parse/{id}", name="aw_manager_manualparser_parse", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@AwardWalletMain/Manager/ManualParser/parse.html.twig")
     * @return array
     * @throws \Exception
     */
    public function parseAction(Request $request, $id)
    {
        $message = $this->getMessage($id, $this->getRegion($request));

        if (empty($message) || !is_array($message)) {
            throw $this->createNotFoundException("Message not found");
        }
        $parser = new \PlancakeEmailParser($message['email']);
        $trip_categories = [
            ["Code" => TRIP_CATEGORY_AIR, "Name" => 'Air trip'],
            ["Code" => TRIP_CATEGORY_CRUISE, "Name" => 'Cruise'],
            ["Code" => TRIP_CATEGORY_BUS, "Name" => 'Bus'],
            ["Code" => TRIP_CATEGORY_TRAIN, "Name" => 'Train'],
            ["Code" => TRIP_CATEGORY_FERRY, "Name" => 'Ferry'],
        ];

        if (!isset($message['kind'])) {
            $message['kind'] = 1;
        }
        $email = [
            'id' => $message['id'],
            'from' => $parser->getHeader('from'),
            'to' => $parser->getHeader('to'),
            'subject' => $parser->getSubject(),
            'date' => date("m/d/Y H:i:s e", strtotime($parser->getHeader('date'))),
            'kind' => $message['kind'],
        ];
        $attachments = [];

        for ($i = 0; $i < $parser->countAttachments(); $i++) {
            $type = $parser->getAttachmentHeader($i, 'Content-type');
            $disp = $parser->getAttachmentHeader($i, 'Content-Disposition');

            if (preg_match('/\/pdf\b|name=.+\.pdf\b/i', $type) > 0 || preg_match('/\/pdf\b|name=.+\.pdf\b/i', $disp) > 0) {
                $attachments[] = ['index' => $i, 'name' => $type, 'enabled' => true];
            } else {
                $attachments[] = ['index' => $i, 'name' => $type, 'enabled' => false];
            }
        }

        if (strlen($message['email']) < 5 * 1024 * 1024) {
            $email['body'] = $message['email'];
        }

        if (!empty($message["providerId"])) {
            $provider = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($message["providerId"]);
        }

        if (!empty($provider)) {
            $email['providerCode'] = $provider->getCode();
        }

        return [
            "codes" => $this->getProviderCodes('0'),
            'categories' => $trip_categories,
            'email' => $email,
            'attachments' => $attachments,
            'unknownCode' => TRIP_CODE_UNKNOWN,
            'unknownNumber' => CONFNO_UNKNOWN,
            'currency' => array_merge([["Name" => "", "Code" => ""]], $this->getDoctrine()->getConnection()->executeQuery('select concat(Name, " (", Sign, ")") as Name, Code from Currency where Code is not null')->fetchAll(\PDO::FETCH_ASSOC)),
            'region' => $this->getRegion($request),
        ];
    }

    /**
     * @Route("/date", name="aw_manager_manualparser_date", methods={"POST"})
     * @return JsonResponse
     */
    public function dateAction(Request $request)
    {
        $date = strtotime($request->request->get('date', ''));

        if ($date && $date > strtotime('01/01/1990')) {
            $response = [
                'result' => 'success',
                'date' => date('d F Y H:i', $date),
                'unix' => $date,
            ];

            if ($date < time()) {
                $response['result'] = 'warning';
                $response['date'] .= ' (in past)';
            }
        } else {
            $response = ['result' => 'fail'];
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/properties", name="aw_manager_manualparser_properties", methods={"POST"})
     * @return JsonResponse
     */
    public function propertiesAction(Request $request)
    {
        $code = $request->request->get('code', "");
        $properties = $this->getDoctrine()->getConnection()->executeQuery("select pp.Code, pp.Name from ProviderProperty pp join Provider p on pp.ProviderID = p.ProviderID where p.Code = ?", [$code], [\PDO::PARAM_STR])->fetchAll(\PDO::FETCH_ASSOC);

        return new JsonResponse($properties);
    }

    /**
     * @Route("/reject/{id}", name="aw_manager_manualparser_reject", methods={"POST"}, requirements={"id" = "\d+"})
     * @Security("is_granted('CSRF')")
     * @return Response
     * @throws ApiException
     */
    public function rejectAction($id, Request $request)
    {
        $this->emailApi->call("admin/manualParser/rejectMessage/$id", true, null, ["userId" => $this->getUser()->getUserid(), 'junk' => $request->query->get('junk', 'true')]);

        return new Response("Message was marked as junk");
    }

    /**
     * @Route("/submit/{id}", name="aw_manager_manualparser_submit", methods={"POST"}, requirements={"id" = "\d+"})
     * @Security("is_granted('CSRF')")
     * @return Response
     */
    public function submitAction($id, Request $request)
    {
        try {
            $this->emailApi->call("admin/manualParser/submitMessage/$id", true, $request->getContent(), ["userId" => $this->getUser()->getUserid()], true, $this->getRegion($request));

            return new Response("Message was processed");
        } catch (ApiException $e) {
            return new Response($e->getMessage());
        }
    }

    /**
     * @Route("/list/{kind}", name="aw_manager_manualparser_list", requirements={"kind" = "parse|scan|all"}, defaults={"kind" = "parse"})
     * @Template("@AwardWalletMain/Manager/ManualParser/list.html.twig")
     * @return Response
     */
    public function listAction(Request $request, RouterInterface $router, $kind)
    {
        $params = [];

        foreach (['subject', 'to', 'from', 'providerId', 'date', 'show', 'id', 'sort',
            'direction', 'partner', 'userData', 'mailboxLogin',
            'subjectAdv', 'toAdv', 'fromAdv', 'providerAdv', 'partnerAdv', 'userDataAdv',
            'method'] as $filter) {
            $params[$filter] = $request->query->get($filter, '');
        }

        if (is_array($params['show'])) {
            $params['show'] = implode(',', $params['show']);
        }
        $regions = array_keys($this->emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        if ($requestId = $request->query->get('requestId', '')) {
            return $this->searchRequestId($request, $region, $requestId);
        }

        if ($request->getMethod() == 'POST') {
            if ($request->request->get('requestId', '')) {
                return $this->searchRequestId($request, $region, $request->request->get('requestId'));
            }
            $post = $request->request->all();
            $action = $request->get('actionMany', '');

            if (in_array($action, ['reject', 'parse', 'skip', 'timeout', 'restrict', 'sendStaff'])) {
                $ids = [];

                foreach ($post as $name => $value) {
                    if (preg_match('/^sel(\d+)$/', $name, $m)) {
                        $ids[] = $m[1];
                    }
                }
                $selected = $request->request->get('selected_all', '');

                if (!empty($selected)) {
                    $query = $params;
                    unset($query['sort']);
                    unset($query['direction']);
                } else {
                    $query = [];
                }

                if (array_key_exists($kind, self::$kinds)) {
                    $query["kind"] = self::$kinds[$kind];
                }

                switch ($action) {
                    case "reject":
                    case "skip":
                        try {
                            if ($request->request->get('auto', 0) && count($ids) == 1) {
                                $id = $ids[0];
                                // $flash = "New filter was created";
                                $this->emailApi->call("admin/manualParser/rejectSubject/$id", true, [], ["userId" => $this->getUser()->getUserid(), 'action' => $action], false, $region);
                            } else {
                                if (empty($selected)) {
                                    $this->emailApi->call("admin/manualParser/rejectMessages", true, $ids, ["userId" => $this->getUser()->getUserid(), "list" => "request", 'action' => $action], false, $region);
                                } else {
                                    $query["userId"] = $this->getUser()->getUserid();
                                    $query["list"] = "query";
                                    $query["action"] = $action;
                                    $this->emailApi->call("admin/manualParser/rejectMessages", true, [], $query, false, $region);
                                }
                                // $flash = "Messages were marked as junked";
                            }
                        } catch (ApiException $e) {
                            // $flash = $e->getMessage();
                        }

                        break;

                    case "timeout":
                        try {
                            if (empty($selected)) {
                                $this->emailApi->call("admin/manualParser/timeoutMessages", true, $ids, ["userId" => $this->getUser()->getUserid(), "list" => "request"], false, $region);
                            } else {
                                $query["userId"] = $this->getUser()->getUserid();
                                $query["list"] = "query";
                                $this->emailApi->call("admin/manualParser/timeoutMessages", true, [], $query, false, $region);
                            }
                            // $flash = 'OK';
                        } catch (ApiException $e) {
                            // $flash = $e->getMessage();
                        }

                        break;

                    case "restrict":
                        try {
                            if (empty($selected)) {
                                $this->emailApi->call("admin/manualParser/restrictMessages", true, $ids, ["userId" => $this->getUser()->getUserid(), "list" => "request"], false, $region);
                            } else {
                                $query["userId"] = $this->getUser()->getUserid();
                                $query["list"] = "query";
                                $this->emailApi->call("admin/manualParser/restrictMessages", true, [], $query, false, $region);
                            }
                            // $flash = 'OK';
                        } catch (ApiException $e) {
                            // $flash = $e->getMessage();
                        }

                        break;

                    case "parse":
                        $query["userId"] = $this->getUser()->getUserid();

                        if (empty($selected)) {
                            $query['list'] = 'request';
                        } else {
                            $ids = [];
                            $query["list"] = "query";
                        }

                        try {
                            $result = $this->emailApi->call('admin/manualParser/runParse', true, $ids, $query, false, $region);

                            if (isset($result['message'])) {
                                $flash = $result['message'];
                            }
                        } catch (ApiException $e) {
                            $flash = $e->getMessage();
                        }

                        break;

                    case "sendStaff":
                        /** @var Useragent $ua */
                        $ua = $this->getUser()->findFamilyMemberByAlias($request->request->get('member', ''));
                        $query['userData'] = json_encode([
                            'user' => $this->getUser()->getUserid(),
                            'userAgent' => $ua ? $ua->getId() : null,
                            'email' => 'sentmanually@aw.fake',
                        ]);
                        $query["userId"] = $this->getUser()->getUserid();
                        $query['list'] = 'request';
                        $query['email'] = 'sentmanually@aw.fake';

                        try {
                            $this->emailApi->call('admin/manualParser/sendStaff', true, $ids, $query, false, $region);
                            // $flash = "Messages were sent to staff";
                        } catch (ApiException $e) {
                            // $flash = $e->getMessage();
                        }

                        break;
                }
            }

            if (isset($flash)) {
                $this->session->getFlashBag()->add('notice', $flash);
            }

            return new RedirectResponse($router->generate('aw_manager_manualparser_redirect', ['url' => urlencode($request->getUri())]));
        }
        $page = intval($request->query->get('page', 1));
        $params['limit'] = self::PAGE_SIZE;
        $params['offset'] = ($page - 1) * self::PAGE_SIZE;

        if (array_key_exists($kind, self::$kinds)) {
            $params['kind'] = self::$kinds[$kind];
        }
        $params['preview'] = $request->query->get('preview', '');
        $aaPartners = [];

        try {
            if ($this->checkParams($params)) {
                $result = $this->emailApi->call("admin/manualParser/list", false, null, $params, false, $region);

                if (isset($result['list'])) {
                    $emails = $result['list'];
                    $meta = $result['meta'];
                    $partners = $result['partners'];
                    $aaPartners = $result['aaPartners'];
                } else {
                    $emails = $result;
                    $meta = null;
                    $partners = [];
                }
            } else {
                $emails = [];
                $flash = "Invalid filters";
                $meta = null;
                $partners = [];
            }
        } catch (ApiException $e) {
            $emails = [];
            $flash = $e->getMessage();
            $meta = null;
            $partners = [];
        }

        if (isset($flash)) {
            $this->session->getFlashBag()->add('notice', $flash);
        }
        $list = [];
        $providersCache = [];
        $isAa = false;

        if (is_array($emails)) {
            if (count($emails) == 0 && $request->getMethod() == 'POST') {
                return $this->redirect($router->generate("aw_manager_manualparser_list"));
            }

            foreach ($emails as $email) {
                if (!empty($email['providerId'])) {
                    if (isset($providersCache[$email['providerId']])) {
                        $email['providerName'] = $providersCache[$email['providerId']];
                    } elseif ($provider = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($email['providerId'])) {
                        $providersCache[$email['providerId']] = $email['providerName'] = $provider->getShortname() . " (" . $provider->getCode() . ")";
                    }

                    if ($email['providerId'] == 1) {
                        $isAa = true;
                    }
                }

                if (!isset($email['providerName'])) {
                    $email['providerName'] = "";
                }
                $datetime = new \DateTime($email['createDate']);
                $email['createDateUtc'] = $datetime->format("Y-m-d H:i:s");
                $email['createDate'] = $datetime->format("Y-m-d H:i:s");
                $datetime->setTimestamp(strtotime($email['processDate']));
                $email['processDate'] = $datetime->format("Y-m-d H:i:s");

                if (isset($email['userId']) && $email['userId'] !== "0") {
                    if ($user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($email['userId'])) {
                        $email['userId'] = $user->getLogin();
                    }
                } else {
                    $email['userId'] = "0";
                }

                foreach (['parsedJson', 'parsedData'] as $jsonField) {
                    if (empty($email[$jsonField])) {
                        $email[$jsonField] = "";
                    } else {
                        $email[$jsonField] = json_encode(json_decode($email[$jsonField]), JSON_PRETTY_PRINT);
                    }
                }
                $email['allowDelete'] = $request->query->get('allowdelete', '') === '1' && in_array($email['partner'], ['debugemail', 'testemail']);
                $list[] = $email;
            }
        }

        $url = trim(preg_replace("/page=\d+\&?/", "", $request->getRequestUri()), "?&");

        if (stripos($url, "?") === false) {
            $url .= "?";
        } else {
            $url .= "&";
        }
        $pages = [
            "first" => $url . "page=1",
            "prev" => $url . "page=" . ($page - 1),
            "current" => $page,
            "next" => $url . "page=" . ($page + 1),
        ];

        if (count($list) < self::PAGE_SIZE) {
            unset($pages['next']);
        }

        if ($page <= 2) {
            unset($pages['first']);
        }

        if ($page == 1) {
            unset($pages['prev']);
        }
        $adv = false;

        foreach ($params as $key => $value) {
            if (strpos($key, 'Adv') !== false && strlen(trim($value)) > 0) {
                $adv = true;

                break;
            }
        }
        $time = time();
        $start = strtotime('02:00');
        $indexing = $this->memcached->get('awe_index');

        if ($options = $request->cookies->get($this->optionsCookieName)) {
            $options = json_decode($options, true);
        } else {
            $options = [];
        }

        foreach ($this->queueOptions as $option) {
            if (!isset($options[$option['name']])) {
                $options[$option['name']] = $option['default'];
            }
        }

        if (is_string($params['show'])) {
            $params['show'] = explode(',', $params['show']);
        }
        $params['show'] = array_filter($params['show']);

        if (empty($params['show'])) {
            $params['show'][] = 'all';
        }

        return $this->render("@AwardWalletMain/Manager\ManualParser/list.html.twig", [
            "list" => $list,
            "meta" => $meta,
            "providers" => $this->getProviderCodes($options['pl']),
            "partners" => $partners,
            'inputValues' => $params,
            'pages' => $pages,
            'kind' => $kind,
            'preview' => $request->query->get('preview') === 'true',
            'notice' => $indexing ? 'now' : (($time > $start - 10 * 60 && $time < $start) ? 'soon' : 'none'),
            'advanced' => $adv,
            'region' => $region,
            'regions' => $regions,
            'options' => $options,
            'optionList' => $this->queueOptions,
            'aaPartners' => $isAa ? $aaPartners : [],
            // keep synced with EmailBundle\Lib\MessageState
            'states' => [
                1 => 'new',
                2 => 'parsed',
                3 => 'rejected',
                4 => 'process',
                5 => 'restricted',
                6 => 'skipped',
                7 => 'timeout',
            ],
        ]);
    }

    /**
     * @Route("/emailFrame/{id}", name="aw_manager_manualparser_emailframe", requirements={"id" = "\d+"})
     * @return Response
     */
    public function emailFrameAction(Request $request, $id)
    {
        $message = $this->getMessage($id, $this->getRegion($request));
        $parser = new \PlancakeEmailParser($message['email']);

        if ($request->query->has('a')) {
            $a = $request->query->get('a');

            if (!is_numeric($a) || $a >= $parser->countAttachments()) {
                return new Response('error');
            }
            $response = new Response();

            if ($request->query->get('t')) {
                $type = $request->query->get('t');

                if ($type !== 'true') {
                    return new Response('error');
                }
                $message = $this->getMessageAttach($id, $a, $this->getRegion($request));
                $response->setContent("<pre>" . $message['attach'] . "</pre>");
            } elseif ($request->query->get('i')) {
                $type = $request->query->get('i');

                if ($type !== 'true') {
                    return new Response('error');
                }
                $response->headers->add(['Content-type' => 'image/jpeg']);
                $response->setContent($parser->getAttachmentBody($a));
            } else {
                $response->headers->add(['Content-type' => 'application/pdf']);
                $response->setContent($parser->getAttachmentBody($a));
            }

            return $response;
        } elseif ($request->query->has('s')) {
            $source = $request->query->get('s');

            if ($source !== 'true') {
                return new Response('error');
            }
            $response = new Response();
            $response->headers->add(['Content-type' => 'text/plain']);
            $response->setContent($message['email']);

            return $response;
        } else {
            $body = trim($parser->getHTMLBody());
            // todo: fix plancake
            $plain = false;

            if (empty($body) || stripos($body, 'Content-Type: text/plain;') !== false) {
                $plain = true;
                $body = $parser->getPlainBody();
            }

            if (!$plain && preg_match("/text\/plain/", $parser->getHeader('content-type'))) {
                $plain = true;
            }

            if ($plain) {
                $body = "<pre>" . $body . "</pre>";
            }

            return new Response($body);
        }
    }

    /**
     * @Route("/emailAttachmentInfo/{id}", name="aw_manager_manualparser_emailattachmentinfo", requirements={"id" = "\d+"})
     * @return JsonResponse
     */
    public function emailAttachmentInfoAction(Request $request, $id)
    {
        $message = $this->getMessage($id, $this->getRegion($request));
        $parser = new \PlancakeEmailParser($message['email']);
        $attachments = [];

        for ($i = 0; $i < $parser->countAttachments(); $i++) {
            $type = $parser->getAttachmentHeader($i, 'Content-type');
            $disp = $parser->getAttachmentHeader($i, 'Content-Disposition');

            if (preg_match('/\/pdf\b|name=.+\.pdf\b/i', $type) > 0 || preg_match('/\/pdf\b|name=.+\.pdf\b/i', $disp) > 0) {
                $attachments[] = ['index' => $i, 'name' => $type, 'enabled' => true];
                $attachments[] = ['index' => $i, 'name' => '&nbsp;&nbsp;&nbsp;[text]:&nbsp;' . $type, 'enabled' => true, 'text' => true];
            } elseif (preg_match('/\/(?:jpeg|png|jpg|gif)\b|name=.+\.(?:jpeg|png|jpg|gif)\b/i', $type) > 0 || preg_match('/\/(?:jpeg|png|jpg|gif)\b|name=.+\.(?:jpeg|png|jpg|gif)\b/i', $disp) > 0) {
                $attachments[] = ['index' => $i, 'name' => $type, 'enabled' => true, 'image' => true];
            } else {
                $attachments[] = ['index' => $i, 'name' => $type, 'enabled' => false];
            }
        }

        if ($message['parser'] === "mlparser") {
            $attachments[] = ['ml' => true];
        }

        return new JsonResponse($attachments);
    }

    /**
     * @Route("/getEmail/{id}", name="aw_manager_manualparser_getemail", requirements={"id" = "\d+"})
     * @return Response
     */
    public function getEmailAction(Request $request, $id)
    {
        $message = $this->getMessage($id, $this->getRegion($request));
        $response = new Response();
        $response->headers->add([
            'Content-type' => 'message/rfc822',
            'Content-Disposition' => "attachment; filename=it-{$id}.eml", ]);
        $response->setContent($message['email']);

        return $response;
    }

    /**
     * @Route("/filters", name="aw_manager_manualparser_filters")
     * @Template("@AwardWalletMain/Manager/ManualParser/filters.html.twig")
     * @return array
     * @throws ApiException
     */
    public function filtersAction(Request $request)
    {
        $regions = array_keys($this->emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        if ($request->getMethod() == 'POST') {
            $delete = intval($request->request->get('delete', 0));

            if ($delete) {
                $del = $this->emailApi->call("admin/manualParser/filters/delete/$delete", true, [], [], false, $region);
                $this->session->getFlashBag()->add('notice', $del);
            } else {
                $subject = trim($request->request->get('subject', ''));
                $from = trim($request->request->get('from', ''));
                $attach = $request->request->get('attach', 0);
                $attachType = $request->request->get('attachType', 0);
                $fail = false;

                if (empty($subject)) {
                    $subject = null;
                } elseif (strlen($subject) < 10) {
                    $fail = true;
                }

                if (empty($from)) {
                    $from = null;
                } elseif (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
                    $fail = true;
                }

                if (!$fail && (isset($from) || isset($subject))) {
                    try {
                        $add = $this->emailApi->call("admin/manualParser/filters/add", true, [
                            'subject' => $subject,
                            'from' => $from,
                            'attach' => $attach,
                            'attachType' => $attachType,
                        ], ["userId" => $this->getUser()->getUserid()], true, $region);
                    } catch (ApiException $e) {
                        $add = $e->getMessage();
                    }
                    $this->session->getFlashBag()->add('notice', $add);
                } else {
                    $this->session->getFlashBag()->add('notice', "Invalid filter");
                }
            }
        }
        [$list,$attaches,$attacheTypes] = $this->emailApi->call("admin/manualParser/filters", false, [], [], false, $region);

        if (!is_array($list)) {
            $list = [];
        }

        if (!is_array($attaches)) {
            $attaches = [];
        }

        if (!is_array($attacheTypes)) {
            $attacheTypes = [];
        }

        return [
            "list" => $list,
            "attaches" => $attaches,
            "attacheTypes" => $attacheTypes,
        ];
    }

    /**
     * @Route("/filters/check", name="aw_manager_manualparser_checkfilter")
     * @return Response
     * @throws ApiException
     */
    public function checkFiltersAction(Request $request)
    {
        $data = $this->emailApi->call("admin/manualParser/filters/check", strcasecmp($request->getMethod(), 'post') === 0);

        return $this->render('@AwardWalletMain/Manager/ManualParser/checkFilters.html.twig', $data);
    }

    /**
     * @Route("/report/{kind}", name="aw_manager_manualparser_report", requirements={"kind" = "parse|scan|all"}, defaults={"kind" = "parse"})
     * @Template("@AwardWalletMain/Manager/ManualParser/report.html.twig")
     * @return array
     */
    public function reportAction(Request $request, $kind)
    {
        $params = [];

        foreach (["group" => "subject", "results" => 200, "matches" => 10] as $key => $default) {
            $params[$key] = $request->query->get($key, $default);
        }

        if (array_key_exists($kind, self::$kinds)) {
            $params["kind"] = self::$kinds[$kind];
        }
        $regions = array_keys($this->emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        try {
            $list = $this->emailApi->call("admin/manualParser/report", false, [], $params, false, $region);
        } catch (ApiException $e) {
            $this->session->getFlashBag()->add('notice', $e->getMessage());
            $list = [];
        }

        return [
            "report" => $list,
            "params" => $params,
            "kind" => $kind,
            "region" => $region,
            "regions" => $regions,
        ];
    }

    /**
     * @Route("/delete/{id}", name="aw_manager_manualparser_delete", requirements={"id" = "\d+"})
     * @return Response
     */
    public function deleteAction(Request $request, $id)
    {
        $params = ['id' => $id, 'deleted' => false, 'backto' => $request->headers->get('referer', null), 'error' => null];

        if ($request->getMethod() === 'POST') {
            try {
                $params['deleted'] = true;
                $params['backto'] = $request->request->get('backto');
                $this->emailApi->call('admin/manualParser/deleteMessage/' . $id, true);
            } catch (ApiException $e) {
                $params['error'] = $e->getMessage();
            }
        }

        return $this->render("@AwardWalletMain/Manager/ManualParser/delete.html.twig", $params);
    }

    /**
     * @Route("/redirect/{url}", name="aw_manager_manualparser_redirect")
     * @return RedirectResponse
     */
    public function redirectAction($url)
    {
        return new RedirectResponse(urldecode($url));
    }

    /**
     * @Route("/switchPreview/{preview}", name="aw_manager_manualparser_switchpreview", requirements={"preview" = "true|false"})
     * @return RedirectResponse|Response
     */
    public function switchPreviewAction(Request $request, $preview)
    {
        $referer = $request->headers->get('referer');

        if (empty($referer)) {
            return new Response("", 400);
        }
        $referer = preg_replace('/&?preview=[^&]+/', '', $referer);

        if (strpos($referer, '?') === false) {
            $referer .= '?';
        } else {
            $referer .= '&';
        }
        $referer .= "preview=" . $preview;

        return new RedirectResponse($referer);
    }

    /**
     * @Route("/switchRegion", name="aw_manager_manualparser_switchregion")
     * @return RedirectResponse|Response
     */
    public function switchRegion(Request $request)
    {
        $referer = $request->headers->get('referer');

        if (empty($referer)) {
            return new Response("", 400);
        }
        $arr = parse_url($referer);

        if (!$arr) {
            return new Response("", 400);
        }
        parse_str($arr['query'], $query);
        $url = strtok($referer, '?');
        $url .= '?show=all&region=' . $request->query->get('r');

        if (!empty($query['preview'])) {
            $url .= '&preview=' . $query['preview'];
        }

        return new RedirectResponse($url);
    }

    /**
     * @Route("/metric", name="aw_manager_manualparser_metric")
     * @return JsonResponse
     */
    public function metricAction(EmailQueueWatcher $queueWatcher)
    {
        $data = $queueWatcher->getStat12hQuaterly();
        $graph = [];

        if (!empty($data['Parse']['Datapoints']) && !empty($data['Callbacks']['Datapoints'])) {
            foreach (['Parse', 'Callbacks'] as $name) {
                foreach ($data[$name]['Datapoints'] as $point) {
                    if (!isset($graph[$point['Timestamp']])) {
                        $graph[$point['Timestamp']] = [substr($point['Timestamp'], 11, 8)];
                    }
                    $graph[$point['Timestamp']][] = floatval($point['Average']);
                }
            }
            $graph = array_values(array_filter($graph, function ($a) {return count($a) == 3; }));
            $data['Graph'] = array_merge([['Time', 'Parse', 'Callbacks']], $graph);
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/options", name="aw_manager_manualparser_options")
     * @return JsonResponse
     */
    public function optionsAction(Request $request)
    {
        $response = new JsonResponse(['status' => 'success']);
        $cookie = [];

        foreach ($this->queueOptions as $option) {
            if (!is_null($val = $request->request->get($name = $option['name'])) && array_key_exists($val, $option['options'])) {
                $cookie[$name] = $val;
            }
        }
        $response->headers->setCookie(AwCookieFactory::createLax($this->optionsCookieName, json_encode($cookie)));

        return $response;
    }

    protected function searchRequestId(Request $request, $region, $requestId)
    {
        $requestId = trim($requestId);

        if (preg_match('/^[a-z\d]{32}$/', $requestId) === 0) {
            $this->session->getFlashBag()->add('notice', 'invalid requestId');

            return $this->redirect($this->generateUrl('aw_manager_manualparser_list', ['kind' => 'all', 'show' => 'all', 'region' => $region]));
        }
        $message = $this->emailApi->call("admin/manualParser/requestId/$requestId", false, [], [], false, $region);

        if (isset($message['found']) && $message['found'] && isset($message['id'])) {
            return $this->redirect($this->generateUrl('aw_manager_manualparser_list', ['kind' => 'all', 'id' => $message['id'], 'show' => 'all', 'region' => $region]));
        } else {
            $this->session->getFlashBag()->add('notice', 'requestId not found');

            return $this->redirect($this->generateUrl('aw_manager_manualparser_list', ['kind' => 'all', 'show' => 'all', 'region' => $region]));
        }
    }

    protected function checkParams(&$params)
    {
        $params["id"] = trim(str_ireplace("\t", " ", $params["id"]));

        if (!preg_match("/^[\d\,\s]*$/", $params["id"])) {
            return false;
        }

        return true;
    }

    protected function getProviderCodes($kind)
    {
        $d = [
            '0' => ['concat(ShortName, " (", Code, ")") as Name', 'ShortName'],
            '1' => ['concat(Code, " (", ShortName, ")") as Name', 'Code'],
        ];

        return $this->connection->executeQuery(sprintf('select ProviderID as ID, Code, %s, ShortName from Provider order by %s', $d[$kind][0], $d[$kind][1]))->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getMessage($id, $region)
    {
        $message = $this->memcached->get('queuedMessage_' . $id);

        if (!$message) {
            $message = $this->emailApi->call("admin/manualParser/message/$id", false, [], [], false, $region);
            $message["email"] = base64_decode($message["email"]);
            $this->memcached->set('queuedMessage_' . $id, $message, 60 * 5);
        }

        return $message;
    }

    protected function getMessageAttach($id, $num, $region)
    {
        $message = $this->memcached->get('queuedMessage_' . $id . '_' . $num);

        if (!$message) {
            $message = $this->emailApi->call("admin/manualParser/message/{$id}?a={$num}", false, [], [], false, $region);
            $message["attach"] = base64_decode($message["attach"]);
            $this->memcached->set('queuedMessage_' . $id . '_' . $num, $message, 60 * 5);
        }

        return $message;
    }

    protected function getRegion(Request $request)
    {
        $regions = array_keys($this->emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        return $region;
    }
}
