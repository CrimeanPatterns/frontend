<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class LoungeSchema extends AbstractEnhancedSchema
{
    private Connection $connection;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        Connection $connection,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->tokenStorage = $tokenStorage;

        $this->TableName = 'Lounge';
        $this->ListClass = LoungeList::class;

        /** @var Usr $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();
        $managers = stmtAssoc($this->connection->executeQuery("
            SELECT DISTINCT u.Login
            FROM Usr u
                JOIN GroupUserLink gl ON u.UserID = gl.UserID
                JOIN SiteGroup g ON gl.SiteGroupID = g.SiteGroupID
            WHERE g.GroupName = 'staff'
        "))->flatten(1)->toArray();
        $checkedByOptions =
            $this->connection->fetchAllKeyValue("
                SELECT 
                    UserID, 
                    CONCAT(FirstName, ' ', LastName, ' (', Login, ')') AS Login 
                FROM Usr
                WHERE Login IN (?)
                ORDER BY IF(UserID = ?, 0, 1), Login
            ", [$managers, $currentUser->getId()], [Connection::PARAM_STR_ARRAY]);

        $this->Fields = [
            'LoungeID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'filterWidth' => 20,
            ],
            'Name' => [
                'Type' => 'string',
                'filterWidth' => 60,
            ],
            'AirportCode' => [
                'Type' => 'string',
                'filterWidth' => 30,
            ],
            'Terminal' => [
                'Type' => 'string',
                'Sort' => false,
                'AllowFilters' => false,
            ],
            'Gate' => [
                'Type' => 'string',
            ],
            'Gate2' => [
                'Type' => 'string',
            ],
            'OpeningHours' => [
                'Type' => 'string',
                'Database' => false,
                'Note' => '
                    <div class="opening-hours-actions">
                        <a class="action pretty-action" href="javascript:void(0);">Pretty Print</a>
                        <a class="action help-action" href="javascript:void(0);">Help</a>
                        <span class="status"></span>
                    </div>
                ',
            ],
            'IsAvailable' => [
                'Type' => 'boolean',
                'Caption' => 'Available',
            ],
            'PriorityPassAccess' => [
                'Type' => 'boolean',
            ],
            'AmexPlatinumAccess' => [
                'Type' => 'boolean',
            ],
            'DragonPassAccess' => [
                'Type' => 'boolean',
            ],
            'LoungeKeyAccess' => [
                'Type' => 'boolean',
            ],
            'IsRestaurant' => [
                'Type' => 'boolean',
                'Caption' => 'Restaurant',
            ],
            'Location' => [
                'Type' => 'string',
            ],
            'AdditionalInfo' => [
                'Type' => 'string',
                'Sort' => false,
                'AllowFilters' => false,
            ],
            'Amenities' => [
                'Type' => 'string',
            ],
            'Rules' => [
                'Type' => 'string',
            ],
            'Sources' => [
                'Type' => 'string',
                'Size' => 40,
                'InplaceEdit' => false,
                'Sort' => 'AttentionRequired DESC, FreezeAction ASC, IF(DeletedSources IS NOT NULL OR Duplicates = 1, 0, 1) ASC, ChangesCount DESC',
                'Database' => false,
            ],
            'CreateDate' => [
                'Type' => 'datetime',
            ],
            'UpdateDate' => [
                'Type' => 'datetime',
            ],
            'CheckedBy' => [
                'Type' => 'integer',
                'Options' => $checkedByOptions,
            ],
            'CheckedDate' => [
                'Type' => 'datetime',
            ],
            'Visible' => [
                'Type' => 'boolean',
                'Note' => 'Lounge is visible to users',
            ],
        ];
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        unset(
            $result['Gate'],
            $result['Gate2'],
            $result['Location'],
            $result['Amenities'],
            $result['Rules'],
            $result['CreateDate'],
        );

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $list->PageSize = 100;
        $list->CanAdd = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = true;
        $list->ReadOnly = false;
        $list->DefaultSort = 'Sources';
    }
}
