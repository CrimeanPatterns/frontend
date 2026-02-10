<?

class TBalanceWatchSchema extends TBaseSchema
{
    function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = "BalanceWatch";
        $this->Description = "Transfer Times";
        $this->ListClass = 'BalanceWatchAdminList';
        $this->Fields = [
            "BalanceWatchID" => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "ReadOnly" => true,
                "filterWidth" => 20,
                "Sort" => "BalanceWatchID DESC",
                "InplaceEdit" => false,
                "InputAttributes" => " readonly",
            ],
            "FirstName" => [
                "Type" => "string",
                "filterWidth" => 50,
                "FilterField" => "u.FirstName",
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "LastName" => [
                "Type" => "string",
                "filterWidth" => 50,
                "FilterField" => "u.LastName",
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "Email" => [
                "Type" => "string",
                "filterWidth" => 50,
                "FilterField" => "u.Email",
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "TransferFromProviderID" => [
                "Caption" => "Source Program",
                "Type" => "integer",
                "filterWidth" => 50,
                "FilterField" => "bw.TransferFromProviderID",
                "InplaceEdit" => false,
                "Options" => ["" => ""] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
            ],
            'SourceProgramRegion' => [
                'Caption' => 'Source Region',
                'Type' => 'string',
                'Required' => false,
                'InplaceEdit' => false,
            ],
            "ProviderID" => [
                "Type" => "integer",
                "Caption" => "Target Program",
                "filterWidth" => 50,
                "FilterField" => "a.ProviderID",
                "ReadOnly" => false,
                "InplaceEdit" => false,
                "Options" => ["" => ""] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
            ],
            'TargetProgramRegion' => [
                'Caption' => 'Target Region',
                'Type' => 'string',
                'Required' => false,
                'InplaceEdit' => false,
            ],
            "PointsSource" => [
                "Caption" => "Points Source",
                "Type" => "integer",
                "filterWidth" => 50,
                "FilterField" => "PointsSource",
                "Options" => \AwardWallet\MainBundle\Entity\BalanceWatch::POINTS_SOURCES,
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "ExpectedPoints" => [
                "Type" => "integer",
                "filterWidth" => 50,
                "FilterField" => "bw.ExpectedPoints",
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "StopReason" => [
                "Type" => "integer",
                "Required" => true,
                "filterWidth" => 50,
                "FilterField" => "StopReason",
                "Options" => \AwardWallet\MainBundle\Entity\BalanceWatch::REASONS,
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "CreationDate" => [
                "Type" => "datetime",
                "Required" => true,
                "filterWidth" => 50,
                "FilterField" => "bw.CreationDate",
                "ReadOnly" => true,
                "InplaceEdit" => false,
                "InputAttributes" => " readonly",
            ],
            "TransferRequestDate" => [
                "Type" => "datetime",
                "filterWidth" => 50,
                "FilterField" => "bw.TransferRequestDate",
                "ReadOnly" => true,
                "InplaceEdit" => false,
                "InputAttributes" => " readonly",
            ],
            "TransferRequested" => [
                "Type" => "integer",
                "Required" => true,
                "filterWidth" => 50,
                "ReadOnly" => true,
                "InplaceEdit" => false,
            ],
            "StopDate" => [
                "Type" => "datetime",
                "filterWidth" => 50,
                "FilterField" => "bw.StopDate",
                "ReadOnly" => true,
                "InplaceEdit" => false,
                "InputAttributes" => " readonly",
            ],
            "ProcessingTime" => [
                "Type" => "float",
                "filterWidth" => 40,
                "FilterField" => "TIMESTAMPDIFF(HOUR,bw.TransferRequestDate, bw.StopDate)",
                "ReadOnly" => true,
                "InplaceEdit" => false,
                "InputAttributes" => " readonly",
            ],
            "Status" => [
                "Type" => "string",
                "Required" => true,
                "filterWidth" => 50,
                "Options" => \AwardWallet\MainBundle\Entity\BalanceWatch::STATUSES
            ],
            "Note" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => false,
                "filterWidth" => 130,
            ],
        ];
    }

    function TuneList(&$list)
    {
        parent::TuneList($list);

        $tranferRequestedField = "case 
            when TIMESTAMPDIFF(HOUR,bw.TransferRequestDate, bw.CreationDate) < 25 then TIMESTAMPDIFF(HOUR,bw.TransferRequestDate, bw.CreationDate)
            else 25
        end
        ";

        $options = array_merge(
            [
                "Less than hour ago",
                "1 hour ago",
            ],
            array_map(function(string $hours){ return $hours . " hours ago"; }, range(2, 24)),
            [
                "More than 24 hours ago"
            ]
        );
        $list->Fields["TransferRequested"]["Options"] = array_combine(range(0, 25), $options);

//        if (isset($_GET['TransferRequested']) && isset($options[(int)$_GET['TransferRequested']])) {
            $list->Fields["TransferRequested"]["FilterField"] = "case when TIMESTAMPDIFF(HOUR, bw.TransferRequestDate, bw.CreationDate) < 25 then TIMESTAMPDIFF(HOUR, bw.TransferRequestDate, bw.CreationDate) else 25 end";
//        }

        $list->CanAdd = false;
        $list->AllowDeletes = true;
        $list->MultiEdit = true;
        $list->PageSize = 500;
        $list->PageSizes["500"] = "500";
        $list->repeatHeadersEveryNthRow = 10;
        $list->InplaceEdit = true;

        $list->SQL = "
            SELECT
                bw.BalanceWatchID, bw.AccountID,
                u.FirstName as FirstName, u.LastName, u.Email,
                a.UserID,
                bw.TransferFromProviderID,
                a.ProviderID,
                bw.PointsSource, bw.ExpectedPoints,
                bw.StopReason, bw.CreationDate, bw.TransferRequestDate, bw.StopDate,
                TIMESTAMPDIFF(HOUR,bw.TransferRequestDate, bw.StopDate) AS ProcessingTime,
                $tranferRequestedField as TransferRequested, bw.Status, bw.Note,
                bw.SourceProgramRegion, bw.TargetProgramRegion
            FROM BalanceWatch bw
                JOIN Account a on a.AccountID = bw.AccountID
                JOIN Usr u on a.UserID = u.UserID
        ";
    }

    function GetFormFields(){
        $fields = parent::GetFormFields();
        unset($fields["FirstName"]);
        unset($fields["LastName"]);
        unset($fields["Email"]);
        unset($fields["TransferRequested"]);
        unset($fields["ProcessingTime"]);

        return $fields;
    }
}
