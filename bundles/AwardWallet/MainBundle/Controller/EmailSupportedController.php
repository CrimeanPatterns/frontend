<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Entity\Provider;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class EmailSupportedController extends AbstractController
{
    public const EMAIL_FORMAT_KIND_BUS = 13;
    public const EMAIL_FORMAT_KIND_TRANSFER = 14;
    public const EMAIL_FORMAT_KIND_EVENT = 15;
    public const EMAIL_FORMAT_KIND_FERRY = 16;
    public const EMAIL_FORMAT_KIND_AGENCY = 17;

    private LoggerInterface $logger;

    private Api $emailApi;

    private $cssDefinedLanguages = [
        'en', 'de', 'fr', 'es', 'zh', 'ja', 'pt', 'ru', 'he', 'da', 'nl', 'it', 'sv',
        'no', 'fi', 'et', 'ro', 'tr', 'pl', 'vi', 'cs', 'hu', 'id', 'ca', 'bg', 'ko',
        'sr', 'uk', 'lv', 'lt', 'sl', 'el', 'az', 'is', 'ar', 'th', 'sk', 'gl', 'ms',
        'hr', 'bs', 'kk',
    ];

    public function __construct(LoggerInterface $logger, Api $emailApi)
    {
        $this->logger = $logger;
        $this->emailApi = $emailApi;
    }

    /**
     * @Route("/supportedEmail", name="aw_supported_email")
     * @Route("/{_locale}/supportedEmail", name="aw_supported_email_locale", requirements={"_locale" = "%route_locales%"}, defaults={"_locale"="en"})
     */
    public function indexAction()
    {
        $data = $this->emailApi->call('admin/manager/parser/formats/list', false);
        $data = $this->addEmailFormatKind($data);
        $data['kinds'] = $this->getKindFull();
        $data['ignore'] = false;
        $data['names'] = $this->getLanguageNames($data['stats']['languages']);

        return $this->render('@AwardWalletMain/EmailSupported/newIndex.html.twig', $data);
    }

    /**
     * @Route("/manager/email/supportedEmailIgnore", name="aw_supported_email_ignore")
     * @Security("is_granted('ROLE_USER') and is_granted('ROLE_MANAGE_MANUALPARSER')")
     */
    public function indexIgnoreAction()
    {
        $data = $this->emailApi->call('admin/manager/parser/formats/listIgnore', false);
        $data = $this->addEmailFormatKind($data);
        $data['kinds'] = $this->getKindFull();
        $data['ignore'] = true;
        $data['mainTextTitle'] = 'List of email providers hidden for some of the partners.';
        $data['names'] = $this->getLanguageNames($data['stats']['languages']);

        return $this->render('@AwardWalletMain/EmailSupported/newIndex.html.twig', $data);
    }

    public static function getArEmailFormatKind(): array
    {
        return [
            self::EMAIL_FORMAT_KIND_AGENCY => "agencies",
            self::EMAIL_FORMAT_KIND_BUS => "buses",
            self::EMAIL_FORMAT_KIND_EVENT => "events",
            self::EMAIL_FORMAT_KIND_FERRY => "ferries",
            self::EMAIL_FORMAT_KIND_TRANSFER => "transfers",
        ];
    }

    private function getLanguageNames(array $languages)
    {
        $iso = json_decode(file_get_contents(__DIR__ . '/../../../../vendor/awardwallet/service/data/iso-639-1.json'), true);
        $names = [];
        $missing = [];

        foreach ($languages as $lang) {
            if (isset($iso[$lang])) {
                $names[$lang] = $iso[$lang];

                if (!in_array($lang, $this->cssDefinedLanguages)) {
                    $missing[] = $lang;
                }
            }
        }

        if (count($missing) > 0) {
            $this->logger->info('Unset languages in supported formats page: ' . json_encode($missing));
        }

        return $names;
    }

    private function getKindFull(): array
    {
        $kinds = Provider::getKinds();
        $emailFormatKinds = [
            self::EMAIL_FORMAT_KIND_AGENCY => 'track.group.agency',
            self::EMAIL_FORMAT_KIND_BUS => 'track.group.bus',
            self::EMAIL_FORMAT_KIND_EVENT => 'track.group.event',
            self::EMAIL_FORMAT_KIND_FERRY => 'track.group.ferry',
            self::EMAIL_FORMAT_KIND_TRANSFER => 'track.group.transfer',
        ];

        return $kinds + $emailFormatKinds;
    }

    private function addEmailFormatKind(array $data): array
    {
        foreach ($data['providers'] as $keyProv => $typeProvider) {
            foreach ($typeProvider as $key => $provider) {
                if ($provider['emailFormatKind'] !== null) {
                    $data['providers'][$provider['emailFormatKind']][] = $provider;
                    unset($data['providers'][$keyProv][$key]);
                }
            }
        }

        return $data;
    }
}
