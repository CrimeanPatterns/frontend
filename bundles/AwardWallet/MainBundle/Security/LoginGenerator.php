<?php

namespace AwardWallet\MainBundle\Security;

use Doctrine\DBAL\Connection;

class LoginGenerator
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function generate(string $desiredLogin): string
    {
        $suffix = 2;
        $desiredLogin = preg_replace('#[^a-z_0-9A-Z\-]+#ims', '', $desiredLogin);
        $login = $desiredLogin;

        do {
            $exists = $this->connection->executeQuery("select 1 from Usr where Login = ?", [$login])->fetchColumn(0);

            if ($exists !== false) {
                $login = $desiredLogin . $suffix;
                $suffix++;
            }
        } while ($exists !== false && $suffix < 5000);

        if ($exists !== false) {
            throw new \Exception("failed to generate login");
        }

        return $login;
    }
}
