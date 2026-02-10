<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SharingOptionsType extends AbstractType implements DataTransformerInterface
{
    private $uaRepo;

    // a bit dirty approach to put state here,
    // there will be problems, when same builder will be user to build two forms
    // I hope we will not build two forms with this field on same page
    private $agents;
    private $selected;
    private $account;

    private $tokenStorage;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TokenStorageInterface $tokenStorage, UseragentRepository $uaRepo, TranslatorInterface $translator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->uaRepo = $uaRepo;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->account = $event->getForm()->getParent()->getData();
            $user = $this->tokenStorage->getToken()->getUser();
            $form = $event->getForm();
            $data = $event->getData();
            $this->selected = [];

            foreach ($data as $agent) {
                /** @var $agent Useragent */
                $this->selected[strval($agent->getUseragentid())] = $agent;
            }
            $this->agents = $this->uaRepo->getShareableAgentsByUserID($user->getUserid(), 'A', $this->translator);
            $this->agents = array_combine(array_map(function ($agent) { return $agent['value']; }, $this->agents), $this->agents);

            if ($options['is_add_form']) {
                foreach (array_filter($this->agents, function ($agent) {return $agent['checked']; }) as $agent) {
                    $this->selected[$agent['value']] = $agent;
                }
            }

            if (!$options['hidden_mode']) {
                foreach ($this->agents as $agent) {
                    $form->add($agent['value'], CheckboxType::class, [
                        'label' => $agent['label'],
                        'required' => false,
                    ]);
                }
            }
        });
    }

    public function getBlockPrefix()
    {
        return 'sharing_options';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($this->agents as $agent) {
            $view->vars[strval($agent['value'])] = $agent;
        }
    }

    /**
     * @return array
     * @internal param Collection $accounts
     */
    public function transform($agents)
    {
        if (null === $agents) {
            return [];
        }

        $result = [];

        foreach ($this->agents as $agent) {
            $result[strval($agent['value'])] = isset($this->selected[$agent['value']]);
        }

        return $result;
    }

    /**
     * @param  array $ids
     * @return Accountshare[]
     */
    public function reverseTransform($ids)
    {
        $result = [];

        foreach ($ids as $id => $selected) {
            if (!$selected) {
                continue;
            }

            if (isset($this->selected[$id])) {
                if ($this->selected[$id] instanceof Useragent) {
                    $result[] = $this->selected[$id];
                } else {
                    $result[] = $this->uaRepo->find($id);
                }
            } else {
                if (isset($this->agents[$id])) {
                    $result[] = $this->uaRepo->find($id);
                }
            }
        }

        return $result;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'is_add_form' => false,
            'hidden_mode' => false,
        ]);
    }
}
