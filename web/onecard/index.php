<?
require( "../kernel/public.php" );

Redirect(getSymfonyContainer()->get("router")->generate("aw_one_card"), 301);