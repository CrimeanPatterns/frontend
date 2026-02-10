<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountsSelectorExtendedType extends AbstractType implements DataTransformerInterface, EventSubscriberInterface
{
    /** @var EntityManager */
    private $em;

    /** @var AbRequest */
    private $request;

    /** @var LocalizeService */
    private $localizer;

    /** @var Usr */
    private $user;

    private $cache = [
        'ua' => [],
        'uaccounts' => [],
        'accounts' => [],
    ];
    /**
     * @var AccountListManager
     */
    private $accountListManager;
    /**
     * @var OptionsFactory
     */
    private $optionsFactory;

    public function __construct(AccountListManager $accountListManager, OptionsFactory $optionsFactory, EntityManager $em, LocalizeService $localizer)
    {
        $this->em = $em;
        $this->localizer = $localizer;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
        $builder->addEventSubscriber($this);

        $builder->add('UserAgentID', Select2HiddenType::class, [
            /** @Ignore */
            'label' => false,
            'required' => false,
            'configs' => 'f:$.extend({}, AddForm.select2hiddenOptions, Miles.userAgentsSelect2Options)',
            'init-data' => function ($value) use ($options) {
                if (is_numeric($value)) {
                    return $this->getUseragent($options['user'], $value);
                }

                return null;
            },
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->user = $options['user'];

            if ($event->getForm()->getParent() && $event->getForm()->getParent()->getParent()) {
                $this->request = $event->getForm()->getParent()->getParent()->getData();
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
        ]);
        $resolver->setRequired(['user']);
        $resolver->setAllowedTypes('user', 'AwardWallet\\MainBundle\\Entity\\Usr');
    }

    public function getBlockPrefix()
    {
        return 'accounts_selector_extended';
    }

    /**
     * @param AbAccountProgram $value
     * @return array|mixed
     */
    public function transform($value)
    {
        $result = [];

        if (!$value instanceof AbAccountProgram) {
            return $result;
        }
        $account = $value->getAccount();

        if (!$account) {
            return $result;
        }

        $accountId = $account->getAccountid();

        if ($accountId && $this->getAccount($this->user, $accountId)) {
            $acc = $this->getAccount($this->user, $accountId);
            $ua = $acc['ShareUserAgentID'] ?? $acc['UserAgentID'];

            if (empty($ua)) {
                $ua = 0;
            }

            return [
                'UserAgentID' => $ua,
                'AccountID' => $acc['ID'],
                'Status' => isset($acc['MainProperties']['Status']) ? $acc['MainProperties']['Status']['Status'] : '',
                'Balance' => $acc['Balance'],
            ];
        }

        return $result;
    }

    public function reverseTransform($value)
    {
        $result = new AbAccountProgram();
        $result->setRequest($this->request);

        if (!is_array($value)) {
            return $result;
        }

        if (!isset($value['AccountID'])) {
            return $result;
        }

        $accountId = $value['AccountID'];

        if (!$this->getAccount($this->user, $accountId)) {
            return $result;
        }

        $apRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbAccountProgram::class);
        $ap = $apRep->findOneBy(['AccountID' => $accountId, 'RequestID' => $this->request->getAbRequestID()]);

        if ($ap) {
            return $ap;
        }

        /** @var AccountRepository $accRep */
        $accRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $acc = $accRep->find($accountId);

        if (!$acc) {
            return $result;
        }
        $result->setAccount($acc);

        return $result;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $data = $this->transform($event->getData());
        $form = $event->getForm();
        $this->processAddAccountField($form, $data);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $this->processAddAccountField($form, $data);
    }

    public function getUseragent(Usr $user, $ua)
    {
        $_ua = $this->getCache('ua', $ua);

        if ($_ua === false) {
            return null;
        } elseif (!is_null($_ua)) {
            return $_ua;
        } else {
            if (is_numeric($ua) && $ua == "0") {
                $data = [
                    'UserAgentID' => 0,
                    'FirstName' => null,
                    'MiddleName' => null,
                    'LastName' => null,
                    'Connected' => 0,
                    'text' => $user->getFullName(),
                    'id' => 0,
                ];
            } else {
                /** @var UseragentRepository $uaRep */
                $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
                $data = $uaRep->getAgentInfo($user->getUserid(), $ua, $this->localizer);

                if (!is_array($data)) {
                    $data = false;
                }
            }
            $this->setCache('ua', $ua, $data);

            return ($data === false) ? null : $data;
        }
    }

    public function getAccountsByUa(Usr $user, $ua)
    {
        $_ua = $this->getCache('uaccounts', $ua);

        if ($_ua === false) {
            return [];
        } elseif (!is_null($_ua)) {
            return $_ua;
        } else {
            $options = $this->optionsFactory->createDefaultOptions()
                ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_NOLOAD)
                ->set(Options::OPTION_LOAD_SUBACCOUNTS, false)
                ->set(Options::OPTION_LOAD_PROPERTIES, true)
                ->set(Options::OPTION_USER, $user)
                ->set(Options::OPTION_USERAGENT, $ua);

            $data = $this->accountListManager->getAccountList($options);

            if (empty($data)) {
                $data = false;
            } else {
                $arr = [];

                foreach ($data as $id => $account) {
                    $arr[] = [
                        'id' => $account['ID'],
                        'text' => $account['DisplayName'] . " / " . $account['Login'],
                        'status' => isset($account['MainProperties']['Status']) ? $account['MainProperties']['Status']['Status'] : '',
                        'balance' => $account['Balance'],
                    ];
                }
                $data = $arr;
            }
            $this->setCache('uaccounts', $ua, $data);

            return ($data === false) ? [] : $data;
        }
    }

    public function getAccount(Usr $user, $accountId)
    {
        $_acc = $this->getCache('accounts', $accountId);

        if ($_acc === false) {
            return null;
        } elseif (!is_null($_acc)) {
            return $_acc;
        } else {
            $conn = $this->em->getConnection();
            $sql = "
                SELECT
                    COALESCE(t.UserAgentID, a.UserAgentID, 0) AS UserAgentID
                FROM   Account a
                       LEFT OUTER JOIN
                              ( SELECT sh.AccountID,
                                      ua.*
                              FROM    AccountShare sh
                                      JOIN UserAgent ua
                                      ON      sh.UserAgentID    = ua.UserAgentID
                                              AND ua.AgentID    = :user
                                              AND ua.IsApproved = 1
                              WHERE   sh.AccountID = :acc
                              )
                              t
                       ON     t.AccountID = a.AccountID
                       LEFT OUTER JOIN Provider p
                       ON 	  p.ProviderID = a.ProviderID
                WHERE  a.AccountID  = :acc
                LIMIT 1
            ";
            $statement = $conn->prepare($sql);
            $statement->bindValue(':user', $user->getUserid(), \PDO::PARAM_INT);
            $statement->bindValue(':acc', $accountId, \PDO::PARAM_INT);
            $statement->execute();
            $account = $statement->fetch(\PDO::FETCH_ASSOC);

            if ($account === false) {
                $data = false;
            } else {
                $this->list->setUser($user)->setMapper();
                $this->list->list->setOptions([
                    'load.phones' => false,
                    'load.subaccounts' => false,
                    'load.properties' => true,
                    'filter' => ' AND a.AccountID = ' . $this->em->getConnection()->quote($accountId, \PDO::PARAM_INT),
                ]);
                $this->list->list->setUserAgent($account['UserAgentID']);
                $data = $this->list->list->getAccounts();

                if (!is_array($data) || !isset($data['a' . $accountId])) {
                    $data = false;
                } else {
                    $data = $data['a' . $accountId];
                }
            }

            $this->setCache('accounts', $accountId, $data);

            return ($data === false) ? null : $data;
        }
    }

    private function processAddAccountField($form, $data)
    {
        $options = [];

        if (is_array($data) && isset($data['UserAgentID']) && is_numeric($data['UserAgentID'])) {
            $attr = $this->getAccountsByUa($this->user, $data['UserAgentID']);
            $options = [
                'configs' => 'f:$.extend({
                        data: ' . json_encode($attr) . ',
                    }, AddForm.select2choiceOptions, Miles.accountsSelect2Options)',
                'attr' => [],
            ];

            return $this->addAccountField($form, $options);
        }
        $this->addAccountField($form, $options);
    }

    private function addAccountField(FormInterface $form, $options = [])
    {
        $options = array_merge([
            /** @Ignore */
            'label' => false,
            'required' => false,
            'configs' => 'f:$.extend({
                data: [],
            }, AddForm.select2choiceOptions, Miles.accountsSelect2Options)',
            'attr' => [
                'disabled' => 'disabled',
            ],
        ], $options);
        $form->add('AccountID', Select2HiddenType::class, $options);

        return true;
    }

    private function setCache($key, $subkey, $value)
    {
        $this->cache[$key][$subkey] = $value;
    }

    private function getCache($key, $subkey, $default = null)
    {
        if (!isset($this->cache[$key][$subkey])) {
            return $default;
        }

        return $this->cache[$key][$subkey];
    }
}
