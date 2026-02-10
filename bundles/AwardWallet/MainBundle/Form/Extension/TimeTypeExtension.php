<?php

namespace AwardWallet\MainBundle\Form\Extension;

use AwardWallet\MainBundle\Form\Transformer\TimeToLocalizedTimeTransformer;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;

class TimeTypeExtension extends AbstractTypeExtension
{
    /**
     * @var LocalizeService
     */
    private $localizeService;

    /**
     * TimeTypeExtension constructor.
     */
    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['html5']) {
            $builder->addViewTransformer(new TimeToLocalizedTimeTransformer($this->localizeService));
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [TimeType::class];
    }
}
