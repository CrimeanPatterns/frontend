
var lockAjax=false;
function getAllRows(container){
    var a=new Array();
    $('#'+container+' tr.search-program').each(function(j){
                a.push(this);
    });
    return a;
}
function getContent(collection,_class){
    var a=new Array();
    $(collection).filter('.'+_class).each(function(i){
        var a1=new Array();
        $(this.childNodes).each(function(j){
            var h= this.innerHTML;
            a1.push(h);
        })
        a1.push(this.childNodes[0].getAttribute('data-type'));
        a.push(a1);
    });
    return a;
}
function fireSort(order){
    var _column=0;
    var _order=0;
    var criteria=getSearchCriteria();
    var re=order.match(new RegExp('^c(\\d+)o(\\d+)([a-z])','i'));
    var t=new RegExp('<[^>]+>','ig');
    function sortFunc(i1,i2){
        var _i1=i1[_column].replace(t,'');
        var _i2=i2[_column].replace(t,'');
        if(_i1=='—')_i1=0;
        if(_i2=='—')_i2=0;
        if(_i1<_i2)return _order?1:-1;
        if(_i1>_i2)return _order?-1:1;
        return 0;
    }
    function sortResults(container){
        if(!lockAjax)lockAjax=true;
        else return;
        var l=container.rows;
        var a=new Array();
        for(var i=0;i< l.length;i++){
            if(l[i].className.search('search-program')<0)continue;
            a.push(getRowContent(l[i]));
        }
        a= a.sort(sortFunc);
        var ii=0;
        var jj=false;
        for(i=0;i< l.length;i++){
            if(l[i].className.search('search-program')<0)continue;
            var r=l[i];
            var a1=a[ii];ii++;
            for(var j=0;j< r.cells.length;j++){
                r.cells[j].innerHTML=a1[j];
            }
            r.setAttribute('data-search',a1[j++]);
            r.setAttribute('data-url',a1[j++]);
            r.cells[0].setAttribute('data-owner',a1[j++]);
            r.className=a1[j];
            if(a1[j].search('hidden')<0){
                if(jj){
                    $(r).addClass('even');
                }else{
                    $(r).removeClass('even');
                }
            }
    }
        lockAjax=false;
    }
    if(re){
        _column=re[1]-1;
        _order=re[2]==0?false:true;
        var f=document.forms['addAccount'];
        for(var i=0;i< f.length;i++){
            var _i=f[i];
            if(_i.nodeName!='INPUT')continue;
            if(_i.name.search('search-sort')>=0){
                if(_i.value.search('c'+re[1]+'o'+re[2])>=0){
                    _i.checked=true;
                }
            }
        }

        var _group=re[3].toUpperCase();
        var b=document.getElementById('search-result').tBodies;
        for(var i=0;i< b.length;i++){
            //if(b[i].dataset['type']==_group){
                sortResults(b[i]);
            //    break;
            //}
        }
        document.getElementById('row-pop-up').style.display='';
    }
}
function getSearchCriteria(){
    var f=document.forms['addAccount'];
    var _type='';
    var _text='';
    for(var i=0;i< f.length;i++){
        if(f[i].name=='search-tabs'){
            if(f[i].checked){
                _type=f[i].value;
            }
        }else
        if(f[i].name=='search-text'){
            _text=f[i].value;
        }
    }
    return {
        type:_type,
        text:_text,
        regex:new RegExp('('+_text+')','i')
    }
}
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
    for(var i=0;i< row.cells.length;i++){
        var h= row.cells[i].innerHTML;
        a.push(h)//.replace(t,''));
    }
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
    var t=$('#search-result');
    t=t.get(0).tBodies;
    $(t).each(function(i){
        var k=false,l=0;
        $(this.rows).each(function(j){
            if($(this).hasClass('search-program')){
                if(matchRow(regex, this,k)){
                    k=!k;l++;
                    if(ext){
                        $(this).addClass('show-owner');
                    }else{
                        $(this).removeClass('show-owner');
                    }
                }else{
                    $(this).removeClass('show-owner');
                }
            }
        });
        if(l<1){
            //if(!$(b).hasClass('only-group'))
            $(this).addClass('search-hidden');
        }else{
            $(this).removeClass('search-hidden');
        }

    });
    $('#row-pop-up').css('display','');
}
function groupSelect(el){
    var tag=el.value;
    $($('#search-result').get(0).tBodies).each(function(i){
        if(tag.indexOf(this.getAttribute('data-type'))>=0){
            $(this).removeClass('hidden');
            if(tag.length>1){
                $(this).removeClass('only-group');
            }else{
                $(this).addClass('only-group');
            }
            $(this.rows).filter('.group-tab').each(function(j){
                if(tag.length>1){
                    $(this).removeClass('hidden');
                }else{
                    $(this).addClass('hidden');
                }
            });
        }else{
            $(this).addClass('hidden').removeClass('only-group');
        }
    });
    $('#row-pop-up').css('display','');
}
function checkRadio(el){
    for(var l=el.nextSibling;l && !l.firstChild;l= l.nextSibling);
    l= l.getElementsByTagName('input');
    for(var i=0;i< l.length;i++){
        e=l[i];
        if(!e.checked){
            e.checked=true;
            fireSort(e.value);
            break;
        }
    }
}

function pushUpRow(e){
    var elem=document.getElementById('row-pop-up');
    if(!elem){
        elem=document.createElement('a');
        elem.id='row-pop-up';
        document.body.appendChild(elem);
        $(elem).on('mouseout',function(){elem.style.display='';return false;});
    }
    elem.innerHTML='';
    elem.href=e.getAttribute('data-url');
    var pos=$(e).offset();
    $(elem).css({
        top: pos.top - 1 +'px',
        left: pos.left - 10  +'px',
        display:'block',
        width:(e.clientWidth? e.clientWidth : e.offsetWidth)+14+'px'
    });
    $(e.cells).each(function(i){
        var c=document.createElement('div');
        c.style.width= (this.clientWidth? this.clientWidth : this.offsetWidth)+(i==0?10:1)+'px';
        c.innerHTML=this.innerHTML;
        var o;
        if(o=this.getAttribute('data-owner')){
            c.setAttribute('data-owner',o);
        }
        elem.appendChild(c);
    });
}
function pushDownRow(){
    var e=document.getElementById('row-pop-up');
    var ev=window.event;
    var h=ev.pageY - e.offsetTop;
    var w=ev.pageX - e.offsetLeft;
    if(h<0 || w<0){
        e.style.display='';
    }else {
        if(h> e.clientHeight || w> e.clientWidth){
            e.style.display='';
        }
    }
    return false;
}