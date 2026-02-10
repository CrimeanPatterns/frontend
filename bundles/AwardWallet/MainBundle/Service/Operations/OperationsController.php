<?php

namespace AwardWallet\MainBundle\Service\Operations;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoItem;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoResponse;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use JMS\TranslationBundle\Annotation\Ignore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class OperationsController
{
    private Connection $connection;
    private QueueInfoResponse $loyaltyQueueInfo;

    public function __construct(
        Connection $connection,
        ApiCommunicator $communicator
    ) {
        $this->connection = $connection;
        $this->loyaltyQueueInfo = $communicator->GetQueueInfo();
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_OPERATIONS')")
     * @Route("/manager/operations")
     */
    public function testAction(
        FormFactoryInterface $formFactory,
        Request $request,
        Environment $twig,
        Process $asyncProcess,
        AwTokenStorage $tokenStorage,
        Client $messaging
    ) {
        ini_set('memory_limit', '2048M');
        $provider = null;
        $id = $request->query->get('ID');

        if (isset($id)) {
            $provider = $this->connection->executeQuery(/** @lang MySQL */
                "SELECT Code FROM Provider WHERE Provider.ProviderID = ?",
                [$id],
                [\PDO::PARAM_INT]
            )->fetchOne();
        }

        $builder = $formFactory->createBuilder();

        $builder
            ->add('Operation', ChoiceType::class, [
                'required' => true,
                'row_attr' => ['class' => 'select'],
                'choices' => /** @Ignore */ array_flip(OperationsExecutor::listOfOperations()),
            ])
            ->add('CheckStart', DateType::class, [
                'row_attr' => ['style' => 'display:none', 'class' => 'dates'],
            ])
            ->add('CheckEnd', DateType::class, [
                'row_attr' => ['style' => 'display:none', 'class' => 'dates'],
            ])
            ->add('Provider', ChoiceType::class, [
                'choices' => /** @Ignore */ $this->listOfProviders(),
                'row_attr' => ['class' => 'select'],
                'required' => true,
            ])
            ->add('WithBackgroundCheckOff', CheckboxType::class, [
                'attr' => ['class' => 'checkBoxRight'],
                'required' => false,
                'help' => /** @Ignore */ "<div class=\"fieldhint\" style=\"margin-top: -10px;\" id=\"fldCheckingOffHint\">Include providers with State 'Checking off' and 'Fixing'. Use it carefully!</div>",
                'help_html' => true,
            ])
            ->add('Limit', IntegerType::class)
            ->add('Execute', SubmitType::class, [
                'row_attr' => ['class' => 'button'],
                'label' => /** @Ignore */ 'Execute',
            ]);

        $data = [
            'Operation' => null,
            'CheckStart' => new \DateTime("-1 week"),
            'CheckEnd' => new \DateTime("-1 day"),
            'Provider' => $provider,
            'WithBackgroundCheckOff' => false,
            'Limit' => 300,
        ];

        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        $channel = null;
        $optionText = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['Operation'] === 'checkProviderZB') {
                // show fields
                $options = $form->get('CheckStart')->getConfig()->getOptions();
                unset($options['row_attr']['style']);
                $builder->add('CheckStart', DateType::class, $options);

                $options = $form->get('CheckEnd')->getConfig()->getOptions();
                unset($options['row_attr']['style']);
                $builder->add('CheckEnd', DateType::class, $options);

                $form = $builder->getForm();
                $form->handleRequest($request);
            }
            $channel = UserMessaging::getChannelName('operations' . bin2hex(random_bytes(10)),
                $tokenStorage->getUser()->getUserid());
            $task = new OperationsTask($channel, $data['Operation'], $data['Provider'], $data['Limit'],
                $data['WithBackgroundCheckOff'], $data['CheckStart']->getTimestamp(),
                $data['CheckEnd']->getTimestamp());
            $optionText = OperationsExecutor::getOperationText($data['Operation'], $data['Provider']);
            $asyncProcess->execute($task);
        }

        return new Response($twig->render("@AwardWalletMain/Manager/Support/Operations/operations.html.twig", [
            "title" => "Operations",
            "contentTitle" => "",
            'queueInformation' => $this->getQueueInformation($twig),
            "form" => $form->createView(),
            "channel" => $channel,
            "action" => $optionText,
            'centrifuge_config' => $messaging->getClientData(),
        ]));
    }

    private function getQueueInformation(Environment $twig)
    {
        $loyaltyTotal = 0;
        $loyaltyProvidersQueue = '';
        // TODO into twig
        $rowHtml = '<tr><td>%s</td><td style="text-align: right">%s</td></tr>';
        $totalPriorities = [];
        $priorityMapping = [
            2 => 'Background',
            3 => 'Background (aa)',
            6 => 'Operations',
            7 => 'Users',
        ];

        if (!empty($this->loyaltyQueueInfo->getQueues())) {
            $queue = [];
            $totalPriorities = [];

            /** @var QueueInfoItem $queueItem */
            foreach ($this->loyaltyQueueInfo->getQueues() as $queueItem) {
                if (!isset($queue[$queueItem->getProvider()])) {
                    $queue[$queueItem->getProvider()] = 0;
                }

                $queue[$queueItem->getProvider()] += $queueItem->getItemsCount();
                $loyaltyTotal += $queueItem->getItemsCount();

                if (empty($queueItem->getPriority())) {
                    continue;
                }

                if (!isset($totalPriorities[$queueItem->getPriority()])) {
                    $totalPriorities[$queueItem->getPriority()] = 0;
                }

                $totalPriorities[$queueItem->getPriority()] += $queueItem->getItemsCount();
            }
            arsort($queue);

            foreach ($queue as $provider => $queueInfo) {
                $loyaltyProvidersQueue .= sprintf($rowHtml, $provider, $queueInfo);
            }
        }// if (!empty($loyaltyQueueInfo->getQueues()))

        foreach ($totalPriorities as $key => $val) {
            $loyaltyProvidersQueue = sprintf($rowHtml,
                '<b>' . ($priorityMapping[$key] ?? '') . ' (' . $key . ')</b>',
                '<b>' . $val . '</b>') . $loyaltyProvidersQueue;
        }

        $loyaltyProvidersQueue = sprintf($rowHtml, '<b>Total</b>',
            '<b>' . $loyaltyTotal . '</b>') . $loyaltyProvidersQueue;

        return $twig->render('@AwardWalletMain/Manager/Support/Operations/queueInformation.html.twig',
            ['loyaltyProvidersQueue' => $loyaltyProvidersQueue]
        );
    }

    private function listOfProviders(): array
    {
        $res = $this->connection->executeQuery(/** @lang MySQL */ "
                SELECT Code, DisplayName 
                FROM Provider 
                WHERE State >= ? OR State = ?
                ORDER BY DisplayName", [PROVIDER_ENABLED, PROVIDER_TEST],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetchAllAssociative();
        $providers = ['Select' => ''];

        foreach ($res as $r) {
            $providers[$r['DisplayName']] = $r['Code'];
        }

        return $providers;
    }
}
