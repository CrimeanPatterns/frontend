<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileCouponType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $apiVersioning;

    public function __construct(
        DataTransformerInterface $dataTransformer,
        EntityManager $em,
        TranslatorInterface $translator,
        ApiVersioningService $apiVersioning
    ) {
        $this->dataTransformer = $dataTransformer;
        $this->em = $em;
        $this->translator = $translator;
        $this->apiVersioning = $apiVersioning;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('submit_label', 'user.coupon.form.submit');

        if ($this->apiVersioning->supports(MobileVersions::COUPON_ERROR_EXTENSION)) {
            $builder->add("head", BlockContainerType::class, [
                'blockData' => new GroupTitle($this->translator->trans('user.coupon.form.title')),
                'attr' => [
                    'class' => 'small',
                ],
            ]);
        }
        $builder->add('coupon', TextType::class, [
            'label' => /** @Desc("Coupon code") */ 'user.coupon.form.label',
            'allow_urls' => true,
            'attr' => ['notice' => 'user.coupon.form.please-enter', 'autocomplete' => 'off'],
        ]);
        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'data_class' => CouponModel::class,
        ]);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function getBlockPrefix()
    {
        return 'mobile_profile_coupon';
    }
}
