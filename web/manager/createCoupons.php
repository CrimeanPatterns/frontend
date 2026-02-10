<?php

$schema = "AdminCoupon";

require "start.php";

require_once "$sPath/kernel/TForm.php";

require_once "$sPath/schema/AdminCoupon.php";

drawHeader("Create coupons");

$schema = new TAdminCouponSchema();
$schemaFields = $schema->GetFormFields();
unset($schemaFields['Code']);
unset($schemaFields['UserID']);

$fields = array_merge([
    "Prefix" => [
        "Type" => "string",
        "Size" => 20,
        "Required" => true,
        "Value" => "MILEPOINT-",
        "Database" => false,
    ],
    "CodeSize" => [
        "Type" => "integer",
        "Required" => true,
        "Value" => 10,
        "Min" => 1,
        "Database" => false,
    ],
    "CodeMin" => [
        "Type" => "string",
        "Required" => true,
        "Value" => 'A',
        "Size" => 1,
        "Database" => false,
    ],
    "CodeMax" => [
        "Type" => "string",
        "Required" => true,
        "Value" => 'Z',
        "Size" => 1,
        "Database" => false,
    ],
    "Count" => [
        "Type" => "integer",
        "Required" => true,
        "Min" => 1,
        "Database" => false,
    ],
    "Generate" => [
        "Type" => "string",
        "Database" => false,
        "Required" => true,
        "Value" => "Code",
        "Options" => ["Code" => "Code", "Link" => "Link"],
    ],
], $schemaFields);
$fields['MaxUses']['Value'] = 1;
$fields['FirstTimeOnly']['Value'] = 0;
$fields['EndDate']['Value'] = date(DATE_FORMAT, strtotime("+1 year"));

$form = new TForm($fields);
$form->SubmitButtonCaption = "Generate";
$form->ButtonsAlign = "left";

if ($form->IsPost) {
    $form->Check();
}
echo $form->HTML();

if ($form->IsPost && !isset($form->Error) && $_POST['DisableFormScriptChecks'] != '1') {
    echo '<textarea rows="20" cols="80">';
    $prefix = $form->Fields['Prefix']['Value'];
    $connection = getSymfonyContainer()->get("database_connection");
    $form->CalcSQLValues();
    $sql = "";
    $registerLinkTemplate = getSymfonyContainer()->get("router")->generate("aw_register", ["Code" => "CODETEMPLATE"], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

    $values = [
        'Code' => "'CODETEMPLATE'",
    ];

    foreach ($form->Fields as $fieldName => $field) {
        if ($field['Database']) {
            $values[$fieldName] = $field['SQLValue'];
        }
    }
    $couponInsertTemplate = InsertSQL("Coupon", $values);

    foreach ($form->Fields["Items"]["Manager"]->SelectedOptions as $option) {
        $option["CouponID"] = "CouponID";
        $s = InsertSQL("CouponItem", $option);
        $s = str_replace("values(", "select ", $s);
        $s = substr($s, 0, -1) . " from Coupon where Code = 'CODETEMPLATE'";
        $couponInsertTemplate .= ";\n" . $s;
    }

    $connection->beginTransaction();

    for ($n = 0; $n < intval($form->Fields['Count']['Value']); $n++) {
        $code = $form->Fields['Prefix']['Value'] . RandomStr(ord($form->Fields['CodeMin']['Value']), ord($form->Fields['CodeMax']['Value']), $form->Fields['CodeSize']['Value']);

        if ($form->Fields['Generate']['Value'] == 'Code') {
            echo $code . "\n";
        } else {
            echo str_replace("CODETEMPLATE", $code, $registerLinkTemplate) . "\n";
        }

        $sql .= str_replace("CODETEMPLATE", addslashes($code), $couponInsertTemplate) . ";\n";

        if (strlen($sql) > 100000) {
            $connection->executeStatement($sql);
            $sql = "";
            $connection->commit();
            $connection->beginTransaction();
        }
    }

    if ($sql !== "") {
        $connection->executeStatement($sql);
        $connection->commit();
    }

    echo '</textarea>';
}

drawFooter();
