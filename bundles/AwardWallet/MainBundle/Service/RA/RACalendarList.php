<?php

namespace AwardWallet\MainBundle\Service\RA;

class RACalendarList extends \TBaseList
{
    public function __construct($table, $fields, $defaultSort = null, ?\Symfony\Component\HttpFoundation\Request $request = null)
    {
        parent::__construct($table, $fields, $defaultSort, $request);
        $this->SQL = /** @lang MySQL */ "
            SELECT RACalendar.*, Provider.ShortName FROM RACalendar LEFT JOIN Provider ON RACalendar.Provider = Provider.Code 
        ";
    }
}
