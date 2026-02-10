pagePopup=(function(){
    var activePopup,
        activeOnHide,
        activeShown=false,
        activeForm=false,
        afterCheck,
        faderEnabled=true;
    return {
        init:function(){
            if(!activePopup){
                activePopup=$('#stdPopup');
                if(activePopup.length<1){
                    return false;
                }
                activePopup.on('resize',this.center);
                $(window).on('resize',this.center);
            }
            return activePopup;
        },
        center:function(){
            var a=pagePopup.init();
            a.css('marginLeft',(-a.width() / 2)+"px");
            a.css('marginTop',(-a.height() / 2)+"px");
        },
        size:function(windowHeight, windowWidth){
            var a=pagePopup.init();
            a.css('min-height',(windowHeight|240)+'px');
            if (windowWidth !== undefined)
                a.css('min-width',windowWidth+'px');
        },
        show:function(onHide){
            var a=pagePopup.init();
            if(!activeShown){
                $('#faderNew').css('visibility','visible');
                a.css({'visibility':'visible'});
                pagePopup.center();
                activeShown=true;
            }
            if(onHide){
                activeOnHide=onHide;
            }else{
                activeOnHide=null;
            }

        },
        hide:function(){
            var a=pagePopup.init();
            if(activeShown){
                $('#faderNew').css('visibility','hidden');
                a.css('visibility','hidden');
                activeShown=false;
                if(activeOnHide){
                    activeOnHide.call();
                }
            }
        },
        wait:function(){
            //this.title('Wait please!');
            pagePopup.content('<div class="stdPopupWait"></div>');
        },
        disableClose:function(){
            $('.stdPopupClose',pagePopup.init()).css({'display':'none'});
        },
        enableClose:function(){
            $('.stdPopupClose',pagePopup.init()).css({'display':''});
        },
        faderClosable:function(enable){
            faderEnabled=enable;
        },
        hideByFader:function(){
            if(faderEnabled){
                pagePopup.hide();
            }
        },
        ajax:function(url,data,onDone){
            data=data||"";
            onDone=onDone||pagePopup.inner;
            //pagePopup.wait();
            $.ajax({
                type:"POST",
                url:url,
                data:data
            }).done(onDone);
        },
        title:function(text){
            var a=pagePopup.init();
            if(!text){
                return  $('#stdPopupTitle',a).html()
            }
            else {
                $('#stdPopupTitle',a).html(text);
            }
        },
        content:function(text){
            var a=pagePopup.init();
            var c=$('#stdPopupContent',a);
            pagePopup.enableClose();
            pagePopup.faderClosable(true);
            c.html(text);
        },
        addButton:function(text,action,closePopup,classes){
            classes=classes || "";
            closePopup=typeof closePopup != 'boolean'?true:closePopup;
            var c=$('#stdPopupContent',pagePopup.init());
            var b=$('div.buttons',c);
            if(b.length<1){
                c.append('<div class="buttons"></div>');
                b=$('div.buttons',c);
            }
            b= b.last();
            var matches=action.match(/^javascript:(.*)$/i);
            var event='',href='';
            if(matches){
                event=matches[1];
            }else{
                href=action;
            }
            if(closePopup){
                event='pagePopup.hide();'+event;
            }
            var html='<div class="btn-group"><a href="#"';
            if(href)html.replace('#',href);
            if(event)html+=' onclick="'+event+';return false;"';
            html+=' class="btn '+classes+'">'+text+'</a></div>'
            b.append(html);
        },
        inner:function(text,func){
            pagePopup.content(text);
            if(window.ajaxSendForm){
                ajaxSendForm.ondone=func;
            }
            if((c=$('#stdPopupContent form',pagePopup.init())).length){
                if(window.InputStyle){
                    InputStyle.init(c);
                }
                activeForm=c;
            }else{
                activeForm=false;
            }
        },
        form:{
            callback:null,
            init:function(receive){
                receive=receive||'noaction';
                if(typeof receive == 'string'){
                    this.callback=this[receive];
                }else{
                    this.callback = this['noaction'];
                    afterCheck=receive;
                }
                var c=false;
                if((c=$('#stdPopupContent form',activePopup)).length){
                    c.eq(0).on('submit',this.callback);
                    if(window.InputStyle){
                        InputStyle.init(c);
                    }
                    activeForm=c;
                }else{
                    activeForm=false;
                }
            },
            instance:false,
            'noaction':function(){
                if(afterCheck)
                    afterCheck(data);
            },
            send:function(){
                if(activeForm){
                    pagePopup.ajax(
                        activeForm.get(0).action,
                        activeForm.serialize()
                    );
                }
                return false;

            },
            get:function(e){
                return $(e||':first-child',activeForm).get(0);
            },
            errors:function(data){
                if(typeof data == 'string')
                    data = eval('('+data+')');
                //var f=pagePopup.form.instance;
                $('label',activeForm).each(function(i){
                    var label=this;//.get(0);

                    var input=$('#'+label.htmlFor).get(0);
                    var name=input.name;//label.innerText.replace(' *','');
                    var p= label.parentNode.parentNode;
                    if(data[name]){
                        $(p).addClass('error');
                        p.setAttribute('data-tooltip',data[name]);
                    }else{
                        $(p).removeClass('error');
                        p.setAttribute('data-tooltip','');
                    }
                })
                var m=$('#form_message',activePopup);
                if(data['form_message']){
                    var d=data['form_message'];
                    if(m.length<1){
                        $('div',activeForm).eq(0).append('<div id="form_message" class="message"></div>');
                        m=$('#form_message',activeForm);
                    }
                    if(d.success){
                        $(m).addClass('success');
                    }else{
                        $(m).removeClass('success');
                    }
                    m.html('<p>'+d.text+'</p>');
                }else{
                    if(m.length>0){
                        m.remove();
                    }
                }
                if(data.success){
                    afterCheck(data);
                }
            },
            check:function(){
                if(activeForm){
                    pagePopup.ajax(
                        activeForm.get(0).action,
                        activeForm.serialize(),
                        pagePopup.form.errors);
                }
                return false;
            }

        }

    };
    //return obj;
})();
function popupCenter(){
    pagePopup.center();
}

// if optional onHide parameter is provided, then function that is passed in it
// will be called when the popup window is closed
function popupShow(e, onHide){
    pagePopup.show(onHide);
}

function popupHide(){
    pagePopup.hide();
}
function popupWait(e, windowHeight, windowWidth){
    pagePopup.size(windowHeight, windowWidth);
    pagePopup.wait();
}
// windowHeight and windowWidth are optional
function popupAjax(e, url, windowHeight, windowWidth){
    stdPopupAjax(url, windowHeight, windowWidth);
}

function stdPopupTitle(title){
    pagePopup.title(title);
}

// title is optional
function stdPopupShow(title, onHide){
    pagePopup.title(title);
    pagePopup.show(onHide);
}

function stdPopupHide(){
    pagePopup.hide();
}

// windowHeight and windowWidth are optional
function stdPopupAjax(url, windowHeight, windowWidth){
    pagePopup.size(windowHeight, windowWidth);
    pagePopup.wait();
    pagePopup.ajax(url);
}

function stdPopupContent(e){
    var o = $('#'+e);
    //pagePopup.show();
    pagePopup.inner(o.html());
}
function stdPopupFormCheck(){
    var xmlhttp;
    var f = $('#stdPopupContent form').eq(0);
    $.ajax({
        type:"POST",
        url: f.get(0).action,
        data:f.serialize()
    }).done(function(data){
            if(typeof data == 'string')
                data = eval('('+data+')');
            $('label',f).each(function(i){
                var label=this;//.get(0);

                var input=$('#'+label.htmlFor).get(0);
                var name=input.name;//label.innerText.replace(' *','');
                var p= label.parentNode.parentNode;
                if(data[name]){
                    $(p).addClass('error');
                    p.setAttribute('data-tooltip',data[name]);
                }else{
                    $(p).removeClass('error');
                    p.setAttribute('data-tooltip','');
                }
            })
            var m=$('#form_message');
            if(data['form_message']){
                var d=data['form_message'];
                if(m.length<1){
                    $('div',f).eq(0).append('<div id="form_message" class="message"></div>');
                    m=$('#form_message');
                }
                if(d.success){
                    $(m).addClass('success');
                }else{
                    $(m).removeClass('success');
                }
                m.html('<p>'+d.text+'</p>');
            }else{
                if(m.length>0){
                    m.remove();
                }
            }
        });
    return false;
}
