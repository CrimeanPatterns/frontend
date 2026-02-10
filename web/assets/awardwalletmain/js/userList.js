function usersResize(){
    var l=getElementsByClass('left-column')[0], lh= l.clientHeight;
    var s=getElementsByClass('scroll-static',l)[0], sh= s.clientHeight;
    var h=0;
    // var c=cssSelect('div',s);
    // for(var i= c.length;)
    DOMWalk(function(e){
        if(e.parentNode && e.parentNode == s){
            var l=e.clientHeight;
            if(l)h+= l;
        } return false},s);
    var u=getElementsByClass('user-tabs',s)[0], uh=u.clientHeight;
    var e= u.getElementsByTagName('ul')[0], eh= e.clientHeight;
    var minsh=lh - 100;
    var maxsh=h - uh + eh;
    var d=0;
    if(h>minsh){
        d = h - minsh;
    }else if(h<maxsh){
        d = h - minsh;
        if(d>0)d=0;
    }
    if(d != 0){
        d=((d= Math.floor(( uh - d )/38)) <3 ? 3:d) * 38;
        var dh=uh-d;
        if(u.clientHeight != d){
            sh=h - dh + 'px';
            s.style.height = sh;
            d+='px';
            u.style.height  = d;
            u=getElementsByClass('scroll-box',l)[0];
            u.style.marginTop = '-'+sh;
            u.style.paddingTop = sh;
        }
    }else if(sh > h){
        s.style.height = h + 'px';
    }
}
function mainResize(){
    var l=getElementsByClass('main')[0], lh= l.clientHeight;
    var s=getElementsByClass('scroll-static',l)[0], sh= s.clientHeight;
    var h=0;
    DOMWalk(function(e){
        if(e.parentNode && e.parentNode == s){
            var l=e.clientHeight;
            if(l)h+= l;
        } return false},s);
    var d= sh!=h?h:0;
    if(d != 0){
            sh=d + 'px';
            s.style.height = sh;
            var u=getElementsByClass('scroll-box',l)[0];
            u.style.marginTop = '-'+sh;
            u.style.paddingTop = sh;
    }
}
function allResize(){
    usersResize();
    mainResize();
}
Event.add(window,'load',function(){
    Event.add(window,'resize',allResize);
    allResize();
});
