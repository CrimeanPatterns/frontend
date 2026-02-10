<?php

namespace AwardWallet\Manager;

use AwardWallet\MainBundle\Globals\Utils\OutputBufferingUtils;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class SchemaController
{
    private ServiceLocator $lists;
    /**
     * @var FormTunerInterface[]
     */
    private iterable $formTuners;

    private RouterInterface $router;

    public function __construct(ServiceLocator $lists, iterable $formTuners, RouterInterface $router)
    {
        $this->lists = $lists;
        $this->formTuners = $formTuners;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_INDEX')")
     * @Route("/list.php", name="aw_manager_list")
     */
    public function listAction(Request $request, Environment $twig, SchemaFactory $schemaFactory)
    {
        $schemaName = $this->checkSchemaName($request->query->get('Schema'));
        $schema = null;
        $content = OutputBufferingUtils::captureOutput(function () use ($schemaFactory, $schemaName, &$schema) {
            $schema = $schemaFactory->getSchema($schemaName);

            if ($this->lists->has($schemaName)) {
                /** @var \TBaseList $list */
                $list = $this->lists->get($schemaName);
                $schema->TuneList($list);

                $list->Update();
                call_user_func([$list, $schema->ShowMethod]);
            } else {
                $schema->ShowList();
            }
        });

        return new Response($twig->render('@AwardWalletMain/Manager/layout.html.twig', [
            'content' => $content,
            'title' => $schema->Name,
            'contentTitle' => $schema->Name,
        ]));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_INDEX')")
     * @Route("/export.php", name="aw_manager_list_export")
     */
    public function listExportAction(Request $request, SchemaFactory $schemaFactory)
    {
        $schemaName = $this->checkSchemaName($request->query->get('Schema'));
        $content = OutputBufferingUtils::captureOutput(function () use ($schemaName, $schemaFactory) {
            $schema = $schemaFactory->getSchema($schemaName);

            if ($this->lists->has($schemaName)) {
                /** @var \TBaseList $list */
                $list = $this->lists->get($schemaName);
                $schema->TuneList($list);
                $list->ExportCSV();
            } else {
                $schema->ExportCSV();
            }
        });

        return new Response($content, 200, [
            "Content-type" => "text/csv; charset=utf-8",
            "Content-Disposition" => " attachment; filename=" . $schemaName . ".csv",
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_INDEX')")
     * @Route("/edit.php", name="aw_manager_edit")
     */
    public function editAction(Request $request, Environment $twig, SchemaFactory $schemaFactory)
    {
        $schema = null;
        $enhancedSchema = false;
        $content = OutputBufferingUtils::captureOutput(function () use ($request, $schemaFactory, &$schema, &$enhancedSchema) {
            $schema = $schemaFactory->getSchema($this->checkSchemaName($request->query->get('Schema')));
            $enhancedSchema = $schema instanceof AbstractEnhancedSchema && $schema->isEnhancedEditAction();

            $request->getSession()->set(AbstractEnhancedSchema::BACK_URL_SESSION_KEY, $request->headers->get('referer'));

            if (!$enhancedSchema) {
                foreach ($this->formTuners as $tuner) {
                    $schema->addFormTuner([$tuner, "tuneForm"]);
                }

                $schema->ShowForm();
            }
        });

        if ($enhancedSchema) {
            return new RedirectResponse(
                $this->router->generate('aw_enhanced_edit', [
                    'schema' => $schema->Name,
                    'id' => $schema->id,
                ])
            );
        }

        return new Response($twig->render('@AwardWalletMain/Manager/layout.html.twig', [
            'content' =>
                $this->createLinkifier($twig, $schema)
                . $content,
            'title' => $schema->Name,
            'contentTitle' => $schema->Name,
        ]));
    }

    private function createLinkifier(Environment $twig, \TBaseSchema $schema): string
    {
        return $twig->render('@AwardWalletMain/Manager/Schema/contentTitleLinkifier.html.twig', [
            'listLink' => $this->router->generate('aw_manager_list', [
                'Schema' => $schema->Name,
                'PageBy' . $schema->KeyField => $schema->id,
            ]),
        ]);
    }

    private function checkSchemaName(?string $schemaName): string
    {
        if (!preg_match('/^\w+$/', $schemaName)) {
            throw new BadRequestHttpException('Invalid schema name');
        }

        return $schemaName;
    }
}
