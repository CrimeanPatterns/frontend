/*/ onload -> go to hash
$(document).ready(function(){
    var id = window.location.hash.replace( /#/, '' );;
    if (id.length){
        toggleEntry(id)
    }
    // remove anchor
    $('a.remove-me').remove();
});

// Show/hide category
function toggleCategory(id){
    var entry = $('#faqCategory'+id);
    // entry.find('div.questions').stop();
    if (entry.find('header').is('.closed')){
        entry.find('header').removeClass('closed');
        entry.find('div.questions').slideDown('fast');
    }else{
        entry.find('header').addClass('closed');
        entry.find('div.questions').slideUp('fast');
        entry.find('.entry').removeClass('toggled');
        entry.find('dd').hide();
    }
}

// Show/hide question
function toggleEntry(id){
    var entry = $('#faqEntry'+id);
    // entry.find('dd').stop();
    if (entry.is('.toggled')){
        entry.removeClass('toggled');
        entry.find('dd').slideUp('fast');
    }else{
        // show qa
        entry.addClass('toggled');
        entry.find('dd').slideDown('fast');
        // #link
        window.location.hash = '#'+id;
    }
}*/

function toggleBlock(e){
    var el=$(e='#'+e);
    if(el.length){
        el.toggleClass('open');
        var hash=el.hasClass('open')?e:'#';
        //if (typeof history.replaceState === 'undefined') {
            var p=document.body.scrollTop;
            location.replace(hash);
            document.body.scrollTop=p;
        //} else {
        //    history.replaceState(null,null,hash);
        //}
    }
    //var ev=window.event;
    //if(ev.preventDefault){
    //    ev.preventDefault();
    //}
    return false;
}

//var browser = function() {
//    var ua = navigator.userAgent, gecko = /Gecko\//.test(ua) ? ua.match(/; rv:1\.(\d+?)\.(\d)/) : 0,
//        webkit = /AppleWebKit/.test(ua), safari = webkit && /Safari\//.test(ua),
//        ie = 0 /*@cc_on + @_jscript_version * 10 % 10 @*/;
//    return {
//        ie: ie >= 5 ? ie : 0,
//        gecko: gecko ? '1.' + gecko.slice(1).join('.') : 0,
//        firefox: gecko ? (gecko[1] == 9 ? 3 : gecko[1] == 8 && gecko[2] > 0 ? 2 : 0) : 0,
//        opera: window.opera && opera.version ? opera.version()[0] : 0,
//        webkit: webkit ? ua.match(/AppleWebKit\/(\d+?\.\d+?\s)/)[1] : 0,
//        safari: safari && /Version\//.test(ua) ? ua.match(/Version\/(\d{1})/)[1] : 0,
//        chrome: safari && /Chrome\//.test(ua) ? ua.match(/Chrome\/(\d+?\.\d)/)[1] : 0
//    };
//}();
$(document).ready(
    function(){
        var h=location.hash;
        if(h.replace('#','')){
            //h;
            var el=$(h);
            if(el){
                $(el).toggleClass('open');
            }
        }
    }
);

