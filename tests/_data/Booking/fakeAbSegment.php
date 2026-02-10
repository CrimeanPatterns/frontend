<?php

return [
    'DepDateFrom' => date('Y-m-d H:i:s', strtotime('+10 day')),
    'DepDateTo' => date('Y-m-d H:i:s', strtotime('+10 day')),
    'DepDateIdeal' => date('Y-m-d H:i:s', strtotime('+10 day')),
    'ReturnDateFrom' => date('Y-m-d H:i:s', strtotime('+15 day')),
    'ReturnDateTo' => date('Y-m-d H:i:s', strtotime('+15 day')),
    'ReturnDateIdeal' => date('Y-m-d H:i:s', strtotime('+15 day')),
    'Priority' => 1,
    'RoundTrip' => 1,
    'RequestID' => null,
    'Dep' => 'Perm',
    'Arr' => 'Moskow',
];
