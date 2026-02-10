<?php

require_once dirname(__FILE__) . "/../kernel/public.php";
AuthorizeUser();
requirePasswordAccess();

// enter request context, for twig using
$container = getSymfonyContainer();

// check generic manager interface access
$allowedSchemas = $container->get("aw.security.role_manager")->getAllowedSchemas();

if (!in_array('index', $allowedSchemas)) {
    $Interface->DiePage("Access denied");
}

// determine schema name
if (!isset($schema)) {
    $schema = ArrayVal($_GET, 'Schema', 'index');
}

if ($_SERVER['SCRIPT_NAME'] == '/manager/delete.php') {
    $schema = ArrayVal($_POST, 'Schema');

    if (!schemaAccessAllowed($schema)) {
        $schema = $schema . ".Delete";
    }
}

// check specific role
if (!schemaAccessAllowed($schema)) {
    $Interface->DiePage("Access denied to schema {$schema}");
}

// load controller to draw header / footer, menu
$controller = $container->get(\AwardWallet\MainBundle\Controller\Manager\LayoutController::class);

/**
 * @param string $title - browser page title
 * @param string|null $contentTitle - rendered as h1 before content. Pass '' to hide contentTitle. if null - will be rendered same as pageTitle
 */
function drawHeader(string $title, ?string $contentTitle = null)
{
    global $controller;
    StickToMainDomain();
    AuthorizeUser();
    echo $controller->headerAction($title, $contentTitle)->getContent();
}

function drawFooter()
{
    global $Connection, $Interface, $controller;

    if (isset($Connection) && $Connection->Tracing && isset($_GET['SQLTrace'])) {
        $Connection->ShowTraceData();
    }

    if (isset($Interface) && sizeof($Interface->FooterScripts) > 0) {
        echo "<script>
		\$(document).ready(function(){
			" . implode("\n", $Interface->FooterScripts) . "\n
			activateDatepickers('active');
		});
		</script>";
    }

    echo $controller->footerAction()->getContent();
}
