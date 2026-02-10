<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

use Doctrine\DBAL\Connection;

class EmailLog
{
    /** @var int Range 1 - 150, range [151... is reserved for EmailTemplateID */
    public const MESSAGE_KIND_SMTP_BOOKER = 7;
    public const MESSAGE_KIND_RETENTION_USER = 15;
    public const MESSAGE_KIND_CHASE = 20;
    public const MESSAGE_KIND_BLOG_DIGEST = 25;

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $statement;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = $this->connection->prepare("
            SELECT
                *
            FROM
                EmailLog
            WHERE
                UserID = ?
                AND DATE(EmailDate) = DATE(NOW())
                AND MessageKind = ?
        ");
    }

    public function recordEmailToLog(int $userId, int $kind, ?string $template = null, ?string $code = null)
    {
        $this->statement->execute([$userId, $kind]);

        if ($row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
            $this->connection->update('EmailLog',
                ['MessageCount' => $row['MessageCount'] + 1],
                ['EmailLogID' => $row['EmailLogID']]
            );
        } else {
            $this->connection->insert('EmailLog', [
                'UserID' => $userId,
                'MessageKind' => $kind,
                'EmailDate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'MessageCount' => 1,
                'Template' => $template,
                'Code' => $code,
            ]);
        }
    }
}
