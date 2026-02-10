cache=../web/extension/lib.js

echo "" >$cache
echo "// jQuery cookie plugin" >>$cache
cat ../web/lib/3dParty/jquery/plugins/jquery.cookie.js >>$cache
