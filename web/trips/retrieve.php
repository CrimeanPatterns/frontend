<?php
require '../kernel/public.php';

Redirect(getSymfonyContainer()->get("router")->generate("aw_trips_add"));

