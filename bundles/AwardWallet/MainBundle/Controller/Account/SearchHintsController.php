<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Service\Account\SearchHintsHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchHintsController extends AbstractController
{
    /**
     * Обновление списка поисковых подсказок.
     *
     * @Route("/search-hints", name="aw_account_list_search_hints", methods={"POST"}, condition="request.isXmlHttpRequest()")
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function actionUpdate(Request $request, SearchHintsHelper $helper): JsonResponse
    {
        $params = ($request->getContent() !== '') ? json_decode($request->getContent(), true) : [];
        $result = $helper->update($params['value'] ?? '');

        return new JsonResponse([
            'message' => $result->getMessage(),
            'data' => $result->getData(),
        ]);
    }
}
