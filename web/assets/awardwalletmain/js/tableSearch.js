function isMatch(regex,_node){
    if(_node[0].match(regex) || _node[4].match(regex)){
        return true;
    }else{
        if(_node[2]){
            if(_node[3].match(regex))return true;
        }
    }
    return false;
}
function getRowContent(row){
    //var t=new RegExp('<[^>]+>','ig');
    var a=new Array();
    $(row.cells).each(function(i){
        a.push(this.innerHTML);
    });
    a.push(row.getAttribute('data-search'));
    a.push(row.getAttribute('data-url'));
    a.push(row.cells[0].getAttribute('data-owner'));
    a.push(row.className)
    return a;
}
function matchRow(regex,row,even){
    var t=new RegExp('<[^>]+>','ig');
    var a=getRowContent(row);
    a[0]=a[0].replace(t, '');
    //a[4]=a[4].replace(t, '');
    if(isMatch(regex,a)){
        row.cells[0].innerHTML=a[0].replace(regex, '<em class="matches">$1</em>');
        //row.cells[0].dataset.owner=a[4].replace(regex, '<em class="matches">$1</em>');
        $(row).removeClass('hidden');
        if(even){ $(row).addClass('even');}
        else $(row).removeClass('even');
        return true;
    }else{
        row.cells[0].innerHTML=a[0];
        $(row).addClass('hidden');
        return false;
    }
}
function textSearch(text){
    text=text||'';

    if(text.length){
        var ext=true;//text.trim().length > 0;
    }else{
        var ext=false;
    }
    var regex=RegExp('('+text+')','i');
    var t=$('#search-result').tBodies;
    for(var i=0;i< t.length;i++){
        var b=t[i],k=false,l=0;
        for(var j=0;j< b.rows.length;j++){
            var r= b.rows[j];
            if(!$(r).hasClass('search-program'))continue;
            if(matchRow(regex, r,k)){
                k=!k;l++;
                if(ext){
                    $(r).addClass('show-owner');
                }else{
                    $(r).removeClass('show-owner');
                }
            }else{
                $(r).removeClass('show-owner');
            }
        }
        if(l<1){
            //if(!$(b).hasClass('only-group'))
            $(b).addClass('search-hidden');
        }else{
            $(b).removeClass('search-hidden');
        }
    }
    $('#row-pop-up').css('display','');
}
