#echo '---------------------------------------------------------------------' >> /var/log/www/rsycBackup.log
#echo `date +%d-%m-%Y' '%H:%M` ": rsync run awardwallet backup" >> /var/log/www/rsycBackup.log
rsync --delete -avzPO -e "ssh -i /home/veresch/.ssh/alexiPassLess.key" /www/awardwallet/web/images/uploaded/ veresch@test.itlogy.com:/www/awardwallet/web/images/uploaded
