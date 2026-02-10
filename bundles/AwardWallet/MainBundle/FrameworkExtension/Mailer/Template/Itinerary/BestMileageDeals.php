<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BestMileageDeals extends AbstractTemplate
{
    public array $data = [];

    public static function getDescription(): string
    {
        return 'Best Mileage Deals';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $providerIds = $container->get('doctrine.orm.default_entity_manager')->getConnection()
            ->fetchAllKeyValue('
                SELECT DisplayName, ProviderID
                FROM Provider
                WHERE Kind = :kind AND AwardChangePolicy IS NOT NULL
            ', ['kind' => PROVIDER_KIND_AIRLINE], ['kind' => \PDO::PARAM_INT]);
        $builder->add('providerId', ChoiceType::class, [
            'label' => /** @Ignore */ 'Provider with Award Change Policy',
            'choices' => $providerIds,
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $providerRep = $container->get(ProviderRepository::class);

        if ($providerId = ($options['providerId'] ?? null)) {
            /** @var Provider $provider */
            $provider = $providerRep->find($providerId);
        } else {
            // United Airlines
            /** @var Provider $provider */
            $provider = $providerRep->find(26);
        }

        $template = new static(Tools::createUser());

        $template->data = [
            'destination' => 'Chicago, IL (ORD)',
            'existing' => [
                'airline_name' => 'Avianca (LifeMiles)',
                'airline_code' => 'aviancataca',
                'passengers' => 2,
                'policy' => $provider->getAwardChangePolicy(),
                'phone' => '1-800-123-12345',
                'dep_date' => 'Friday, June 28, 2024',
                'conf_no' => 'OQ28NZ',
                'cost' => '56,490 miles',
                'total_charge' => '$244.80',
                'duration' => '16h 25m',
                'segments' => [
                    [
                        'from' => [
                            'name' => 'Newark, NJ (EWR)',
                            'time' => '1:00 PM',
                        ],
                        'to' => [
                            'name' => 'Boston, MA (BOS)',
                            'time' => '2:16 PM',
                        ],
                        'airline' => 'American Airlines UA1994',
                        'cabin' => 'Economy',
                        'layover' => '3h 20m',
                    ],
                    [
                        'from' => [
                            'name' => 'Boston, MA (BOS)',
                            'time' => '5:35 PM',
                        ],
                        'to' => [
                            'name' => 'Chicago, IL (ORD)',
                            'time' => '7:16 PM',
                        ],
                        'airline' => 'American Airlines UA6722',
                        'cabin' => 'Economy',
                    ],
                ],
            ],
            'found_routes' => [
                [
                    'airline_name' => 'Virgin Atlantic (Flying Club)',
                    'airline_code' => 'virgin',
                    'airline_color' => '#60116A',
                    'phone' => '1-800-864-8331',
                    'dep_date' => 'Friday, June 28, 2024',
                    'cost' => '55,500 miles',
                    'total_charge' => '$464.87',
                    'duration' => '16h 25m',
                    'check_availability_url' => 'https://www.virginatlantic.com',
                    'changes' => it([
                        [
                            'kind' => 'duration',
                            'duration' => '1h 50m',
                            'percentage' => '18%',
                            'isBeneficial' => true,
                        ],
                        [
                            'kind' => 'cost',
                            'amount' => '15,500',
                            'currency' => 'miles',
                            'percentage' => '23%',
                            'isBeneficial' => true,
                        ],
                        [
                            'kind' => 'taxes',
                            'amount' => '$220.07',
                            'percentage' => '10%',
                            'isBeneficial' => false,
                        ],
                    ])
                        ->map(function (array $change) {
                            if ($change['kind'] === 'cost') {
                                return $change;
                            }

                            if (rand(0, 1)) {
                                return null;
                            }

                            $values = [true, false];
                            $change['isBeneficial'] = $values[array_rand($values)];

                            return $change;
                        })
                        ->filterNotNull()
                        ->usort(fn (array $a, array $b) => $b['isBeneficial'] <=> $a['isBeneficial'])
                        ->toArray(),
                    'segments' => [
                        [
                            'from' => [
                                'name' => 'Newark, NJ (EWR)',
                                'time' => '1:00 PM',
                            ],
                            'to' => [
                                'name' => 'Boston, MA (BOS)',
                                'time' => '2:16 PM',
                            ],
                            'airline' => 'American Airlines UA1994',
                            'cabin' => 'Business',
                            'layover' => '3h 20m',
                        ],
                        [
                            'from' => [
                                'name' => 'Boston, MA (BOS)',
                                'time' => '5:35 PM',
                            ],
                            'to' => [
                                'name' => 'Chicago, IL (ORD)',
                                'time' => '7:16 PM',
                            ],
                            'airline' => 'American Airlines UA6722',
                            'cabin' => 'Economy',
                        ],
                    ],
                ],
            ],
        ];

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
