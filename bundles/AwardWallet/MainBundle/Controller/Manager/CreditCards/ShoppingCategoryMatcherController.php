<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\Common\Monolog\Handler\ArrayHandler;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Doctrine\DBAL\Connection;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ShoppingCategoryMatcherController
{
    private Connection $connection;

    private Logger $logger;
    private ShoppingCategoryMatcher $shoppingCategoryMatcher;

    public function __construct(Connection $connection, Logger $logger, ShoppingCategoryMatcher $shoppingCategoryMatcher)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->shoppingCategoryMatcher = $shoppingCategoryMatcher;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/credit-cards/shopping-category-matcher-test", name="aw_shopping_category_matcher_test")
     */
    public function testAction(FormFactoryInterface $formFactory, Request $request, Environment $twig)
    {
        $builder = $formFactory->createBuilder();
        $builder->add('Category', TextType::class);
        $builder->add('Provider', TextType::class, ['label' => 'Provider ID']);
        $builder->add('Check', SubmitType::class);

        $form = $builder->getForm();
        $form->handleRequest($request);
        $logs = new ArrayHandler();
        $logs->setFormatter(new LineFormatter());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->logger->pushHandler($logs);

            try {
                $this->detectCategory($data['Category'], $data['Provider']);
            } finally {
                $this->logger->popHandler();
            }
        }

        return new Response($twig->render("@AwardWalletMain/Manager/CreditCards/shoppingCategoryMatcherTest.html.twig", [
            "form" => $form->createView(),
            "logs" => $logs->getRecords(),
        ]));
    }

    private function detectCategory(string $category, int $providerId): void
    {
        $this->logger->info("detecting merchant category for: {$category}, provider {$providerId}");
        $categoryId = $this->shoppingCategoryMatcher->identify($category, $providerId);
        $this->logger->info("detected category: {$categoryId}");
    }
}
