<?php
require "../kernel/public.php";

$url = getSymfonyContainer()->get("router")->generate("aw_timeline");
if(!empty($_GET['UserAgentID']) && $_GET['UserAgentID'] != 'My')
    $url .= '#' . $_GET['UserAgentID'];
Redirect($url);

