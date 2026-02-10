select 'creating database';

create user awardwallet;
set password for awardwallet = 'awardwallet';
create database awardwallet character set utf8 COLLATE utf8_general_ci;
grant all privileges on awardwallet.* to awardwallet;
grant file on *.* to awardwallet;

use awardwallet;

select 'importing dump';
source /tmp/dump.sql;

select 'deleting extra';
delete t from Trip t left join Account a on t.AccountID = a.AccountID where t.AccountID is not null and a.AccountID is null;
delete t from Trip t left join UserAgent ua on t.UserAgentID = ua.UserAgentID where t.UserAgentID is not null and ua.UserAgentID is null;

select 'adding dump date';
insert into Param(Name, Val) values ('lite_dump_date', now());

commit;

select 'creating vendor db';
create database vendor character set utf8 COLLATE utf8_general_ci;
grant all privileges on vendor.* to awardwallet;

use vendor;

select 'creating vendor structure';
CREATE TABLE `PasswordHash` (
  `Hash` char(40) NOT NULL,
  `LeakCount` int(11) NOT NULL,
  PRIMARY KEY (`Hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

select 'creating vendor data';
insert into PasswordHash(Hash, LeakCount) values(sha1('12345'), 1000);

commit;

select 'done';
