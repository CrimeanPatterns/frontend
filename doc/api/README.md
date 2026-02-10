как мне пересобрать?


Artem Puzakov [15:33] 
с помощью https://github.com/AwardWallet/swagger2html


new messages
[15:34] 
нужно этот проект стянуть в контейнер с frontend
затем композер
затем:
php ./run -s /www/awardwallet/doc/api/export.yml -d /www/doc_html/ -p
cp /www/doc_html/index.html /www/awardwallet/bundles/AwardWallet/MainBundle/Resources/views/ApiDocumentation/account_access.html.twig