<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Security\RealUserDetector;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecaptchaType extends AbstractType
{
    /**
     * @var string
     */
    private $recaptchaV3Secret;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var string
     */
    private $recaptchaV2Secret;
    /**
     * @var string
     */
    private $recaptchaV3Key;
    /**
     * @var string
     */
    private $recaptchaV2Key;
    /**
     * @var RealUserDetector
     */
    private $realUserDetector;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        string $recaptchaV3Key,
        string $recaptchaV3Secret,
        string $recaptchaV2Key,
        string $recaptchaV2Secret,
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        RequestStack $requestStack,
        RealUserDetector $realUserDetector,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session
    ) {
        $this->recaptchaV3Secret = $recaptchaV3Secret;
        $this->logger = $logger;
        $this->httpDriver = $httpDriver;
        $this->requestStack = $requestStack;
        $this->recaptchaV2Secret = $recaptchaV2Secret;
        $this->recaptchaV3Key = $recaptchaV3Key;
        $this->recaptchaV2Key = $recaptchaV2Key;
        $this->realUserDetector = $realUserDetector;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['action']);
        $resolver->setDefaults([
            'attr' => ['disableLabel' => true],
            'required' => false,
            'mapped' => false,
            'constraints' => [
                new Callback(['callback' => [$this, "validateCallback"]]),
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', HiddenType::class);
        $builder->add('state', HiddenType::class, ['data' => bin2hex(random_bytes(4))]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // non-intrusive, score-based
        $siteKey = $this->recaptchaV3Key;
        $isBot = $this->isCurrentUserBot();

        if ($isBot) {
            // require user to solve some images
            $siteKey = $this->recaptchaV2Key;
        }

        $view->vars['recaptcha_site_key'] = $siteKey;
        $this->session->set($this->getSessionIsBotKey($form->get('state')->getData() ?? 'unknown'), $isBot);
    }

    /**
     * @internal
     */
    public function validateCallback($data, ExecutionContextInterface $context)
    {
        if (!isset($data['state']) || !isset($data['token'])) {
            $this->logger->warning("invalid recaptcha state: " . json_encode($data));
            $context->addViolation("invalid_captcha");

            return;
        }

        $sessionKey = $this->getSessionIsBotKey($data['state'] ?? 'unknown');
        $isBot = $this->session->get($sessionKey, true);
        $this->session->remove($sessionKey);

        $response = $this->httpDriver->request(new \HttpDriverRequest(
            'https://www.google.com/recaptcha/api/siteverify',
            'POST',
            [
                'secret' => $isBot ? $this->recaptchaV2Secret : $this->recaptchaV3Secret,
                'response' => (string) $data['token'],
                'remoteip' => $this->requestStack->getMasterRequest()->getClientIp(),
            ],
            [],
            5
        ));

        $this->logger->info("recaptcha response: " . Strings::cutInMiddle($response->body, 2000), ["ip" => $this->requestStack->getMasterRequest()->getClientIp(), "isBot" => $isBot]);

        $result = json_decode($response->body, true);
        /** @var Form $input */
        $input = $context->getObject();

        if (!is_array($result) || !isset($result['success']) || $result['success'] !== true || (!$isBot && $input->getConfig()->getOption('action') !== $result['action'])) {
            $this->logger->warning("invalid captcha: " . Strings::cutInMiddle($response->body, 2000), ["isBot" => $isBot]);

            if ($isBot) {
                $context->addViolation("invalid_captcha");
            }

            return;
        }

        if (isset($result['score'])) {
            $this->logger->info("recaptcha score", [
                "score" => $result['score'],
                "action" => $result['action'],
                "ip" => $this->requestStack->getMasterRequest()->getClientIp(),
                "isBot" => $isBot,
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'recaptcha';
    }

    private function isCurrentUserBot(): bool
    {
        $realUserScore = $this->realUserDetector->getScore($this->tokenStorage->getToken()->getUser()->getId());

        return $realUserScore->getInvalidPasswordsScore() > 0.001 && $realUserScore->getTotal() < 0.4;
    }

    private function getSessionIsBotKey(string $key): string
    {
        return 'recaptcha_is_bot_' . $key;
    }
}
