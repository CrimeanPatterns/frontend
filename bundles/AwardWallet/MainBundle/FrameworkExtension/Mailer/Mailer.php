<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\DoNotSendException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailWasNotSentException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\NonDeliveryException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\SendTracker;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\SecureLink;
use AwardWallet\Strings\Strings;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\Message as TranslationMessage;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class Mailer implements TranslationContainerInterface
{
    public const HEADER_KIND = "X-AW-KIND";
    public const HEADER_CATEGORY = "X-AW-CATEGORY";

    public const OPTION_TRANSACTIONAL = 'transactional';
    public const OPTION_DIRECT = 'direct';  // allow direct email delivery, to the recipient smtp server, skipping relays (sparkpost)
    public const OPTION_RELAY = 'relay';
    public const OPTION_SKIP_DONOTSEND = 'skip_donotsend';
    public const OPTION_SEND_ATTEMPTS = 'attempts_send';
    public const OPTION_SEPARATE_CC = 'separate_cc';
    public const OPTION_ON_SUCCESSFUL_SEND = 'success_send';
    public const OPTION_ON_FAILED_SEND = 'fail_send';
    public const OPTION_FIX_BODY = 'fix_body';
    public const OPTION_SKIP_STAT = 'skip_stat';
    public const OPTION_TRANSPORT = 'transport';
    public const OPTION_EXTERNAL_CLICK_TRACKING = 'external_click_tracking';
    public const OPTION_EXTERNAL_OPEN_TRACKING = 'external_open_tracking';
    public const OPTION_THROW_ON_FAILURE = 'throw_on_failure';
    // delay between failed attempts in seconds, by default: 2^attempt, specified number will be raised to the power of attempt
    public const OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS = 'delay_between_failed_attempts';

    protected const TWIG_TEMPLATE_NAMESPACE = __NAMESPACE__ . '\\Template';

    /** @var \Symfony\Bridge\Monolog\Logger */
    protected $logger;

    /** @var \Symfony\Bridge\Monolog\Logger */
    protected $mailLogger;

    /** @var EntityManager */
    protected $em;

    /** @var \Twig_Environment */
    protected $twig;

    /**
     * @var SecureLink
     */
    protected $secureLink;

    protected $emailAddresses = [];

    protected $defaultOptions = [
        self::OPTION_SKIP_DONOTSEND => false,
        self::OPTION_SEND_ATTEMPTS => 3,
        self::OPTION_SEPARATE_CC => false,
        self::OPTION_ON_SUCCESSFUL_SEND => null,
        self::OPTION_ON_FAILED_SEND => null,
        self::OPTION_FIX_BODY => true,
        self::OPTION_SKIP_STAT => false,
        self::OPTION_TRANSPORT => null,
        self::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS => 2,
        /**
         * click-tracking conflicts with mobile route intercepts, so we enable it only for mass emails.
         */
        self::OPTION_EXTERNAL_CLICK_TRACKING => false,
        self::OPTION_EXTERNAL_OPEN_TRACKING => true,
        self::OPTION_THROW_ON_FAILURE => false,
        self::OPTION_DIRECT => true,
    ];

    protected $defaultLang;

    protected $hosts;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $failures = [];
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var RelaySelector
     */
    private $relaySelector;
    /**
     * @var SendTracker
     */
    private $sendTracker;

    public function __construct(
        EntityManager $em,
        \Twig_Environment $twig,
        LoggerInterface $logger,
        LoggerInterface $mailLogger,
        SecureLink $secureLink,
        $defaultLang,
        RelaySelector $relaySelector,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        SendTracker $sendTracker
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->twig = $twig;
        $this->mailLogger = $mailLogger;
        $this->secureLink = $secureLink;
        $this->defaultLang = $defaultLang;
        $this->relaySelector = $relaySelector;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->sendTracker = $sendTracker;
    }

    /**
     * Send message(s).
     *
     * @param Message[]|Message $messages
     * @param array $options - see OPTIONS_ constants
     * @return bool
     */
    public function send($messages, $options = [])
    {
        $this->logger->debug('preparation for sending emails');

        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $options = array_merge($this->defaultOptions, $options);

        if (!isset($options[self::OPTION_TRANSPORT])) {
            $options[self::OPTION_TRANSPORT] = $this->relaySelector->getTransportByOptions($options);
        }
        $this->logger->debug('sending options', ['options' => TraceProcessor::filterArguments($options)]);
        $reservedAddresses = [];

        foreach ($this->emailAddresses as $address) {
            if (is_array($address)) {
                $reservedAddresses[] = current($address);
            } else {
                $reservedAddresses[] = $address;
            }
        }

        $result = true;

        /** @var \Swift_Message $message */
        foreach ($messages as $message) {
            $to = $message->getTo();
            $to = key($to);
            $this->logger->debug("$to: preparation for sending");

            if (!$options[self::OPTION_SKIP_DONOTSEND] && !in_array(strtolower($to), $reservedAddresses)) {
                if (!$this->checkNDR($to)) {
                    $this->logger->warning("$to: detected ndr $to. Sending canceled");
                    $this->addFailure($to, 'email_failed.ndr');

                    if ($options[self::OPTION_THROW_ON_FAILURE]) {
                        throw new NonDeliveryException($message);
                    }

                    $result = false;

                    continue;
                }
            }

            if (!$options[self::OPTION_SKIP_DONOTSEND] && !in_array(strtolower($to), $reservedAddresses)) {
                if (!$this->checkDoNotSend($to)) {
                    $this->logger->warning("$to: found $to in DoNotSend table. Sending canceled");
                    $this->addFailure($to, 'email_failed.donotsend');

                    if ($options[self::OPTION_THROW_ON_FAILURE]) {
                        throw new DoNotSendException($message);
                    }

                    $result = false;

                    continue;
                }
            }

            $serviceHeaders = $this->extractServiceHeaders($message);

            if (isset($serviceHeaders[self::HEADER_KIND])) {
                $message->addContext(['kind' => $serviceHeaders[self::HEADER_KIND]]);
            }

            $tracking = $this->sendTracker->prepareTracking(
                $message,
                true,
                true
            );

            $messageResult = false;

            try {
                if ($options[self::OPTION_FIX_BODY]) {
                    $body = $message->getBody();
                    $body = preg_replace(
                        "/(((src|background|href)=['\"]|url\())\//ims",
                        '$1' . $this->hosts['personal'] . '/',
                        $body
                    );

                    if ($message->getContentType() != 'text/plain') {
                        $text = $this->convertHtmlToText($body);
                    } else {
                        $text = $body;
                    }
                    $text = str_replace("\xc2\xa0", " ", $text);
                    $message->setBody($text, 'text/plain');
                    $message->addPart($body, 'text/html');
                }

                $this->logger->debug("$to: Sending...");
                $messageResult = $this->sendMessage($message, $options);
            } finally {
                if ($tracking !== null && StringUtils::isNotEmpty($to)) {
                    $this->sendTracker->track($to, $tracking, $messageResult);
                }
            }

            if (!$messageResult && $options[self::OPTION_THROW_ON_FAILURE]) {
                throw new MailWasNotSentException($message);
            }

            $result = $result && $messageResult;
        }

        return $result;
    }

    public function getErrors()
    {
        return $this->failures;
    }

    /**
     * Record in db that specified email was sent. By default, called internally.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function recordStatEmail($kind)
    {
        $conn = $this->em->getConnection();
        $statement = $conn->prepare("
            INSERT INTO EmailStat(StatDate, Kind, Messages)
            VALUES(NOW(), :kind, 1)
            ON DUPLICATE KEY UPDATE Messages = Messages + 1
        ");
        $statement->bindParam(':kind', $kind, \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * @param string $kind
     */
    public function addKindHeader($kind, \Swift_Message $message)
    {
        $headers = $message->getHeaders();

        if ($headers->has(self::HEADER_KIND)) {
            $headers->removeAll(self::HEADER_KIND);
        }
        $headers->addTextHeader(self::HEADER_KIND, $kind);
    }

    /**
     * @param string $unsubscribeLink
     */
    public function addUnsubscribeHeader($unsubscribeLink, \Swift_Message $message)
    {
        $headers = $message->getHeaders();

        if ($headers->has('List-Unsubscribe')) {
            $headers->removeAll('List-Unsubscribe');
        }
        $headers->addTextHeader('List-Unsubscribe', $unsubscribeLink);
    }

    public function getUnsubscribeLink($email, $toBusiness = false)
    {
        $host = $toBusiness ? $this->hosts['business'] : $this->hosts['personal'];

        return $this->secureLink->protectUnsubscribeUrl($email, $host, $toBusiness);
    }

    /**
     * Set awardwallet email address (bcc, error, notifications, etc).
     *
     * @param array $addresses
     */
    public function setEmails($addresses)
    {
        $this->emailAddresses = $addresses;
    }

    /**
     * Get awardwallet email address (bcc, error, notifications, etc).
     *
     * @return string
     */
    public function getEmail($kind)
    {
        if (!isset($this->emailAddresses[$kind])) {
            throw new \InvalidArgumentException("Email address not found");
        }

        return $this->emailAddresses[$kind];
    }

    /**
     * @param array $hosts
     */
    public function setHosts($hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * Get swift message by template.
     *
     * @param array $extTemplateVars
     */
    public function getMessageByTemplate(AbstractTemplate $template, $extTemplateVars = []): Message
    {
        $message = $this->getMessage();
        $data = [];
        $user = null;

        if ($to = $template->getUser()) {
            if ($to instanceof Usr) {
                $user = $to;
            } elseif ($to instanceof Useragent) {
                $user = $to->getAgentid();
            }
            $message->addContext(["UserID" => $user->getUserid()]);
        }

        $data['personalHost'] = $this->hosts['personal'];
        $data['businessHost'] = $this->hosts['business'];
        $data['baseUrl'] = $template->isBusinessArea() ? $data['businessHost'] : $data['personalHost'];

        if (empty($this->hosts['cdn']) || empty($this->hosts['channel'])) {
            $data['baseImagesPath'] = $data['baseUrl'] . '/images/email/newdesign';
        } else {
            $data['baseImagesPath'] = $this->hosts['channel'] . $this->hosts['cdn'] . '/images/email/newdesign';
        }

        if ($template->isEnableUnsubscribe()) {
            $data['unsubscribeLink'] = $this->getUnsubscribeLink($template->getEmail(), $template->isBusinessUnsubscribe());
        }

        $templateVars = $template->getTemplateVars();
        $kind = call_user_func_array(get_class($template) . "::getEmailKind", []);

        if (!isset($templateVars['lang']) || empty($templateVars['lang'])) {
            $templateVars['lang'] = $user ? $user->getLanguage() : $this->defaultLang;
        }

        if (!isset($templateVars['locale']) || empty($templateVars['locale'])) {
            $templateVars['locale'] = $user ? $user->getLocale() : $this->defaultLang;
        }

        if (sizeof($disputableProps = array_intersect_key($data, $templateVars))) {
            throw new \LogicException(sprintf('An error in the email template "%s", conflicting keys: %s', $kind, implode(", ", array_keys($disputableProps))));
        }
        $data = array_merge($this->twig->mergeGlobals(array_merge($templateVars, $data)), $extTemplateVars);
        $twigTemplate = $this->getTwigTemplate($template);
        $subject = $twigTemplate->renderBlock('subject', $data);
        $htmlBody = $twigTemplate->hasBlock('body_html', $data) ?
            $twigTemplate->renderBlock('body_html', $data) :
            '';
        $textBody = $twigTemplate->hasBlock('body_text', $data) ?
            $twigTemplate->renderBlock('body_text', $data) :
            '';

        $message->setSubject($subject);
        $message->setTo($template->getEmail());
        $this->logWhenInvalidAddress($template->getEmail(), 'to', $message->getTo());

        if (isset($htmlBody) && !empty($htmlBody)) {
            $message->setBody($htmlBody, "text/html");
        } elseif (isset($textBody) && !empty($textBody)) {
            $message->setBody($textBody, "text/plain");
        } else {
            throw new \LogicException('Message body empty');
        }
        $this->addKindHeader($kind, $message);

        if (isset($data['unsubscribeLink'])) {
            $this->addUnsubscribeHeader($data['unsubscribeLink'], $message);
        }

        $message->addContext([
            'template' => $kind,
            'businessArea' => $template->isBusinessArea(),
            'lang' => $templateVars['lang'],
            'locale' => $templateVars['locale'],
            'enableUnsubscribe' => $template->isEnableUnsubscribe(),
        ]);

        return $message;
    }

    /**
     * @param string|null $kind
     * @param string|null $to
     * @param string|null $subject
     */
    public function getMessage($kind = null, $to = null, $subject = null): Message
    {
        $message = new Message();
        $message->setReturnPath($this->getEmail('ndr'));
        $message->setBcc($this->getEmail('bcc'));
        $message->setFrom($this->getEmail('from'));

        if (isset($kind)) {
            $this->addKindHeader($kind, $message);
        }

        if (isset($to)) {
            $message->setTo($to);
            $this->logWhenInvalidAddress($to, 'to', $message->getTo());
        }

        if (isset($subject)) {
            $message->setSubject($subject);
        }

        return $message;
    }

    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    public static function getTranslationMessages()
    {
        return [
            (new TranslationMessage('email_failed.ndr'))->setDesc("Unfortunately the user under this email address (%email%) requested that we don't send any emails to him or her (unsubscribed from all communications). For that reason we are not able to send this email."),
            (new TranslationMessage('email_failed.donotsend'))->setDesc("Unfortunately the user with this email address (%email%) is marked as non-deliverable in our database. For that reason we are not able to send this email."),
        ];
    }

    private function addFailure($email, $reason)
    {
        if (empty($this->failures)) {
            $this->eventDispatcher->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) {
                $event->getResponse()->headers->set('x-aw-mail-failed', json_encode($this->failures));
            });
        }
        /** @Ignore */
        $this->failures[] = $this->translator->trans($reason, ["%email%" => $email]);
    }

    /**
     * @return \Twig_TemplateWrapper
     */
    private function getTwigTemplate(AbstractTemplate $template)
    {
        $class = \get_class($template);

        if (\strpos($class, self::TWIG_TEMPLATE_NAMESPACE) !== 0) {
            throw new \LogicException('Template class and twig should be placed in ' . self::TWIG_TEMPLATE_NAMESPACE . ' namespace');
        }

        $template = '@MailTemplate' . \substr($class, \strlen(self::TWIG_TEMPLATE_NAMESPACE)) . '.twig';

        return $this->twig->load($template);
    }

    private function sendMessage(\Swift_Message $message, $options, $attempt = 1, $isCopy = false)
    {
        $to = $message->getTo();

        if (!$to) {
            $to = null;
            $this->logger->warning("attempt to send to empty address ($attempt), will not send.");

            return false;
        } else {
            $to = key($to);
            $this->logger->debug("$to: attempt to send ($attempt)");
        }

        if (!$isCopy && $options[self::OPTION_SEPARATE_CC]) {
            $cc = $message->getCc();
            $bcc = $message->getBcc();
            $message->setCc([]);
            $message->setBcc([]);
        }
        $result = false;
        $serviceHeaders = $this->extractServiceHeaders($message);
        $this->addTrackingHeaders($message, $serviceHeaders, $options);

        try {
            // subject validation
            $containsLineBreaks = fn (?string $subject): bool => !is_null($subject) && preg_match('/\\n/', $subject);

            if ($containsLineBreaks($message->getSubject())) {
                $message->setSubject(trim(preg_replace('/\\n/', ' ', $message->getSubject())));
                $message->setSubject(preg_replace('/\\s{2,}/', ' ', $message->getSubject()));

                if ($containsLineBreaks($message->getSubject())) {
                    throw new \Exception(sprintf('Subject "%s" contains line breaks', $message->getSubject()));
                }
            }

            $mailer = new \Swift_Mailer($options[self::OPTION_TRANSPORT]);
            $result = (bool) $mailer->send($message);

            if (!$isCopy) {
                if (isset($options[self::OPTION_ON_SUCCESSFUL_SEND])) {
                    call_user_func_array($options[self::OPTION_ON_SUCCESSFUL_SEND], [$this]);
                }

                if (!$options[self::OPTION_SKIP_STAT]) {
                    if (!isset($serviceHeaders[self::HEADER_KIND])) {
                        throw new \Exception(sprintf('Unknown type of e-mail "%s"', $message->getSubject()));
                    }
                    $this->recordStatEmail($serviceHeaders[self::HEADER_KIND]);
                }

                if (isset($cc) || isset($bcc)) {
                    $_cc = [];

                    if (isset($cc) && sizeof($cc)) {
                        $_cc = array_merge($_cc, array_keys($cc));
                    }

                    if (isset($bcc) && sizeof($bcc)) {
                        $_cc = array_merge($_cc, array_keys($bcc));
                    }

                    if (sizeof($_cc)) {
                        $ccMessage = clone $message;
                        $ccMessage->setTo([]);

                        foreach ($_cc as $mail) {
                            $ccMessage->addTo($mail);
                        }

                        $this->logWhenInvalidAddress($_cc, 'to', $ccMessage->getTo());
                        $this->logger->debug("sending copies of the email");
                        $this->sendMessage($ccMessage, array_merge($options, [
                            self::OPTION_TRANSPORT => $this->relaySelector->getTransportByOptions([]),
                        ]), 1, true);
                    }
                }
            }
        } catch (\Swift_TransportException $e) {
            if ($isCopy) {
                $this->logger->debug("$to: copy of the email was not sent");
            } else {
                if ($e->getCode() === 554 && stripos($e->getMessage(), 'no valid recipients') !== false) {
                    $this->logger->warning(sprintf(
                        '%s: no valid recipients (%s)',
                        $to, $e->getMessage()
                    ));

                    if (isset($options[self::OPTION_ON_FAILED_SEND])) {
                        call_user_func_array($options[self::OPTION_ON_FAILED_SEND], [$e]);
                    } else {
                        return false;
                    }
                } elseif ($attempt < $options[self::OPTION_SEND_ATTEMPTS]) {
                    $this->logger->warning(sprintf(
                        '%s: Failed attempt %d/%d (%s, code: %d)',
                        $to, $attempt, $options[self::OPTION_SEND_ATTEMPTS], $e->getMessage(), $e->getCode()
                    ));

                    if (isset($options['fail_attempt']) && is_callable($options['fail_attempt'])) {
                        call_user_func_array($options['fail_attempt'], [$e, $attempt]);
                    }

                    if (isset($cc)) {
                        $message->setCc($cc);
                        $this->logWhenInvalidAddress($cc, 'cc', $message->getCc());
                    }

                    if (isset($bcc)) {
                        $message->setBcc($bcc);
                        $this->logWhenInvalidAddress($bcc, 'bcc', $message->getBcc());
                    }
                    /** @var \Swift_Transport $transport */
                    $transport = $options[self::OPTION_TRANSPORT] ?? $this->relaySelector->getTransportByOptions([]);

                    try {
                        @$transport->stop(); // suppress fwrite errors in StreamBuffer
                    } catch (\Throwable $e) {
                        $this->logger->warning($e->getMessage());
                    }

                    if ($transport instanceof \Swift_Transport_AbstractSmtpTransport) {
                        $reflProp = new \ReflectionProperty(\Swift_Transport_AbstractSmtpTransport::class, 'pipeline');
                        $reflProp->setAccessible(true);
                        $pipeline = $reflProp->getValue($transport);
                        $this->logger->info('smtp pipeline transport info: ' . Strings::cutInMiddle(\json_encode($pipeline), 256));
                        $reflProp->setValue($transport, []);
                        $reflProp->setAccessible(false);

                        $reflProp = new \ReflectionProperty(\Swift_Transport_AbstractSmtpTransport::class, 'started');
                        $reflProp->setAccessible(true);
                        $reflProp->setValue($transport, false);
                        $reflProp->setAccessible(false);
                    }

                    sleep($options[self::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS] ** $attempt);

                    $result = $this->sendMessage($message, $options, $attempt + 1);
                } else {
                    $this->logger->critical("$to: Error sending email (" . $e->getMessage() . ")");

                    if (isset($options[self::OPTION_ON_FAILED_SEND])) {
                        call_user_func_array($options[self::OPTION_ON_FAILED_SEND], [$e]);
                    } else {
                        return false;
                    }
                }
            }
        }

        return $result;
    }

    private function addTrackingHeaders(\Swift_Message $message, array $serviceHeaders, array $options)
    {
        // sparkpost, https://github.com/SparkPost/sparkpost-api-documentation/blob/master/services/smtp_api.md
        $msys = [];

        if (isset($serviceHeaders[self::HEADER_KIND])) {
            // mandrill
            $message->getHeaders()->addTextHeader('X-MC-Tags', $serviceHeaders[self::HEADER_KIND]);

            $msys["campaign_id"] = substr($serviceHeaders[self::HEADER_KIND], 0, 64);
        }

        if (isset($serviceHeaders[self::HEADER_CATEGORY])) {
            $msys["metadata"] = ["category" => $serviceHeaders[self::HEADER_CATEGORY]];
        }

        $opt = [];

        if (!$options[self::OPTION_EXTERNAL_CLICK_TRACKING]) {
            $opt['click_tracking'] = false;
        }

        if (!$options[self::OPTION_EXTERNAL_OPEN_TRACKING]) {
            $opt['open_tracking'] = false;
        }

        $to = key($message->getTo());

        if (preg_match('#@yahoo\.com($|\.)#ims', $to)) {
            $opt['ip_pool'] = ($options[self::OPTION_TRANSACTIONAL] ?? true) ? "transactional" : "marketing";
        }

        if (!empty($opt)) {
            $msys['options'] = $opt;
        }

        if (!empty($msys)) {
            $message->getHeaders()->addTextHeader('X-MSYS-API', json_encode($msys));
        }
    }

    private function extractServiceHeaders(\Swift_Message $message)
    {
        $headers = $message->getHeaders();
        $result = [];

        foreach ([self::HEADER_KIND, self::HEADER_CATEGORY] as $header) {
            if ($headers->has($header)) {
                $result[$header] = $headers->get($header)->getFieldBodyModel();
            }
        }

        return $result;
    }

    private function checkNDR($email)
    {
        $conn = $this->em->getConnection();
        $sth = $conn->prepare("SELECT EmailVerified FROM Usr WHERE Email = ?");
        $sth->execute([$email]);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            if ($row['EmailVerified'] == EMAIL_NDR) {
                $this->mailLogger->info("address {email} marked as NDR, will not send message", ["email" => $email]);

                return false;
            }
        }

        return true;
    }

    private function checkDoNotSend($email)
    {
        $conn = $this->em->getConnection();
        $sth = $conn->prepare("SELECT * FROM DoNotSend WHERE Email = ?");
        $sth->execute([$email]);
        $result = $sth->fetch(\PDO::FETCH_ASSOC) === false;

        if (!$result) {
            $this->mailLogger->info("address {email} found in DoNotSend table, will not send message", ["email" => $email]);
        }

        return $result;
    }

    private function convertHtmlToText(string $body)
    {
        $doc = TidyDoc($body, false, false);

        require_once __DIR__ . '/html2text.php';
        $text = html2text($doc);
        $text = str_replace("\xc2\xa0", " ", $text);
        $text = preg_replace('#(?<link>\[LINK[^\]]+\])[\s\n\r]*http[^\s$]+#ims', '${1}', $text);
        // we do not want [IMG] tags in text version, nonsense
        $text = preg_replace('#\[IMG[^\]]+\]#ims', '', $text);
        // skip title, it's already in subject
        $text = trim(preg_replace('#^[^\r\n]+[\r\n]+\s*-{5,}#is', '', $text));
        // skip leading link, it's logo href
        $text = trim(preg_replace('#^\[LINK[^\]]+\]#is', '', $text));
        // convert links to one link per line, removing [LINK]
        $text = trim(preg_replace('#\[LINK:\s*([^\s\]]+)\s*\]#ims', "\r\n\${1}\r\n", $text));

        return $text;
    }

    /**
     * @param string|array $originalAddress
     */
    private function logWhenInvalidAddress($originalAddress, string $headerName, $effectiveAddress): void
    {
        if (\is_array($effectiveAddress) && !$effectiveAddress) {
            $this->logger->warning("invalid address for '{$headerName}' header: " . \json_encode($originalAddress));
        }
    }
}
