$(document).ready(
    function (){
        var s=$('.container_r .scrolled').eq(0);
        var e=$('.balance-toolbar',s).eq(0);
        var p= e.parent().height(e.outerHeight()).get(0);
        s.scroll(function(){
            var pos=$(p).offset(), h=0;
            if(pos.top-5<h + 43){
                if(!e.hasClass('fixed')){
                    e.addClass('fixed');
                    e.css({left:pos.left + 'px',width:p.clientWidth + 'px'});
                }
            }else{
                e.removeClass('fixed');
                e.css({left:'',width:''});
            }
        });
        $(window).resize(function(){
            if(e.hasClass('fixed')){
                e.css('width',p.clientWidth + 'px');
            }
        });
    }
);

