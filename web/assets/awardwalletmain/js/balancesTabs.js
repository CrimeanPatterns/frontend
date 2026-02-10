function hashChange(h){
    if (location.hash === h)
        return;
    if (typeof history.replaceState === 'undefined') {
        var p=document.body.scrollTop;
        location.replace(h);
        document.body.scrollTop=p;
    } else {
        history.replaceState(null,null,h);
    }
}
function truth(){
    return true;
}
function iterateTable(t,f){
    t= t || $('#'+'search-result').get(0);
    f= f || truth;
    var total=0;
    for(var i=0; i<t.tBodies.length;i++){
        var b= t.tBodies[i], l= 0, u=null, ul= 0, last=true;
        for(var j=0; j< b.rows.length; j++){
            var r= b.rows[j];
            if(!$(r).hasClass('search-program')){
                if($(r).hasClass('user')){
                    if(u){
                        if(ul<1){
                            $(u).addClass('tab-hidden');
                        }else{
                            $(u).removeClass('tab-hidden');
                        }
                    }
                    u=r,ul=0;
                }
                if($(r).hasClass('subacc') || $(r).hasClass('error')){
                    if(last){
                        $(r).removeClass('tab-hidden');
                    }else{
                        $(r).addClass('tab-hidden');
                    }
                }
                continue;
            }
            if(f(r)){
                $(r).removeClass('tab-hidden');
                l++;ul++;last=true;
            }else{
                $(r).addClass('tab-hidden');
                last=false;
            }
        }
        if(l<1){
            $(b).addClass('tab-hidden');
        }else{
            $(b).removeClass('tab-hidden');
            total+=l;
        }
    }
    return total;
}
function switchBalancesTabs(e){
    if(!e){
        e=location.hash;
        e= e.replace('#','');
    }
    var n='all';
    if(e!==''){
        n= e.replace(new RegExp('^tab-','i'),'').toLowerCase();
    }
    //var bl=$('').get(0);
    $('.balance-tabs>li').each(function(l){
        if(this.id == 'tab-'+n){
            if($(this).hasClass('active'))return;
            $(this).addClass('active');
        }else{
            $(this).removeClass('active');
        }
    });
    var t=$('#search-result');
    if(t.length){
        t=t.get(0);
        switch (n){
            case 'all':
                iterateTable(t);
                $('#noitems-recent').addClass('hidden');
                $('#noitems-active').addClass('hidden');
                break;
            case 'recent':
                var total=
                iterateTable(t,function(r){
                    var d= r.getAttribute('data-recent');
                    return d<7;
                });
                if(total<1){
                    $('#noitems-recent').removeClass('hidden');
                }else{
                    $('#noitems-recent').addClass('hidden');
                }
                $('#noitems-active').addClass('hidden');
                break;
            case 'active':
                var total=
                iterateTable(t,function(r){
                    return r.hasAttribute('data-active');
                });
                if(total<1){
                    $('#noitems-active').removeClass('hidden');
                }else{
                    $('#noitems-active').addClass('hidden');
                }
                $('#noitems-recent').addClass('hidden');
                break;
        }
        hashChange(e?'#'+e:'');
    }
    return false;
}
$(document).ready(function(){
    switchBalancesTabs();
});
