<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Form\Type\CreditCardMultipliersType;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CreditCardAdmin extends AbstractAdmin
{
    /** @var CacheManager */
    private $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
        $this->setUniqId('credit-card');
    }

    public function prePersist(object $card): void
    {
        $this->savePictureFile($card);
    }

    public function preUpdate(object $card): void
    {
        $this->savePictureFile($card);
    }

    public function postUpdate(object $card): void
    {
        $this->cacheManager->invalidateTags([Tags::TAG_CREDIT_CARDS_INFO]);
    }

    public function getPerPageOptions(): array
    {
        return [50, 100, 200, 1_000, 100_000];
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name')
        ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->addIdentifier('name')
            ->add('isDiscontinued')
            ->add('isBusiness')
            ->add('isCashBackOnly')
            ->add('provider.displayName', TextType::class, ['label' => 'Provider'])
//            ->add('clickURL', TextType::class, ['label' => 'ClickURL'])
            ->add('multipliersToString', FieldDescriptionInterface::TYPE_HTML, [
                'label' => 'Bonus Earns',
            ])
            ->add('matchingOrder')
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('name')
            ->add('cardFullName', TextType::class, [
                'required' => false,
            ])
            ->add('displayNameFormat', TextType::class, [
                'help' => 'Display name for account list. Params available to bind: ' . implode(", ", CreditCard::AVAILABLE_FORMAT_PARAMS),
            ])
            ->add('qscreditcard', EntityType::class, [
                'class' => 'AwardWallet\MainBundle\Entity\QsCreditCard',
                'choice_label' => 'CardName',
                'label' => 'QuinStreet Card Name',
                'required' => false,
            ])
            ->add('isDiscontinued')
            ->add('isBusiness')
            ->add('isCashBackOnly', CheckboxType::class, [
                'help' => 'Is cashback card (earns 1¢ per $)',
                'required' => false,
            ])
            ->add('provider', EntityType::class, [
                'class' => 'AwardWallet\MainBundle\Entity\Provider',
                'choice_label' => 'displayName',
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('p');

                    return $qb->where($qb->expr()->eq('p.kind', PROVIDER_KIND_CREDITCARD))
                              ->orderBy('p.displayname', 'ASC');
                },
            ])
            ->add('cobrandProvider', EntityType::class, [
                'class' => 'AwardWallet\MainBundle\Entity\Provider',
                'choice_label' => 'displayName',
                'placeholder' => /** @Ignore */ '',
                'required' => false,
            ])
            ->add('patterns', TextareaType::class, [
                'allow_tags' => true,
                'allow_quotes' => true,
                'allow_urls' => true,
                'required' => false,
                'label' => 'Detected cards patterns',
            ])
            ->add('historyPatterns', TextareaType::class, [
                'allow_tags' => true,
                'allow_quotes' => true,
                'allow_urls' => true,
                'required' => false,
            ])
            ->add('cobrandSubAccPatterns', TextareaType::class, [
                'allow_tags' => true,
                'allow_quotes' => true,
                'allow_urls' => false,
                'required' => false,
                'label' => 'Cobrand SubAccounts Patterns',
            ])
            ->add('clickURL', TextType::class, [
                'label' => 'Blog ClickURL',
                'required' => false,
                'allow_urls' => true,
            ])
            ->add('directClickURL', TextType::class, [
                'label' => 'Direct Click URL',
                'required' => false,
                'allow_urls' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'allow_urls' => true,
            ])
            ->add('pointName', TextType::class, [
                'required' => false,
                'label' => 'Point currency',
            ])
            ->add('matchingOrder')
            ->add('multipliers', CollectionType::class, [
                'entry_type' => CreditCardMultipliersType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
            ])
            ->add('visibleOnLanding', CheckboxType::class, [
                'required' => false,
            ])
            ->add('visibleInList', CheckboxType::class, [
                'required' => false,
                'help' => '<a class="btn btn-warning" href="/manager/edit.php?Schema=CreditCard&ID=' . $this->getSubject()->getId() . '" target="advetise' . $this->getSubject()->getId() . '">Edit Excluded Cards</a>',
                'help_html' => true,
            ])
            ->add('sortIndex', NumberType::class, [
                'required' => false,
            ])
            ->add('text', TextareaType::class, [
                'required' => false,
            ])
            ->add('pictureFile', FileType::class, [
                'required' => false,
                'help' => ($preview = $this->getSubject()->getPicturePath()) ? '<img src="' . $preview . '" alt="">' : '',
                'help_html' => true,
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('patterns')
            ->add('clickURL', TextType::class, ['label' => 'ClickURL'])
            ->add('matchingOrder')
        ;
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'credit_card';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'credit-card';
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        parent::configureDefaultSortValues($sortValues);

        $sortValues[DatagridInterface::PER_PAGE] = 100_000;
    }

    private function savePictureFile(CreditCard $card)
    {
        $card->uploadPictureFile();
    }
}
