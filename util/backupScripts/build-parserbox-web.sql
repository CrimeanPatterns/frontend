select 'creating database';

create user awardwallet;
set password for awardwallet = password('awardwallet');
create database awardwallet character set utf8 COLLATE utf8_general_ci;
grant all privileges on awardwallet.* to awardwallet;
grant file on *.* to awardwallet;

use awardwallet;

select 'importing dump';
source /tmp/dump.sql;

commit;

select 'done';
