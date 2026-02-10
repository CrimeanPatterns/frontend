<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\FaqCategoryRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\WidgetBundle\Widget\ContactUsWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class FaqController extends AbstractController
{
    /**
     * @Route(
     *     "/faqs",
     *     name="aw_faq_index",
     *     options={"expose"=true},
     *     defaults={"_canonical" = "aw_faq_index_locale", "_alternate" = "aw_faq_index_locale"}
     * )
     * @Route(
     *     "/{_locale}/faqs",
     *     name="aw_faq_index_locale",
     *     defaults={"_locale"="en", "_canonical" = "aw_faq_index_locale", "_alternate" = "aw_faq_index_locale"},
     *     requirements={"_locale" = "%route_locales%"}
     * )
     * @Template("@AwardWalletMain/Faq/index.html.twig")
     */
    public function indexAction(
        Request $request,
        RouterInterface $router,
        ContactUsWidget $contactUsWidget,
        FaqCategoryRepository $faqCategoryRepository,
        PageVisitLogger $pageVisitLogger
    ) {
        $contactUsWidget->setActiveItem(1);
        $lang = $request->get('_locale');
        /** @var Usr $user */
        $user = $this->getUser();

        if (is_null($lang)) {
            $lang = $user ? $user->getLanguage() : 'en';
        }

        $faqsCategories = $faqCategoryRepository->findBy(
            ['visible' => true],
            ['rank' => 'ASC']
        );

        $items = [];
        $faqUrl = $router->generate('aw_faq_index_locale', [], Router::ABSOLUTE_URL);

        foreach ($faqsCategories as $faqCategory) {
            $faqs = $faqCategory->getVisibleFaqs();

            foreach ($faqs as $faq) {
                $items[] = [
                    '@type' => 'Question',
                    'url' => $faqUrl . '#' . $faq->getFaqid(),
                    'name' => trim(strip_tags($faq->getQuestion())),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq->getAnswer(),
                    ],
                ];
            }
        }

        $jsonld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                '@type' => 'FAQPage',
                'mainEntity' => $items,
            ],
        ];
        $pageVisitLogger->log(PageVisitLogger::PAGE_FAQS);

        return [
            'jsonld' => $jsonld,
            'categories' => $faqsCategories,
            'isEnglish' => 'en' === $lang,
        ];
    }
}
