<?php

namespace AwardWallet\MainBundle\Service\Blog;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class BlogLinkClickSync
{
    public const API_URL = 'https://awardwallet.com/blog/wp-json/pretty-link/sync/';

    private EntityManagerInterface $entityManager;
    private \CurlDriver $curlDriver;
    private BlogApi $blogApi;

    public function __construct(EntityManagerInterface $entityManager, \CurlDriver $curlDriver, BlogApi $blogApi)
    {
        $this->entityManager = $entityManager;
        $this->curlDriver = $curlDriver;
        $this->blogApi = $blogApi;
    }

    public function sync(int $days = 1): bool
    {
        $data = $this->getData($days);

        foreach ($data as $row) {
            if ('undefined' == ($row['mid'] ?? null)) {
                $row['mid'] = null;
            }

            if ('undefined' == ($row['cid'] ?? null)) {
                $row['cid'] = null;
            }

            $this->entityManager->getConnection()->executeQuery('
                INSERT INTO BlogLinkClick (BlogLinkClickID, PrettyLink, TargetLink, `Source`, `Exit`, MID, CID, RefCode, BlogPostID, UserAgent, ClickDate)
                VALUES (:id, :pretty, :target, :source, :exit, :mid, :cid, :ref, :post, :ua, :click)
                ON DUPLICATE KEY UPDATE
                            PrettyLink = :pretty,
                            TargetLink = :target,
                            `Source` = :source, 
                            `Exit` = :exit, 
                            MID = :mid, 
                            CID = :cid, 
                            RefCode = :ref, 
                            BlogPostID = :post, 
                            UserAgent = :ua
            ',
                [
                    'id' => $row['clickId'],
                    'pretty' => $row['prettyLink'],
                    'target' => $row['targetLink'],
                    'source' => $row['source'],
                    'exit' => $row['exit'],
                    'mid' => $row['mid'],
                    'cid' => $row['cid'],
                    'ref' => $row['rkbtyn'],
                    'post' => $row['postId'],
                    'ua' => $row['userAgent'],
                    'click' => $row['clickDate'],
                ],
                [
                    'id' => \PDO::PARAM_INT,
                    'pretty' => \PDO::PARAM_STR,
                    'target' => \PDO::PARAM_STR,
                    'source' => \PDO::PARAM_STR,
                    'exit' => \PDO::PARAM_STR,
                    'mid' => \PDO::PARAM_STR,
                    'cid' => \PDO::PARAM_STR,
                    'ref' => \PDO::PARAM_STR,
                    'post' => \PDO::PARAM_INT,
                    'ua' => \PDO::PARAM_STR,
                    'click' => \PDO::PARAM_STR,
                ]);
        }

        $this->updateUserIdByRefCode();

        return true;
    }

    private function getData(int $days): array
    {
        $response = $this->curlDriver->request(
            new \HttpDriverRequest(
                self::API_URL . '?days=' . $days,
                Request::METHOD_GET,
                null,
                $this->blogApi->getAuthData(),
                10
            )
        );

        $data = json_decode($response->body, true);

        if (array_key_exists('code', $data)) {
            throw new \Exception('Sync link_click BAD response');
        }

        if (empty($data)) {
            throw new \Exception('Sync link_click EMPTY response');
        }

        return $data;
    }

    private function updateUserIdByRefCode(): void
    {
        $this->entityManager->getConnection()->executeQuery('
            UPDATE BlogLinkClick lc
            JOIN Usr u ON (u.RefCode = lc.RefCode)
            SET lc.UserID = u.UserID
            WHERE
                    lc.RefCode IS NOT NULL
                AND lc.UserID IS NULL
                AND u.UserID IS NOT NULL
        ');
    }
}
