<?
require( "../kernel/public.php" );
Redirect(getSymfonyContainer()->get("router")->generate("aw_add_agent"));
