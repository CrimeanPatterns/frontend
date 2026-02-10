<?php

declare(strict_types=1);

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Faq;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Translator\EntityTranslator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FaqController extends AbstractController
{
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/faq", name="awm_faq", methods={"POST"})
     * @JsonDecode
     */
    public function faqAction(
        Request $request,
        EntityManagerInterface $entityManager,
        EntityTranslator $awExtensionTranslationEntity
    ): JsonResponse {
        $faqIdsRaw = $request->request->all();

        $faqIdsList =
            it(\is_array($faqIdsRaw) ? $faqIdsRaw : [])
            ->filter('\is_integer')
            ->take(100)
            ->toArray();

        if ($faqIdsList) {
            $faqsList = $entityManager->getRepository(Faq::class)->findBy(['faqid' => $faqIdsList, 'visible' => 1, 'mobile' => 1]);
        } else {
            $qb = $entityManager->createQueryBuilder();
            $e = $qb->expr();
            $faqsList = $qb
                ->select('f')
                ->from(Faq::class, 'f')
                ->join('f.faqcategory', 'fc')
                ->where($e->andX(
                    $e->eq('fc.visible', 1),
                    $e->eq('f.visible', 1),
                    $e->eq('f.mobile', 1)
                ))
                ->orderBy('fc.rank', 'asc')
                ->addOrderBy('f.rank', 'asc')
                ->getQuery()
                ->execute();
        }

        /** @var Faq[] $faqsMap */
        $faqsMap =
            it($faqsList)
            ->reindexByPropertyPath('faqid')
            ->toArrayWithKeys();

        // preserve order from request
        $faqIdsMap = \array_fill_keys($faqIdsList, null);

        foreach ($faqsMap as $id => $faq) {
            $faqIdsMap[$id] = [
                'question' => $awExtensionTranslationEntity->trans($faq, 'question', ['%locale%' => $request->getLocale()]),
                'answer' => $awExtensionTranslationEntity->trans($faq, 'answer', ['%locale%' => $request->getLocale()]),
                'id' => $id,
            ];
        }

        $faqIdsMap = \array_values(\array_filter($faqIdsMap));

        if (!$faqIdsMap) {
            throw $this->createNotFoundException();
        }

        return $this->jsonResponse($faqIdsMap);
    }
}
