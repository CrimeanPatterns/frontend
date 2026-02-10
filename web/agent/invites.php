<?
require( "../kernel/public.php" );

Redirect(getSymfonyContainer()->get("router")->generate("aw_user_invites"));
