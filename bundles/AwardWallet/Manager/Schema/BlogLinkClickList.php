<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\Blog\BlogLinkClickSync;

/**
 * @property EmailCustomParam $Schema
 */
class BlogLinkClickList extends \TBaseList
{
    public function __construct($table, $fields)
    {
        parent::__construct($table, $fields, 'BlogLinkClickID');

        $fields = array_keys($this->Fields);

        if ($exitPos = array_search('Exit', $fields)) {
            $fields[$exitPos] = '`Exit`';
        }

        $this->SQL = 'select ' . implode(',', $fields) . ' from ' . $table;

        if (isset($_POST['sync'])) {
            exit(getSymfonyContainer()->get(BlogLinkClickSync::class)->sync() ? 'true' : 'false');
        }
    }

    public function DrawEmptyList()
    {
        parent::DrawEmptyList();
        $this->printSyncButton();
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        $this->printSyncButton();
    }

    private function printSyncButton()
    {
        echo '
        <script>
        function clickSync(obj) {
            $(obj).text("Processing...");
            $.post(location.href, {sync: true}, function(response) {
                if ("true" == response) {
                    return $(obj).text("Success, please refresh the page");
                }
                
                return $(obj).text("Failure");
            });
        }
        $("#content-title").append("<button class=\'btn\' href=\'#\' onclick=\'clickSync(this);\' type=\'button\' style=\'margin-left:3rem\'>SYNC</button>");
        </script>
        ';
    }
}
