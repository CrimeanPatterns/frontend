<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Service\EnhancedAdmin\ActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\PageRenderer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RAFlightSearchRouteEnhancedSchema implements ActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function getSchema(): string
    {
        return 'RAFlightSearchRoute';
    }

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response
    {
        switch ($actionName) {
            case 'archive':
            case 'unarchive':
            case 'flag':
            case 'unflag':
            case 'delete':
                $ids = $request->request->get('ids');

                if (!is_array($ids)) {
                    throw new NotFoundHttpException();
                }

                $ids = array_filter($ids, 'is_numeric');
                $ids = array_map('intval', $ids);

                if (empty($ids)) {
                    throw new NotFoundHttpException();
                }

                if ($actionName === 'delete') {
                    $this->connection->executeStatement(
                        "
                            DELETE FROM RAFlightSearchRoute
                            WHERE RAFlightSearchRouteID IN (:ids)
                        ",
                        [
                            'ids' => $ids,
                        ],
                        [
                            'ids' => Connection::PARAM_INT_ARRAY,
                        ]
                    );

                    return new JsonResponse([
                        'success' => true,
                    ]);
                }

                if ($actionName === 'flag' || $actionName === 'unflag') {
                    $this->connection->executeStatement(
                        "
                            UPDATE RAFlightSearchRoute
                            SET Flag = :flag
                            WHERE RAFlightSearchRouteID IN (:ids)
                        ",
                        [
                            'flag' => $actionName === 'flag' ? 1 : 0,
                            'ids' => $ids,
                        ],
                        [
                            'flag' => \PDO::PARAM_INT,
                            'ids' => Connection::PARAM_INT_ARRAY,
                        ]
                    );

                    $queriesIds = $this->connection->executeQuery(
                        "
                            SELECT DISTINCT RAFlightSearchQueryID
                            FROM RAFlightSearchRoute
                            WHERE RAFlightSearchRouteID IN (:ids)
                        ",
                        [
                            'ids' => $ids,
                        ],
                        [
                            'ids' => Connection::PARAM_INT_ARRAY,
                        ]
                    )->fetchFirstColumn();

                    return new JsonResponse([
                        'success' => true,
                    ]);
                }

                $this->connection->executeStatement(
                    "
                        UPDATE RAFlightSearchRoute
                        SET Archived = :archived
                        WHERE RAFlightSearchRouteID IN (:ids)
                    ",
                    [
                        'archived' => $actionName === 'archive' ? 1 : 0,
                        'ids' => $ids,
                    ],
                    [
                        'archived' => \PDO::PARAM_INT,
                        'ids' => Connection::PARAM_INT_ARRAY,
                    ]
                );

                return new JsonResponse([
                    'success' => true,
                ]);

            default:
                throw new NotFoundHttpException();
        }
    }
}
