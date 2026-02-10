/etc/init.d/receivePlans restart >> /var/log/www/awardwallet/restart.log
cd /www/awardwallet/util
if [ ! $? -eq 0 ]; then
	exit 1
fi
