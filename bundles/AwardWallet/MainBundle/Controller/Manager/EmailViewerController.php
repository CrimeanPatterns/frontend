<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Socialad;
use AwardWallet\MainBundle\Form\Type\Select2ChoiceType;
use AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Help\Help;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailViewerController extends AbstractController
{
    use JsonTrait;

    public const TEMPLATE_PATH = __DIR__ . '/../../FrameworkExtension/Mailer/Template';
    public const TEMPLATE_NAMESPACE = 'AwardWallet\\MainBundle\\FrameworkExtension\\Mailer\\Template';

    private EntityManagerInterface $entityManager;
    private array $locales;

    private ContainerInterface $serviceContainer;

    private $statPeriodChoices = [
        '-1 day' => 'Last day (-1 day)',
        '-3 day' => 'Last 3 days (-3 day)',
        '-7 day' => 'Last week (-7 day)',
        '-14 day' => 'Last 2 weeks (-14 day)',
        '-21 day' => 'Last 3 weeks (-21 day)',
        '-1 month' => 'Last month (-1 month)',
        '-3 month' => 'Last 3 months (-3 month)',
        '-6 month' => 'Last 6 months (-6 month)',
        '-1 year' => 'Last year (-1 year)',
    ];

    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager, array $locales)
    {
        $this->serviceContainer = $container;
        $this->entityManager = $entityManager;
        $this->locales = $locales;
    }

    /**
     * @Route("/manager/emailViewer/{action}",
     *     name="aw_manager_emailviewer",
     *     defaults={"action" = "page"},
     *     requirements={"action" = "page|form|preview|send|sendAdvt|help"})
     * @Security("is_granted('ROLE_USER') and is_granted('ROLE_MANAGE_EMAILVIEW')")
     */
    public function indexAction(Request $request, $action, Mailer $mailer, FormErrorHandler $formErrorHandler)
    {
        switch ($action) {
            case "page":
                return $this->render('@AwardWalletMain/Manager/EmailViewer/page.html.twig');

                break;

            case "form":
                $form = $this->getEmailViewerForm($request);

                return $this->render('@AwardWalletMain/Manager/EmailViewer/form.html.twig', [
                    'form' => $form->createView(),
                ]);

                break;

            case "preview":
            case "send":
                $form = $this->getEmailViewerForm($request);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $formData = $form->getData();
                    $choicesData = $this->getChoices();
                    $class = $choicesData['choices'][$formData['kind']];
                    /** @var AbstractTemplate $template */
                    $template = call_user_func_array("$class::createFake", [
                        $this->serviceContainer,
                        \array_merge(
                            $formData['form'] ?? [],
                            [
                                'controllerLang' => $formData['lang'],
                                'controllerLocale' => $formData['locale'],
                            ]
                        ),
                    ]);
                    $template->setEmail($formData['address']);
                    $template->setDebug($formData['debug']);
                    $extTemplateVars = [];

                    if (empty($template->getLang())) {
                        $extTemplateVars['lang'] = $formData['lang'];
                    }

                    if (empty($template->getLocale())) {
                        $extTemplateVars['locale'] = $formData['locale'];
                    }
                    $message = $mailer->getMessageByTemplate($template, $extTemplateVars);
                    // email stat
                    $refl = new \ReflectionClass(get_class($template));
                    $emailStat = [
                        'kind' => call_user_func(get_class($template) . "::getEmailKind"),
                        'file' => preg_replace('/\.php$/', '.twig', $refl->getFileName()),
                        'subject' => $message->getSubject(),
                        'period' => $this->statPeriodChoices[$formData['stat_period']],
                        'count' => $this->getEmailStat(call_user_func(get_class($template) . "::getEmailKind"), new \DateTime($formData['stat_period'])),
                        'status' => call_user_func(get_class($template) . "::getStatus"),
                        'desc' => call_user_func(get_class($template) . "::getDescription"),
                        'keywords' => call_user_func(get_class($template) . "::getKeywords"),
                    ];
                    $statHtml = $this->renderView('@AwardWalletMain/Manager/EmailViewer/singleEmailData.html.twig', [
                        'email_stat' => $emailStat,
                    ]);

                    if ($action == 'send') {
                        $mailer->send($message, [
                            Mailer::OPTION_SKIP_DONOTSEND => true,
                            Mailer::OPTION_SKIP_STAT => true,
                        ]);

                        return $this->successJsonResponse([
                            'stat' => $statHtml,
                        ]);
                    } else {
                        return $this->successJsonResponse([
                            'preview' => str_replace("\\n", "", $message->getBody()),
                            'stat' => $statHtml,
                            'subject' => $message->getSubject(),
                        ]);
                    }
                }

                return $this->errorJsonResponse('Form errors', ['details' => $formErrorHandler->getFormErrors($form)]);

                break;

            case "sendAdvt":
                if (!$request->isMethod("POST")) {
                    return $this->errorJsonResponse('Only POST method is allowed');
                }
                $advt = $request->request->get("advt");
                $template = $request->request->get("template");
                $email = $request->request->get("email");
                $choices = $this->getChoices();

                if (empty($advt)) {
                    return $this->errorJsonResponse('Advt is empty');
                }

                if (empty($template) || !isset($choices["kinds"][$template]) || !isset($choices["choices"][$choices["kinds"][$template]])) {
                    return $this->errorJsonResponse('Wrong email template');
                }

                if (empty($email)) {
                    return $this->errorJsonResponse('Email address is empty');
                }

                $class = $choices["choices"][$choices["kinds"][$template]];
                /** @var AbstractTemplate $template */
                $template = call_user_func_array("$class::createFake", [$this->serviceContainer]);

                if (!property_exists($template, "advt")) {
                    return $this->errorJsonResponse('Template does not support advt');
                }

                $ad = new Socialad();
                $ad->setContent($advt);

                $template->advt = $ad;
                $template->setEmail($email);
                $template->setDebug(true);

                $message = $mailer->getMessageByTemplate($template);
                $mailer->send($message, [
                    Mailer::OPTION_SKIP_DONOTSEND => true,
                    Mailer::OPTION_SKIP_STAT => true,
                ]);

                return $this->successJsonResponse();

                break;

            case "help":
                /** @var AbstractTemplate $template */
                $template = Help::createFake($this->serviceContainer);
                $template->setEmail("fake.email@mail.com");
                $template->setDebug(true);
                $message = $mailer->getMessageByTemplate($template);

                return $this->successJsonResponse([
                    'help' => str_replace("\\n", "", $message->getBody()),
                    'title' => 'Macro Help',
                ]);

                break;
        }
    }

    /**
     * @Route("/manager/reports/sentMail", name="aw_manager_sentmail")
     * @Security("is_granted('ROLE_USER') and is_granted('ROLE_MANAGE_SENTMAIL')")
     * @Template("@AwardWalletMain/Manager/EmailViewer/emailStat.html.twig")
     */
    public function emailStatAction(Request $request)
    {
        $form = $this->createFormBuilder([
            'start_date' => new \DateTime('-1 month'),
            'end_date' => new \DateTime(),
        ])
            ->add('start_date', DateType::class, [
                'label' => 'Start Date',
            ])
            ->add('end_date', DateType::class, [
                'label' => 'End Date',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Calculate email volume',
            ])->getForm();

        $form->handleRequest($request);

        $templateParams = [
            'form' => $form->createView(),
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if ($formData['start_date'] > $formData['end_date']) {
                $temp = $formData['start_date'];
                $formData['start_date'] = $formData['end_date'];
                $formData['end_date'] = $temp;
            }
            $conn = $this->entityManager->getConnection();
            $stm = $conn->prepare("
                SELECT
                    Kind,
                    SUM(Messages) AS Messages
                FROM
                    EmailStat
                WHERE
                    StatDate >= ?
                    AND StatDate <= ?
                GROUP BY
                    Kind
                ORDER BY
                    SUM(Messages) DESC
            ");
            $stm->execute([$formData['start_date']->format('Y-m-d'), $formData['end_date']->format('Y-m-d')]);
            $emailData = [];

            foreach ($this->getChoices()['choices'] as $i => $class) {
                $kind = call_user_func("$class::getEmailKind");
                $emailData[$kind] = [
                    'class' => $class,
                    'code' => $i,
                ];
            }
            $stats = [];
            $total = 0;
            $maxMessages = null;
            $choicesData = $this->getChoices();

            while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
                $row['Messages'] = (int) $row['Messages'];

                if (is_null($maxMessages)) {
                    $maxMessages = $row['Messages'];
                }
                $total += $row['Messages'];
                $stats[$row['Kind']]['Messages'] = $row['Messages'];
                $stats[$row['Kind']]['NewFormat'] = false;

                if (isset($emailData[$row['Kind']])) {
                    $class = $emailData[$row['Kind']]['class'];
                    $stats[$row['Kind']]['NewFormat'] = true;
                    $stats[$row['Kind']]['Code'] = $emailData[$row['Kind']]['code'];
                    $stats[$row['Kind']]['Description'] = call_user_func("$class::getDescription");
                }
            }
            $templateParams = array_merge($templateParams, [
                'formData' => $formData,
                'stats' => $stats,
                'totals' => $total,
                'max' => $maxMessages,
            ]);
        }

        return $templateParams;
    }

    public static function scanTemplates($in, $namespace)
    {
        $result = [];
        $finder = (new Finder())
            ->in($in)
            ->depth('== 0')
            ->notName('Layout')
            ->notName('Tools.php')
            ->notName('EmailOffer.php')
            ->notName('Help')
            ->sortByType();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if ($file->isDir()) {
                $result[] = [
                    'type' => 'dir',
                    'name' => $file->getBasename(),
                    'files' => self::scanTemplates($file->getRealPath(), "$namespace\\" . $file->getBasename()),
                ];

                continue;
            }

            if (!in_array($file->getExtension(), ['php'])) {
                continue;
            }
            $className = $namespace . "\\" . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }
            $reflClass = new \ReflectionClass($className);

            if (!$reflClass->isInstantiable()) {
                continue;
            }
            $result[] = [
                'type' => 'file',
                'class' => $className,
            ];
        }

        return $result;
    }

    protected function getEmailViewerForm(Request $request)
    {
        $user = $this->getUser();
        $formData = [
            'kind' => $request->query->get('id'),
            'address' => $request->request->get('address', $user->getEmail()),
            'lang' => $request->request->get('lang', $user->getLanguage()),
            'locale' => $request->request->get('locale', $user->getLanguage() . '_' . $user->getRegion()),
            'stat_period' => $request->request->get('stat_period', '-1 day'),
            'debug' => true,
        ];
        $choicesData = $this->getChoices();
        $allChoices = $choicesData['choices'];
        $choices = [];
        $keywords = [];
        $currentLevel = 0;
        $levels = [];

        foreach ($choicesData['groups'] as $name => $group) {
            if ($group['level'] < $currentLevel) {
                array_splice($levels, $group['level'] + 1);
            }

            if (!isset($levels[$group['level']])) {
                $levels[$group['level']] = 1;
            } else {
                $levels[$group['level']]++;
            }

            $currentLevel = $group['level'];
            $name = implode(".", $levels) . ". " . ucfirst(trim(strtolower(preg_replace('/[A-Z]/', ' $0', $name))));
            $choices[$name] = [];
            $kinds = $group['choices'];

            if (!sizeof($kinds)) {
                $choices[$name][''] = '';
            } else {
                $currentLevel++;

                if (!isset($levels[$currentLevel])) {
                    $levels[$currentLevel] = 0;
                }

                foreach ($kinds as $kind) {
                    if (isset($allChoices[$kind])) {
                        $levels[$currentLevel]++;
                        $status = call_user_func($allChoices[$kind] . '::getStatus');
                        $desc = call_user_func($allChoices[$kind] . '::getDescription');

                        if ($status == AbstractTemplate::STATUS_NOT_READY) {
                            $desc = "(!) " . $desc;
                        }
                        $desc = sprintf("%s. %s", implode(".", $levels), $desc);
                        $choices[$name][$kind] = $desc;
                        $keywords[$kind] = call_user_func($allChoices[$kind] . '::getKeywords');
                        unset($allChoices[$kind]);
                    }
                }
            }
        }

        if (sizeof($allChoices)) {
            $choices['Others'] = [];

            foreach ($allChoices as $kind => $class) {
                $status = call_user_func($class . '::getStatus');
                $desc = call_user_func($class . '::getDescription');

                if ($status == AbstractTemplate::STATUS_NOT_READY) {
                    $desc = "(!) " . $desc;
                }
                $choices['Others'][$kind] = $desc;
                $keywords[$kind] = call_user_func($class . '::getKeywords');
            }
        }

        // sort choices
        foreach ($choices as $groupName => $group) {
            asort($choices[$groupName]);
            $choices[$groupName] = array_flip($choices[$groupName]);
        }

        $builder = $this->createFormBuilder($formData, [
            'csrf_protection' => false,
        ])
            ->add('kind', Select2ChoiceType::class, [
                'label' => 'Email Kind',
                'choices' => $choices,
                'placeholder' => 'Select',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'style' => 'width: 100%',
                ],
                'choice_attr' => function ($choice) use ($keywords) {
                    if (isset($keywords[$choice]) && count($keywords[$choice]) > 0) {
                        return ['keywords' => $keywords[$choice]];
                    }

                    return [];
                },
            ])
            ->add('lang', ChoiceType::class, [
                'label' => 'Language',
                'choices' => array_combine($this->locales, $this->locales),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('locale', LocaleType::class, [
                'label' => 'Locale',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('debug', CheckboxType::class, [
                'label' => 'Debug',
                'label_attr' => [
                    'title' => /** @Ignore */ 'Не пишется статистика по рекламе, не отслеживаются клики по рекламе',
                ],
            ])
            ->add('address', EmailType::class, [
                'label' => 'Send to email address',
                'required' => false,
                'attr' => [
                    'size' => 40,
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('stat_period', ChoiceType::class, [
                'label' => 'Show stats (period)',
                'choices' => array_flip($this->statPeriodChoices),
            ]);

        if (isset($formData['kind']) && isset($choicesData['choices'][$formData['kind']])) {
            $class = $choicesData['choices'][$formData['kind']];
            $newBuilder = $this->createFormBuilder();
            call_user_func_array("$class::tuneManagerForm", [$newBuilder, $this->serviceContainer]);

            if ($newBuilder->count() > 0) {
                $builder->add($newBuilder);
            }
        }

        return $builder->add('send', SubmitType::class, [
            'label' => 'Send email',
        ])
            ->add('show', SubmitType::class, [
                'label' => 'Show email',
            ])
            ->getForm();
    }

    protected function getChoices($data = null, $level = 0)
    {
        if (!isset($data)) {
            $data = self::scanTemplates(self::TEMPLATE_PATH, self::TEMPLATE_NAMESPACE);
        }
        $result = [
            'choices' => [],
            'groups' => [],
            'kinds' => [],
        ];

        foreach ($data as $item) {
            if ($item['type'] == 'dir') {
                $groupName = $item['name'];
                $result['groups'][$groupName] = [
                    'level' => $level,
                    'choices' => [],
                ];
                $r = $this->getChoices($item['files'], $level + 1);
                $result['choices'] = array_merge($result['choices'], $r['choices']);
                $result['kinds'] = array_merge($result['kinds'], $r['kinds']);
                $result['groups'] = array_merge($result['groups'], $r['groups']);
                $choices = array_keys($r['choices']);

                if (count($r['groups'])) {
                    foreach ($r['groups'] as $group) {
                        $choices = array_diff($choices, $group['choices']);
                    }
                }
                $result['groups'][$groupName]['choices'] = $choices;

                continue;
            }
            $result['choices'][$key = strtolower(str_replace('\\', '_', $item['class'])) . "_choice"] = $item['class'];
            $result['kinds'][call_user_func($item['class'] . "::getEmailKind")] = $key;
        }

        return $result;
    }

    protected function getEmailStat($kind, \DateTime $startDate)
    {
        $conn = $this->entityManager->getConnection();
        $sth = $conn->prepare("
            SELECT
                SUM(Messages) AS Messages
            FROM
                EmailStat
            WHERE
                StatDate >= ?
                AND Kind = ?
            GROUP BY
                Kind
        ");
        $sth->execute([$startDate->format('Y-m-d H:i:s'), $kind]);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? 0 : $row['Messages'];
    }
}
