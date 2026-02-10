<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use AwardWallet\Manager\HeaderMenu;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/manager/enhanced/{schema}", requirements={"schema"="\w+"})
 */
class EnhancedSchemaController extends AbstractController
{
    private Environment $twig;

    private HeaderMenu $headerMenu;

    private QueryBuilder $queryBuilder;

    public function __construct(Environment $twig, HeaderMenu $headerMenu, QueryBuilder $queryBuilder)
    {
        $this->twig = $twig;
        $this->headerMenu = $headerMenu;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @Route("/create", name="aw_enhanced_create", options={"expose"=true})
     * @Route("/edit/{id}", name="aw_enhanced_edit", requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function editAction(
        Request $request,
        ServiceLocator $enhancedEditActionLocator,
        string $schema,
        ?int $id = null
    ): Response {
        if (!$enhancedEditActionLocator->has($schema)) {
            throw $this->createNotFoundException(sprintf('Schema "%s" not found', $schema));
        }

        $this->checkRole($schema);

        /** @var EditActionInterface $schemaInstance */
        $schemaInstance = $enhancedEditActionLocator->get($schema);
        $id = is_null($id) || $id === 0 ? null : $id;

        return $schemaInstance->editAction(
            $request,
            new FormRenderer($this->twig, array_merge($this->getMenuData(), [
                'title' => is_null($id)
                    ? sprintf('Create %s', $schema)
                    : sprintf('Edit %s #%d', $schema, $id),
                'schema' => $schema,
            ])),
            is_null($id) || $id === 0 ? null : $id
        );
    }

    /**
     * @Route("/list", name="aw_enhanced_list", options={"expose"=true})
     */
    public function listAction(
        Request $request,
        ServiceLocator $enhancedListActionLocator,
        string $schema
    ): Response {
        if (!$enhancedListActionLocator->has($schema)) {
            throw $this->createNotFoundException(sprintf('Schema "%s" not found', $schema));
        }

        $this->checkRole($schema);

        /** @var ListActionInterface $schemaInstance */
        $schemaInstance = $enhancedListActionLocator->get($schema);

        return $schemaInstance->listAction(
            $request,
            new ListRenderer($this->twig, $this->queryBuilder, array_merge($this->getMenuData(), [
                'title' => sprintf('List of %s', $schema),
                'schema' => $schema,
            ]))
        );
    }

    /**
     * @Route("/autocomplete/{id}", name="aw_enhanced_autocomplete", requirements={"id"="[\w\-\_]+"}, options={"expose"=true})
     */
    public function autocompleteAction(
        Request $request,
        ServiceLocator $enhancedAutocompleteActionLocator,
        string $schema,
        string $id
    ): JsonResponse {
        if (!$enhancedAutocompleteActionLocator->has($schema)) {
            throw $this->createNotFoundException(sprintf('Schema "%s" not found', $schema));
        }

        $this->checkRole($schema);

        /** @var AutocompleteActionInterface $schemaInstance */
        $schemaInstance = $enhancedAutocompleteActionLocator->get($schema);
        $query = $request->query->get('q');

        if (empty($query) || !is_string($query) || mb_strlen($query) < 3) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        return $schemaInstance->autocompleteAction($request, $id, $query);
    }

    /**
     * @Route("/delete", name="aw_enhanced_delete", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF')")
     */
    public function deleteAction(
        Request $request,
        ServiceLocator $enhancedDeleteActionLocator,
        string $schema
    ): JsonResponse {
        if (!$enhancedDeleteActionLocator->has($schema)) {
            throw $this->createNotFoundException(sprintf('Schema "%s" not found', $schema));
        }

        $this->checkRole($schema);

        /** @var DeleteActionInterface $schemaInstance */
        $schemaInstance = $enhancedDeleteActionLocator->get($schema);
        $ids = $request->request->get('ids');

        if (empty($ids) || !is_array($ids)) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        if (count(array_filter($ids, 'is_numeric')) !== count($ids)) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $ids = array_map('intval', $ids);

        return $schemaInstance->deleteAction($request, $ids);
    }

    /**
     * @Route("/action/{action}", name="aw_enhanced_action", requirements={"action"="[\w\-\_]+"}, options={"expose"=true})
     */
    public function customAction(
        Request $request,
        ServiceLocator $enhancedCustomActionLocator,
        string $schema,
        string $action
    ): Response {
        if (!$enhancedCustomActionLocator->has($schema)) {
            throw $this->createNotFoundException(sprintf('Schema "%s" not found', $schema));
        }

        $this->checkRole($schema);

        /** @var ActionInterface $schemaInstance */
        $schemaInstance = $enhancedCustomActionLocator->get($schema);

        return $schemaInstance->action(
            $request,
            new PageRenderer($this->twig, array_merge(
                $this->getMenuData(),
                [
                    'schema' => $schema,
                    'action' => $action,
                ]
            )),
            $action
        );
    }

    private function checkRole(string $schemaName): void
    {
        $role = 'ROLE_MANAGE_' . strtoupper($schemaName);

        if (!$this->isGranted($role)) {
            throw $this->createAccessDeniedException(sprintf('Access denied without "%s" role', $role));
        }

        if (!$this->isGranted('ROLE_MANAGE_INDEX') || $this->isGranted('USER_IMPERSONATED')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getMenuData(): array
    {
        return [
            'menu' => $this->headerMenu->getMenu(),
            'menuJson' => $this->headerMenu->getJsonMenu(),
        ];
    }
}
