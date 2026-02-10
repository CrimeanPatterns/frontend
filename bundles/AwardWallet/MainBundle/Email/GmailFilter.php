<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\Usr;

class GmailFilter
{
    public const TEMPLATE = <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<feed xmlns='http://www.w3.org/2005/Atom' xmlns:apps='http://schemas.google.com/apps/2006'>
	<title>Mail Filters</title>
	<id>tag:awardwallet.com,2023:filters:%id%</id>
	<updated>%updateDate%</updated>
	<entry>
		<category term='filter'></category>
		<title>Mail Filter</title>
		<id>tag:awardwallet.com,2023:filters:%id%</id>
		<updated>%updateDate%</updated>
		<content></content>
		<apps:property name='from' value='%fromList%'/>%toElement%
		<apps:property name='forwardTo' value='%forwardToAddress%'/>
		<apps:property name='sizeOperator' value='s_sl'/>
		<apps:property name='sizeUnit' value='s_smb'/>
	</entry>
</feed>
EOF;

    private EmailAddressManager $eaManager;

    public function __construct(EmailAddressManager $eaManager)
    {
        $this->eaManager = $eaManager;
    }

    public function getFilter(Usr $user, int $pos, string $alias, string $to): string
    {
        if (!empty($to)) {
            $to = str_replace(['"', '\'', '<', '>', '&'], '', $to);
        }
        $list = $this->eaManager->getList($pos);

        if (empty($list)) {
            return '';
        }
        $list = implode(' OR ', array_map(function ($s) {return '@' . $s; }, $list));
        $id = md5($user->getId() . ' - ' . $list);
        $updateDate = date('Y-m-d\TH:i:s\Z');
        $forwardTo = strtolower($user->getLogin()) . (!empty($alias) ? '.' . $alias : '') . '+f@email.awardwallet.com';
        $toElement = !empty($to) ? "\n\t\t<apps:property name='to' value='{$to}'/>" : "";

        return str_replace([
            '%id%',
            '%fromList%',
            '%updateDate%',
            '%forwardToAddress%',
            '%toElement%',
        ], [
            $id, $list, $updateDate, $forwardTo, $toElement,
        ], trim(self::TEMPLATE));
    }
}
