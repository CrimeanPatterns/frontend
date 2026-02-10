<?php

use AwardWallet\MainBundle\Service\InAppPurchase\AbstractConsumable;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Connector;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlayProvider;

$schema = "AdminCart";

require __DIR__ . "/../start.php";

require __DIR__ . "/../../lib/cart/public.php";

$cartId = intval($_GET["CartID"]);
$container = getSymfonyContainer();
$cartRep = $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);

$cart = $cartRep->find($cartId);
/** @var \AwardWallet\MainBundle\Entity\Usr $user */
$user = $cart->getUser();

if (!$cart) {
    drawHeader("Cart $cartId contents");
    $Interface->DrawMessageBox('Cart not found');
    drawFooter();

    exit;
}

$ajax = new AjaxRequestHandler(false);

if ($ajax->isAjax()) {
    header('Content-Type: application/json');

    function decodeIosReceipt($receipt)
    {
        global $container;
        $connector = $container->get(Connector::class);
        $useSandbox = $container->getParameter("aw.mobile.iap_apple_sandbox");
        $secret = $container->getParameter("aw.mobile.iap_apple_secret");
        $json = $connector->sendRequest(
            $useSandbox ? AppleProvider::ENDPOINT_SANDBOX : AppleProvider::ENDPOINT_PRODUCTION,
            json_encode([
                'receipt-data' => strval($receipt),
                'password' => $secret,
            ])
        );
        $json = json_decode($json);

        if (
            !$useSandbox
            && property_exists($json, 'status')
            && $json->status == AppleProvider::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION
        ) {
            $json = $connector->sendRequest(
                AppleProvider::ENDPOINT_SANDBOX,
                json_encode([
                    'receipt-data' => strval($receipt),
                    'password' => $secret,
                ])
            );
            $json = json_decode($json);
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }
    $ajax
        ->addAction('ios-app-receipt', function () use ($user) {
            if ($user && !empty($user->getIosReceipt())) {
                return [
                    "content" => "
                    <a target='_blank' href='https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ValidateRemotely.html'>Validating Receipts With the App Store</a> <br>
                    <pre>" . decodeIosReceipt($user->getIosReceipt()) . "</pre>",
                ];
            } else {
                return ["content" => "Empty app receipt!"];
            }
        })->addAction('ios-transaction-receipt', function () use ($cart) {
            if (!empty($cart->getPurchaseToken())) {
                return [
                    "content" => "
                    <a target='_blank' href='https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ValidateRemotely.html'>Validating Receipts With the App Store</a> <br>
                    <pre>" . decodeIosReceipt($cart->getPurchaseToken()) . "</pre>",
                ];
            } else {
                return ["content" => "Empty transaction receipt!"];
            }
        })->addAction('android', function () use ($cart, $container) {
            $provider = $container->get(GooglePlayProvider::class);

            if ($productId = $provider->getPlatformProductIdByCart($cart)) {
                if (AbstractConsumable::isConsumable($productId)) {
                    try {
                        $product = $provider->getGooglePlayProduct($productId, $cart->getPurchaseToken());

                        return [
                            "content" => "
                             <a target='_blank' href='https://developers.google.com/android-publisher/api-ref/purchases/products'>Product Purchase</a> <br>
                            <pre>" . var_export($product, true) . "</pre>",
                        ];
                    } catch (\Exception $e) {
                        return [
                            "content" => "<pre>Exception: " . $e->getMessage() . "</pre>",
                        ];
                    }
                } else {
                    try {
                        $subscription = $provider->getGooglePlaySubscription($productId, $cart->getPurchaseToken());

                        return [
                            "content" => "
                             <a target='_blank' href='https://developers.google.com/android-publisher/api-ref/purchases/subscriptions'>In-app Subscriptions</a> <br>
                            <pre>" . var_export($subscription, true) . "</pre>",
                        ];
                    } catch (\Exception $e) {
                        return [
                            "content" => "<pre>Exception: " . $e->getMessage() . "</pre>",
                        ];
                    }
                }
            } else {
                return ["content" => "Unknown product id!"];
            }
        })
        ->handle();
} else {
    drawHeader("Cart $cartId contents");

    $objCart->OpenByID($cartId);
    $objCart->CalcTotals();
    $objCartManager = new TCartManager();

    $Interface->drawSectionDivider("Order information"); ?>
    <table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="100%">
        <tr>
            <td width="150">Client</td>
            <td><?php echo $user ? htmlspecialchars($user->getFullName()) : ""; ?></td>
        </tr>
        <tr>
            <td>Pay Date</td>
            <td><?php echo $cart->getPaydate()->format("Y-m-d H:i:s"); ?></td>
        </tr>
        <tr>
            <td>Payment Type</td>
            <td><?php echo $arPaymentTypeName[$cart->getPaymenttype()]; ?></td>
        </tr>
        <tr>
            <td>Phone 1</td>
            <td><?php echo $user ? $user->getPhone1() : ""; ?></td>
        </tr>
        <tr>
            <td>Email</td>
            <td><?php echo $user ? $user->getEmail() : ""; ?></td>
        </tr>
    </table>
    <?php
    echo "<br>";
    $Interface->drawSectionDivider("Ordered items");
    $objCartManager->DrawContents();

    if (ArrayVal($objCart->Fields, "Notes") != "") {
        echo "<br>";
        $Interface->drawSectionDivider("Special Notes");
        echo $objCart->Fields["Notes"];
        echo "<br>";
    }
    echo "<br>";
    $Interface->drawSectionDivider("Shipping address");

    if (!empty($cart->getShipzip())) {
        ?>
        <table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="100%"
               bgcolor='<?php echo FORM_BODY_COLOR; ?>'>
            <tr>
                <td width="150">Full Name</td>
                <td><?php echo htmlspecialchars($cart->getShipfirstname() . " " . $cart->getShiplastname()); ?></td>
            </tr>
            <tr>
                <td>Address 1</td>
                <td><?php echo $cart->getShipaddress1(); ?></td>
            </tr>
            <tr>
                <td>Address 2</td>
                <td><?php echo $cart->getShipaddress2(); ?></td>
            </tr>
            <tr>
                <td>City</td>
                <td><?php echo $cart->getShipcity(); ?></td>
            </tr>
            <tr>
                <td>State</td>
                <td><?php echo $cart->getShipstate() ? $cart->getShipstate()->getName() : ""; ?></td>
            </tr>
            <tr>
                <td>Country</td>
                <td><?php echo $cart->getShipcountry() ? $cart->getShipcountry()->getName() : ""; ?></td>
            </tr>
            <tr>
                <td>Zip</td>
                <td><?php echo $cart->getShipzip(); ?></td>
            </tr>
        </table>
        <br>
        <?php
    } else {
        echo "I will pickup my order from the " . SITE_NAME . " store<br><br>";
    }

    if (!empty($cart->getBillfirstname())) {
        $Interface->drawSectionDivider("Billing address"); ?>
        <table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="100%">
            <tr>
                <td width="150">Full Name</td>
                <td><?php echo htmlspecialchars($cart->getBillfirstname() . " " . $cart->getBilllastname()); ?></td>
            </tr>
            <tr>
                <td>Address 1</td>
                <td><?php echo $cart->getBilladdress1(); ?></td>
            </tr>
            <tr>
                <td>Address 2</td>
                <td><?php echo $cart->getBilladdress2(); ?></td>
            </tr>
            <tr>
                <td>City</td>
                <td><?php echo $cart->getBillcity(); ?></td>
            </tr>
            <tr>
                <td>State</td>
                <td><?php echo $cart->getBillstate() ? $cart->getBillstate()->getName() : ""; ?></td>
            </tr>
            <tr>
                <td>Country</td>
                <td><?php echo $cart->getBillcountry() ? $cart->getBillcountry()->getName() : ""; ?></td>
            </tr>
            <tr>
                <td>Zip</td>
                <td><?php echo $cart->getBillzip(); ?></td>
            </tr>
        </table>
        <?php
    }

    if (!empty($cart->getCreditcardnumber())) {
        echo "<br>";
        $Interface->drawSectionDivider("Credit Card Info"); ?>
        <table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="100%">
            <tr>
                <td width="150">Credit Card Type</td>
                <td><?php echo $cart->getCreditcardtype(); ?></td>
            </tr>
            <tr>
                <td>Credit Card Number</td>
                <td><?php echo $cart->getCreditcardnumber(); ?></td>
            </tr>
        </table>

        <?php
    }

    echo <<<HTML
        <script>
            var detailsWindow = null;
            function getDetails(action, button) {
                $(button).attr("disabled", "disabled");
                $.get("/manager/cart/contents.php?CartID=$cartId&action=" + action, function(data) {
                    $(button).removeAttr("disabled");
                    if (detailsWindow != null && !detailsWindow.closed) {
                        detailsWindow.close();
                    }
                    detailsWindow = window.open("", "Details", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1024, height=600");
                    detailsWindow.document.write(data.content);
                    detailsWindow.focus();
                });
            }           
        </script>
HTML;

    switch ($cart->getPaymenttype()) {
        case \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_APPSTORE:
            if (isset($user) && !empty($user->getIosReceipt())) {
                echo "<button style='margin: 5px;' onclick='getDetails(\"ios-app-receipt\", this);'>Show Apple Appstore receipt</button>";
            }

            if (!empty($cart->getPurchaseToken())) {
                echo "<button style='margin: 5px;' onclick='getDetails(\"ios-transaction-receipt\", this);'>Show Apple Appstore transaction receipt</button>";
            }

            break;

        case \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_ANDROIDMARKET:
            if (!empty($cart->getPurchaseToken()) && $cart->isAwPlusSubscription()) {
                echo "<button style='margin: 5px;' onclick='getDetails(\"android\", this);'>Show android transaction receipt</button>";
            }

            break;
    }

    drawFooter();
}
