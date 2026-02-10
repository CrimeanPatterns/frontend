<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\Common\Monolog\Handler\ArrayHandler;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class CreditCardMatcherController
{
    private Connection $connection;

    private Logger $logger;

    private CreditCardMatcher $cardMatcher;
    private ProviderRepository $providerRepository;
    private RouterInterface $router;

    public function __construct(Connection $connection, Logger $logger, CreditCardMatcher $cardMatcher, ProviderRepository $providerRepository, RouterInterface $router)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->cardMatcher = $cardMatcher;
        $this->providerRepository = $providerRepository;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_CREDITCARD')")
     * @Route("/manager/credit-cards/credit-card-matcher-test", name="aw_credit_card_matcher_test")
     */
    public function testAction(FormFactoryInterface $formFactory, Request $request, Environment $twig)
    {
        $builder = $formFactory->createBuilder();
        $builder->add('CardName', TextType::class, ["required" => true, 'help' => 'Parsed credit card name', 'attr' => ['size' => 80]]);
        $builder->add('Provider', TextType::class, ["required" => true, 'help' => 'ProviderID or Code']);
        $builder->add('Check', SubmitType::class);

        $form = $builder->getForm();

        if ($request->query->has('CardName')) {
            $form->get('CardName')->setData($request->query->get('CardName'));
        }

        if ($request->query->has('Provider')) {
            $form->get('Provider')->setData($request->query->get('Provider'));
        }

        $form->handleRequest($request);
        $logs = new ArrayHandler();
        $logs->setFormatter(new LineFormatter());
        $cardLink = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->logger->pushHandler($logs);

            try {
                [$error, $cardLink] = $this->findCard($data);

                if ($error !== null) {
                    $form->addError(new FormError($error));
                }
            } finally {
                $this->logger->popHandler();
            }
        }

        return new Response($twig->render("@AwardWalletMain/Manager/CreditCards/creditCardMatcherTest.html.twig", [
            "form" => $form->createView(),
            "logs" => $logs->getRecords(),
            "cardLink" => $cardLink,
        ]));
    }

    /**
     * @return array - [$error, $cardLink]
     */
    private function findCard(array $data): array
    {
        $providerId = $this->findProvider($data['Provider']);

        if ($providerId === null) {
            return ["Provider not found", null];
        }

        $this->logger->info("searching credit card for: {$data['CardName']}, ProviderID: {$providerId}");
        $cardId = $this->cardMatcher->identify($data['CardName'], $providerId);

        if ($cardId === null) {
            $this->logger->info("card not found");

            return [null, null];
        }

        $this->logger->info("identified as credit card: $cardId");
        $this->logger->info("card name: " . $this->connection->executeQuery("select Name from CreditCard where CreditCardID = ?", [$cardId])->fetchOne());

        return [null, $this->router->generate("aw_manager_edit", ["ID" => $cardId, "Schema" => "CreditCard"])];
    }

    private function findProvider(string $provider): ?int
    {
        if (is_numeric($provider)) {
            $providerEntity = $this->providerRepository->find($provider);
        } else {
            $providerEntity = $this->providerRepository->findOneBy(['code' => $provider]);
        }

        if ($providerEntity === null) {
            return null;
        }

        return $providerEntity->getId();
    }
}
