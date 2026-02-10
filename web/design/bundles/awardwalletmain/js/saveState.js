function getParam(saved,name){
    saved=saved || '';
    var matched=saved.match(new RegExp(name+':([^&]*)&?'));
    if(matched){
        return matched[1];
    }else{
        return '';
    }
}
function putParam(saved,name,value){
    saved=saved || '';
    if(saved.length>0){
        var r=new RegExp('(('+name+'|updateTime):[^&]*&?)','g');
        saved=saved.replace(r,'');
        //saved=saved.replace('/&&+/g','&').replace('/&$/','');
        if(saved.length){
            if(saved=='&')saved='';
            else
            if(saved.charAt(saved.length-1)!='&')saved+='&';
        }
    }
    return saved+name+':'+value+'&updateTime:'+new Date().getTime();
}
function saveInput(e){
    var n= e.name || ('#'+ e.id);
    if(e.type=='checkbox'){
        saveParam(n, e.checked?1:0);
    }else
    if(e.type=='text'){
        saveParam(n, e.value);
    }else
    if(e.type=='radio'){
        if(e.checked){
            saveParam(n, e.value);
        }
    }

}
function readInput(e){
    var n= e.name || ('#'+ e.id);
    if(e.type=='checkbox'){
        e.checked=readParam(n)==1?true:false;
    }else
    if(e.type=='text'){
        e.value=readParam(n );
    }else
    if(e.type=='radio'){
        var v=readParam(n);
        if(e.value==v){
            e.checked=true;
        }
    }
}
function saveParam(name,val){
    Cookie.set('currentState',putParam(Cookie.get('currentState'),name,val));
}
function readParam(name){
    return getParam(Cookie.get('currentState'),name);
}
function saveAllParams(){
    var l=document.getElementsByTagName('input');
    for(var i=0;i< l.length;i++){
        saveInput(l[i]);
    }
}
function readAllParams(){
    var l=document.getElementsByTagName('input');
    for(var i=0;i< l.length;i++){
        readInput(l[i]);
    }
}
function checkChanged(last){
    if(!last){
        readAllParams();
    }else {
        var t=readParam('updateTime');
        if(last!=t)
            readAllParams();
    }
}
function checkParams(stop){
    stop=stop || false;
    var lastTime=0;
    var timerFunc=null;
    function runTimer(){
        checkChanged(lastTime);
        lastTime=readParam('updateTime');
        timerFunc=setTimeout(runTimer,1000);
    }
    if(stop){
        timerFunc=null;
    }else{
        runTimer();
    }
}