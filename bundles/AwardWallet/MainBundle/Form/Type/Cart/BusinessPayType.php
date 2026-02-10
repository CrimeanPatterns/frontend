<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class BusinessPayType extends AbstractType
{
    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var LocalizeService
     */
    protected $localizer;

    protected $translator;

    protected $transactionManager;

    public function __construct(LocalizeService $localizer,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        BusinessTransactionManager $transactionManager)
    {
        $this->localizer = $localizer;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->transactionManager = $transactionManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['estimate']) && $options['estimate'] > 0) {
            $recurringRange = $this->getRecurringRange($options['estimate']);
        } else {
            $recurringRange = $this->getDefaultRange();
        }

        $builder
            ->add('credit_amount', ChoiceType::class, [
                'choices' => array_flip($recurringRange),
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'convert-to-number' => '',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => /** @Ignore */ false,
            'estimate' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'business_pay';
    }

    public function getRecurringRange($monthlyEstimate)
    {
        $result = [];

        for ($i = 1; $i <= 12; $i++) {
            $price = $monthlyEstimate * $i;
            $message = $this->translator->trans("business.estimated.cost.monthly", [
                '%count%' => $i,
            ]);
            $result[$price] = $this->localizer->formatCurrency($price, 'USD', false) . ' (' . $message . ' )';
        }

        return $result;
    }

    public function getDefaultRange()
    {
        $result = [10, 50, 100, 200, 500, 1000];
        $result = array_combine($result, $result);

        foreach ($result as $price => $val) {
            $result[$price] = $this->localizer->formatCurrency($price, 'USD', false);
        }

        return $result;
    }
}
