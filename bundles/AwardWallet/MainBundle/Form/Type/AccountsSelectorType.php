<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountsSelectorType extends AbstractType implements DataTransformerInterface
{
    /**
     * @var array
     */
    private $accounts;
    /**
     * @var array
     */
    private $selectedAccounts = [];
    /**
     * @var AbRequest
     */
    private $request;
    /**
     * @var AccountRepository
     */
    private $accountRepo;

    private $readonly;
    /**
     * @var AccountListManager
     */
    private $accountListManager;
    /**
     * @var OptionsFactory
     */
    private $optionsFactory;

    public function __construct(
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        AccountRepository $accountRepo
    ) {
        $this->accountRepo = $accountRepo;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->readonly = $options['read_only_list'];
        $builder->addModelTransformer($this);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var PersistentCollection $data */
            $data = $event->getData();
            $this->selectedAccounts = [];

            foreach ($data as $program) {
                $this->selectedAccounts[strval($program->getAccount()->getAccountid())] = $program;
            }
            $this->request = $event->getForm()->getParent()->getData();

            if (!empty($this->request)) {
                if ($options['read_only_list']) {
                    if (sizeof($this->selectedAccounts)) {
                        $options['filter'] .= " AND a.AccountID IN (" . implode(", ", array_keys($this->selectedAccounts)) . ")";
                    } else {
                        $options['filter'] .= " AND 0 = 1";
                    }
                }
                $listOptions = $this->optionsFactory->createDefaultOptions()
                    ->set(Options::OPTION_FILTER, $options['filter'])
                    ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
                    ->set(Options::OPTION_USER, $this->request->getUser());

                $this->accounts = $this->accountListManager->getAccountList($listOptions);

                foreach ($this->accounts as $account) {
                    $attrs = $options['read_only_list'] ? ['disabled' => 'disabled'] : [];
                    $event->getForm()->add($account['ID'], CheckboxType::class, [
                        'required' => false,
                        'attr' => $attrs,
                    ]);
                }
            }
        });
    }

    public function getBlockPrefix()
    {
        return 'accounts_selector';
    }

    /**
     * @param  Collection $accounts
     * @return array
     */
    public function transform($accounts)
    {
        if (null === $accounts) {
            return [];
        }

        $result = [];

        foreach ($this->accounts as $account) {
            $result[strval($account['ID'])] = isset($this->selectedAccounts[$account['ID']]);
        }

        return $result;
    }

    /**
     * @param  array $ids
     * @return AbAccountProgram[]
     */
    public function reverseTransform($ids)
    {
        $result = [];

        if ($this->readonly) {
            foreach ($this->selectedAccounts as $id => $selected) {
                $result[] = $selected;
            }

            return $result;
        }

        foreach ($ids as $id => $selected) {
            if (!$selected) {
                continue;
            }

            if (isset($this->selectedAccounts[$id])) {
                $result[] = $this->selectedAccounts[$id];
            } else {
                $program = new AbAccountProgram();
                $program->setRequest($this->request);

                if (isset($this->accounts["a" . $id])) {
                    $program->setAccount($this->accountRepo->find($id));
                    $result[] = $program;
                }
            }
        }

        return $result;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($this->accounts as $account) {
            $view->vars[strval($account['ID'])] = [
                "ID" => $account['ID'],
                "DisplayName" => $account['DisplayName'],
                "UserName" => $account['UserName'],
                "Status" => isset($account['MainProperties']['Status']) ? $account['MainProperties']['Status']['Status'] : '',
                "Balance" => $account['Balance'] ?? '',
                "Selected" => isset($this->selectedAccounts[strval($account['ID'])]),
            ];
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'filter' => '',
            'read_only_list' => false,
        ]);
    }
}
