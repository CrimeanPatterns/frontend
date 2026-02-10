function checkGroup(el){
    //var tb=document.getElementById('search-result').tBodies;
    var c=$('#'+el);//document.getElementById(el);
    //var l=$('label');//document.getElementsByTagName('label');
    var m=new RegExp('^'+el,'i');
    $('label').each(
        function(l){
            var n=l.htmlFor;
            if((n != el) && n.match(m)){
                var e=$('#'+n);//document.getElementById(n);
                if(c.checked !== e.checked){
                    //if(ll.onclick){
                    //    ll.click();
                    //}else{
                    e.click();
                    //}
                }
            }
        }
    );
    for(var i=0;i< l.length;i++){
        //if(l[i].type == 'checkbox'){
        //}
    }
}