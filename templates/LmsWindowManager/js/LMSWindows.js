function LMSWindowsManager(p)
{
    this.objName=p.objName;
    this.funcHooks=new Array();
    this.funcHooksN=new Array();
    this.funcHooksJS=new Array();
    this.funcHooksUri=new Array();
    this._windowClick=false;
    this._activeWindow='';
    var me=this;
    this._zindex=10;
    this._stack=0;
    this._stackFunc=new Array();
    this._stackFuncId=new Array();
    this.Error=false;

    this.SetStatus=function()
        {
            if(document.getElementById('lmswstatus'))
                document.getElementById('lmswstatus').style.height=document.getElementById('lmswstatusinside').offsetHeight+'px';        
        }
    this.CT=function(jak)
        {
            a=this.QuestionLink;
            if(jak)
                {
                    if (a.match(/\?.*/))
                        document.location.href=a;
                    else
                        eval(a);
                }
            this.QuestionWindow.HidePanel();
            document.getElementById('lmswtlo').style.display='none';
        }
    this.Question=function(q,a)
        {
            this.QuestionLink=a; 
            t="<table  width=100% cellspacing=15 cellpadding=8 border=0><tr class=lmswtitle><td colspan=3><B>Pytanie</B></td></tr><tr class=lucid><td colspan=3><B>"+q+"</B></td></tr><TR class=lucid><td nowrap class='text-center hand' onclick=\"LmsWindowsManager.CT(true);\"><div class=statusokLMS >TAK</div></td><td></td><td nowrap class=\"text-center hand\" onclick=\"LmsWindowsManager.CT(false);\"><div class=alertLMS>NIE</div></td></tr></table>";
            document.getElementById(this.QuestionWindow.baseWindow).innerHTML=t;
            elO=document.getElementById(this.QuestionWindow.baseWindow);        
            elT=document.getElementById('lmswtlo');
            
            y=window.innerHeight/2-150;
            x=window.innerWidth/2-20;
            elO.style.top=y+'px';
            elO.style.left=x+'px';
            elO.style.position='fixed';
            elO.style.zIndex=100099;

            this.QuestionWindow.ShowPanel(this.QuestionWindow.baseWindow);
            elT.style.display='block';
            elT.style.zIndex=100098;
        }


    this.progress=function(k,w)
        {
            if(k)
                this._stack++;
            else
                if(this._stack>0)
                    this._stack--;
            if(document.getElementById('lmswprogressbar'))
                document.getElementById('lmswprogressbar').style.display=this._stack>0 ? '' : 'none';
        }
    this.progresslog=function(k,d,w)
        {
            if(!w)
                return;
                
            var jest=false;
            for(var i=0;i<this._stackFuncId.length;i++)
                if(this._stackFuncId[i]==w)
                    {
                        jest=i;
                        break;
                    }
            if(!jest)
                this._stackFuncId[this._stackFuncId.length]=w;
            this._stackFunc[w]=k;
            
            var c=0;
            var data='';
            d=document.getElementById('lmswlog');
            d.innerHTML='';
            datan=new Array();
            for(var i=0;i<this._stackFuncId.length;i++)
            {
                if(this._stackFunc[this._stackFuncId[i]]==true)
                        {
                            c++;
                            datan[datan.length]=this._stackFuncId[i]+'';
                        }
            }
            x=c-4;
            if (x<0)
                x=-1;
            for(i=c-1;i>x;i--)
                d.innerHTML=d.innerHTML+'<BR>'+datan[i];
        }

    this.Call=function(p,jsFunc,fname,re_id,s_id,desc,load)
        {
            pp={
            jsFunc : jsFunc,
            baseXajaxFunc : p.baseXajaxFunc,
            infoDivMaster: p.infoDivClone ? (p.infoDivMaster ? p.infoDivMaster : p.baseWindow) : false,
            infoDivClone : p.infoDivClone,
            }
            if(eval('typeof '+jsFunc)=='undefined')
                {
                    eval(jsFunc+'=new LMSWindows(pp);');
                }
            eval(jsFunc+'.InitLoad(\''+p.LoadTxt+'\')');
            eval(jsFunc+".ShowWindowXajax('"+fname+"','"+re_id+"',s_id,'"+desc+"','"+load+"');");
        }
    this.HideAll=function()
        {
            lista=new Array();
            var i=0;
            for(i=0;i<this.funcHooksN.length;i++)
                lista[lista.length]=this.funcHooksN[i];
            for(i=0;i<lista.length;i++)
                eval(this.funcHooksJS[lista[i]]+'.HidePanel();');
        }
    this.RollDownAll=function()
        {
            lista=new Array();
            var i=0;
            for(i=0;i<this.funcHooksN.length;i++)
                lista[lista.length]=this.funcHooksN[i];
            for(i=0;i<lista.length;i++)
                {
                    eval(this.funcHooksJS[lista[i]]+'.RollDownPanel();');
                }
        }
    this.ShowAll=function()
        {
            lista=new Array();
            var i=0;
            for(i=0;i<this.funcHooksN.length;i++)
                lista[lista.length]=this.funcHooksN[i];
            for(i=0;i<lista.length;i++)
            {
                if(eval(this.funcHooksJS[lista[i]]+'._load')==true)            
                    {
                        eval(this.funcHooksJS[lista[i]]+'.RestorePanel();');
                        eval(this.funcHooksJS[lista[i]]+'.BlurWindow();');
                        this.SetXYofEl(lista[i],'lmsWindowsManagerMenu'+lista[i]);
                    }
            }
            this.ShowPanels();
        }

    this.RegisterFunction=function(name,desc,jsFunc,uri)
        {
            if(this.funcHooks[name]=='' || this.funcHooks[name]==undefined)
                {
                    this.funcHooksN[this.funcHooksN.length]=name;
                    this.funcHooks[name]=desc;
                    this.funcHooksJS[name]=jsFunc;
                    this.funcHooksUri[name]=uri;
                }

            var s=false;
            var i;
            var dc;
            var c=getCookie('_registeredLmsWindows');
            c=JSON.parse(c);
            if (c==null)
                c=new Array();
            for(i=0;i<c.length;i++)
                {
                    dc=JSON.parse(c[i]);
                    if(dc)
                        {
                            if(dc.jsFunc==jsFunc)
                                {
                                    s=true;
                                    if(eval(this.funcHooksJS[name]+'._load')==true)
                                        c[i]=JSON.stringify(eval(this.funcHooksJS[name]+'.getParams();'));
                                }
                        }
                }
            if(!s && eval(this.funcHooksJS[name]+'.disableCookie')==false)
                {                                               
                    c[c.length]=JSON.stringify(eval(this.funcHooksJS[name]+'.getParams();'));
                    setCookie('_registeredLmsWindows',JSON.stringify(c));
                }
            zindex=this._zindex;
            for(i=0;i<this.funcHooksN.length;i++)
                {
                    if(this.funcHooksN[i]!=name && document.getElementById(this.funcHooksN[i]))
                        {
                            if(zindex<document.getElementById(this.funcHooksN[i]).style.zIndex)
                                zindex=document.getElementById(this.funcHooksN[i]).style.zIndex;
                        }
                }
            if(document.getElementById(name))
                {
                    document.getElementById(name).style.zIndex=zindex-1+2;
                    this._zindex++;
                    this._activeWindow=name;
                }
            this.ShowPanels();
            this.ShowManager();
            this._windowClick=true;
        }
    this.ParseCookie=function(name)
        {
            c=getCookie('_registeredLmsWindows');
            c=JSON.parse(c);
            if(c==null)
                return;
            nc=new Array();
            j=0;
            for (i=0;i<c.length;i++)
                {
                    dc=JSON.parse(c[i]);
                    if(dc)
                    if(dc.jsFunc!=this.funcHooksJS[name])
                        {
                            nc[j]=c[i];
                            j++;
                        }
                }
            setCookie('_registeredLmsWindows',JSON.stringify(nc));        
        }

    this.UnRegisterFunction=function(name)
        {
            f=this.funcHooksN;
            this.funcHooksN=new Array();
            j=0;
            for(i=0;i<f.length;i++)
                {
                    if(f[i]!=name)
                        {
                            this.funcHooksN[j]=f[i];
                            j++;
                        }
                }
            if(this.funcHooks[name])
                this.funcHooks[name]='';
            if(this.funcHooksUri[name])
                this.funcHooksUri[name]='';
            this.UnactiveWindow(name);
            if(name && this.funcHooksJS[name])
                eval(this.funcHooksJS[name]+'.ActiveWindow(\''+name+'\');');

            this.ParseCookie(name);
            this.ShowPanels();
            this.ShowManager();
        }
    this.getTopWindow=function()
        {
            zindex=0;
            wname='';
            for(i=0;i<this.funcHooksN.length;i++)
                {
                    if(document.getElementById(this.funcHooksN[i]))
                    if(zindex<document.getElementById(this.funcHooksN[i]).style.zIndex && document.getElementById(this.funcHooksN[i]).style.display!='none')
                        {
                            zindex=document.getElementById(this.funcHooksN[i]).style.zIndex;
                            wname=this.funcHooksN[i];
                        }
                }
            return wname;
        }
    this.ClearOnTop=function()
        {
            if(!this._windowClick)
            {
            for(i=0;i<this.funcHooksN.length;i++)
                {
                    if(document.getElementById(this.funcHooksN[i]))
                        document.getElementById(this.funcHooksN[i]).style.display='none';
                }
            this._activeWindow='';
            this.ShowPanels();
            }
            this._windowClick=false;
        }
    this.SetWindowOnTop=function(name)
        {
            var id=0;
            if(eval(this.funcHooksJS[name]+'._load')==false)
                {
                    eval(this.funcHooksJS[name]+'._load=true');
                    eval(this.funcHooksJS[name]+".ShowWindowXajax(null,'lmsWindowsManagerMenu"+name+"');");
                }
            if(document.getElementById(name))
                {
                    zindex=document.getElementById(name).style.zIndex;
                    newzindex=10;
                    for(i=0;i<this.funcHooksN.length;i++)
                        {
                            if(document.getElementById(this.funcHooksN[i]))
                                {
                                    actzindex=document.getElementById(this.funcHooksN[i]).style.zIndex;
                                    if(actzindex>newzindex)
                                        newzindex=actzindex;
                                    if(actzindex>zindex)
                                        {
                                            document.getElementById(this.funcHooksN[i]).style.zIndex=actzindex-1;
                                        }
                                }
                        }
                    if(newzindex==0)
                        newzindex=10;
                    this._zindex=newzindex;
                    document.getElementById(name).style.zIndex=newzindex;
                    document.getElementById(name).style.display='';
                    this._activeWindow=name;
                    
                    this.ShowPanels();
                    this.ShowManager();
                    eval(this.funcHooksJS[name]+'.ActiveWindow()');
                }
        }
    this.UnactiveWindow=function(name)
        {
            if(this._activeWindow==name)
                this._activeWindow='';
        }
    this.ShowManager=function()
        {
            d=document.getElementById('lmsWindowsManager');
            if(!d)
                return;
            dm=document.getElementById('lmsWindowsManagerMenu');
            d.style.display='block';
                
            if(d.style.display=='none')
                dm.style.display='none';

        }
    this._onmousepopup=function(txt)
        {
            a="onmouseover=\"popup('"+txt+"');document.getElementById('overDiv').style.zIndex="+(this._zindex-1+10000)+";\" onmouseout=\"pophide();\"";
            
            return a; 
        }
    this.ShowPanels=function(force)
        {
            this._windowClick=true;
            var d=document.getElementById('lmsWindowsManagerMenu');
            var dm=document.getElementById('lmsWindowsManager');
            dmsh=document.getElementById('lmswindowsshowbtn');
            dmhi=document.getElementById('lmswindowshidebtn');
            if(d)
            {
            if(d.style.display=='none')
                {
//                    dm.className='lmsWindowsManager';
                    if(dmsh) {
                        removeClass(dmsh,'lmswinactive');
                        addClass(dmsh,'lmswactive');
                    }
                    if(dmhi) {
                        removeClass(dmhi,'lmswactive');
                        addClass(dmhi,'lmswinactive');
                    }
                }
            else
                {
                    if(dmsh) {
                        addClass(dmsh,'lmswinactive');
                        removeClass(dmsh,'lmswactive');
                    }
                    if(dmhi) {
                        addClass(dmhi,'lmswactive');
                        removeClass(dmhi,'lmswinactive');
                    }
//                    dm.className='lmsWindowsManagerShow';
                }
            }
            wtop=this.getTopWindow();
            if(wtop!='')
                {
                    if(document.getElementById(wtop).style.display=='none')
                        wtop=this._activeWindow;
                }
            else
                wtop=this._activeWindow;
            linia='<tr class="" ><td colspan=6 nowrap ><span class="lmsWindowsManagerButtons hand" onclick='+this.objName+'.HideAll();>Zamknij wszystko</span><span class="lmsWindowsManagerButtons hand" onclick='+this.objName+'.RollDownAll();>Schowaj wszystko</span><span class="lmsWindowsManagerButtons hand" onclick='+this.objName+'.ShowAll();>Pokaż wszystko</span></TD></TR><TR><td height=5></TD></TR>';
            for(var _el in this.funcHooksN)
                {
                    el=this.funcHooksN[_el];
                    html='';                    
                            if(wtop!=el)
                                {
                                    if(document.getElementById(el))
                                        {
                                            html=html+"<TR class='"+(document.getElementById(el).style.display=='none' ? 'suspended' : '')+"' nowrap id='lmsWindowsManagerMenu"+el+"'><TD>"+(eval(this.funcHooksJS[el]+'._load')==false ? "<img src=img/red.png "+this._onmousepopup('Zawartość okna nie załadowana.')+">" : '')+"</TD><TD width=5></TD><TD nowrap class=''><a href=javascript:void(); class='' onclick=\""+this.objName+".SetWindowOnTop('"+el+"');"+this.objName+".SetXYofEl('"+el+"','lmsWindowsManagerMenu"+el+"')\">"+this.funcHooks[el]+"</a></TD>";
                                            eval(this.funcHooksJS[el]+'.BlurWindow();');
                                        }
                                    else
                                        html=html+"<TR class='blend' nowrap id='lmsWindowsManagerMenu"+el+"'><TD></TD><TD width=5></TD><TD nowrap class=''>"+this.funcHooks[el]+"<img src=img/warningon.gif "+this._onmousepopup('Brak dostępnych metod na tej karcie.')+"><a href='"+this.funcHooksUri[el]+"' class='lmsWindowsManagerButtons' "+this._onmousepopup('Przejdź do karty w której okno było uruchomione.')+">przejdź</a></TD>";
                                }
                            else
                                {
                                    html=html+"<TR nowrap id='lmsWindowsManagerMenu"+el+"'><TD nowrap><img src=img/green.png></TD><TD></TD><TD nowrap class=lmswnewwindowa>"+this.funcHooks[el]+"</TD>";
                                    eval(this.funcHooksJS[el]+'.ActiveWindow();');
                                }

                            if(document.getElementById('lmsm'+el))
                                dis=document.getElementById('lmsm'+el).style.display;
                            else
                                dis='none';
    
                            html=html+"<TD width=5></TD><TD width=20><div id=lmsm"+el+" style='display:"+dis+"'>"+(document.getElementById(el) ? '<div id=lmswprogressbar class=lmswprogresssmall></div>' : '')+"</div></TD><TD><a class='text-right hand lmswsymbolstxt buttoncs' style='height:12px;' nowrap onclick="+this.funcHooksJS[el]+".HidePanel('"+el+"'); "+this._onmousepopup('Zamknij okno.')+" ><img src=templates/LmsWindowManager/css/close.png></a></TD>";
                            html=html+'</TR>';
                    if(eval(this.funcHooksJS[el]+'.disableInWM')!=true)
                        {
                            linia=linia+html;
                        }
                }
            dx=document.getElementById('lmswstatusinside');
            if(dx)
                this.SetXYofEl('lmsWindowsManagerMenu','lmsWindowsManager',0,dx.clientHeight,force);
            if(d)
                {
                    d.innerHTML='<TABLE>'+linia+'</TABLE>';
                    d.style.left=(window.innerWidth-d.clientWidth-25)+'px';
                }
        }
    this.SetXYofEl=function(_e,_base,_left,_top, force)
        {
            var e=document.getElementById(_e);
            if(!e)
                return;
            re = e.getBoundingClientRect();
            if(re.left<0 || re.top<0 || re.left>window.innerWidth || re.top>window.innerHeight || force==true)
                {
                    e.style.top=0;
                    e.style.left=0;  
                    re = e.getBoundingClientRect();
                    rel = document.getElementById(_base).getBoundingClientRect();

                    e.style.top=rel.top-re.top+(_top ? _top : -10)+'px';
                    e.style.left=rel.left-re.left+(_left ? _left : 0-e.offsetWidth-20)+'px';
                }
        }
        
    this.Run=function()
        {
            var b=document.getElementById('lmswmenubar');
            var pc=document.getElementById('pagecontainer');
//            pc.addEventListener('click',function(){me.ClearOnTop();});
//            pc.addEventListener('click',function() {event.stopPropagation();});
            
            var ediv=document.createElement("DIV");
            ediv.className='';
            ediv.id='lmsWindowsManager';
            lmswstatus=document.getElementById('lmswstatusinside');
            ediv.objName=this.objName;
            var objshow={
                handleEvent: function(){
                    var d=document.getElementById("lmsWindowsManagerMenu");
                    d.style.display="block";
                    eval(this.objName+'.ShowPanels(true);');eval(this.objName+'.ShowManager();');
                    pophide();                
                },
                objName: this.objName
            }
            var objhide={
                handleEvent: function(){
                    var d=document.getElementById("lmsWindowsManagerMenu");
                    d.style.display="none";
                    eval(this.objName+'.ShowPanels(true);');eval(this.objName+'.ShowManager();');
                    pophide();                
                },
                objName: this.objName
            }
//            ediv.addEventListener('click',obj,false);
            
            ediv.innerHTML='<span id=lmswindowsshowbtn class="lmswactive" title="rozwiń"><img src=templates/LmsWindowManager/css/arrow_down.png></span><span id=lmswindowshidebtn class="lmswinactive" title="zwiń"><img src=templates/LmsWindowManager/css/arrow_up.png></span>';
            if(b)
                b.appendChild(ediv);

            sw=document.getElementById('lmswindowsshowbtn');
            if(sw)
                sw.addEventListener('click',objshow,false);
            sw=document.getElementById('lmswindowshidebtn');
            if(sw)
                sw.addEventListener('click',objhide,false);

            ediv=document.createElement("DIV");
            ediv.id='lmsWindowsManagerMenuOut';
            ediv.style.position='relative';

            var b=document.getElementsByTagName('body');
//            b[0].addEventListener('click',function(){me.ClearOnTop();});
            b[0].appendChild(ediv);

            ediv=document.createElement("DIV");
            ediv.className='buttoncsx lmsWindowsManagerMenu';
            ediv.id='lmsWindowsManagerMenu';
            ediv.style.left=window.innerWidth-150+'px';
            ediv.innerHTML='';
            ediv.style.display='none';
            pe=document.getElementById('lmsWindowsManagerMenuOut');
            pe.appendChild(ediv);


//            ediv=document.createElement("DIV");
//            ediv.className='overdiv';
//            ediv.id='lmsWindowsQuestion';
//            ediv.style.display='none';
//            b[0].appendChild(ediv);
            p={jsFunc : 'questionWindow', relId: 'pagecontainer'}
            this.QuestionWindow=new LMSWindows(p);
            this.QuestionWindow.InitLoad('');
            this.ShowManager();
        }

    this.LoadCookies=function()
        {
            c=getCookie('_registeredLmsWindows');
            cc=JSON.parse(c);
            var ii;
            if(cc!=null)
                for(ii=0;ii<cc.length;ii++)
                    {
                        var dc=JSON.parse(cc[ii]);
                        if(dc!=null)
                            {
                                cur_name=dc.jsFunc.replace(dc.s_id+'','');
                                var id=dc.s_id;
                                isfunc=false;
                                dc.WM = this;
                                try
                                    {
                                        (eval(dc.jsFunc+'.jsFunc'));
                                        isfunc=true;
                                    }
                                catch (err)
                                    {
                                    }

                                if(!isfunc)
                                    {
                                        dc._load=false;
                                        eval(dc.jsFunc+"=new LMSWindows(dc);");
                                        eval(dc.jsFunc+".InitLoad('"+dc.LoadTxt+"');");
                                    }
                                if(eval('typeof xajax__'+dc.baseXajaxFunc+'Show')=='function')
                                    {
                                        eval(dc.jsFunc+".ext_id='"+dc.ext_id+"';");
                                        eval(dc.jsFunc+".ShowWindowXajax('"+dc.fname+"','"+dc.re_id+"','"+dc.s_id+"','"+dc.description+"',"+false+");");
                                        this.RegisterFunction(eval(dc.jsFunc+'.baseWindow'),dc.description,dc.jsFunc);
                                        eval(dc.jsFunc+".RollDownPanel();");
                                    }
                                else
                                    {
                                        this.RegisterFunction(dc.jsFunc, dc.description, dc.jsFunc, dc.requestUri);
                                    }
                            }
                    }        
        }                
}

function LMSWindows(p)
{
    this.jsFunc=p.jsFunc;
    this.baseWindow=p.baseWindow ? this.jsFunc+p.baseWindow : this.jsFunc+'baseWindow';
    this.formName=p.formName ? p.formName : this.jsFunc+'_service_form';   
    this.baseXajaxFunc=p.baseXajaxFunc;
    this.infoDiv=p.infoDiv ? p.infoDiv : this.baseXajaxFunc+'infodiv'; 
    this.refreshFname=p.refreshFname ? p.refreshFname : '';
    this.refreshId=p.refreshId ? p.refreshId : 0;
    this.WM=p.WM ? p.WM : LmsWindowsManager;
    this._load= p._load!=undefined ? p._load : true;
    this.infoDivClone=p.infoDivClone ? p.infoDivClone : false;
    this.infoDivMaster=p.infoDivMaster ? p.infoDivMaster : (this.infoDivClone ? this.baseWindow : false);
    this.disableCookie=p.disableCookie ? p.disableCookie : false;

    this.relId=p.relId ? p.relId : null;
    this._windowLeft=p._windowLeft ? p._windowLeft : -700;
    this._windowTop=p._windowTop ? p._windowTop : -50;
    this._windowPadding=10;
    this._windowClass=p._windowClass ? p._windowClass : "lmswnewwindow lmswmenuwindowanimate";
    this._infoPadding=30;
//    this._infoClass="lmswnewwindowa text-center";
    this._infoClass="loadwindow text-center";
    this._loadLeft=-30;
    this._loadTop=-100;
    this._move=false;
    this._runUpload=false;
    this.fileUploads='';
    this.LoadingData=false;
    this._globalreload=true;
    this.GlobalResponseTimeout=p.GlobalResponseTimeout ? p.GlobalResponseTimeout : 1000;
    this.GlobalResponseCount=p.GlobalResponseCount ? p.GlobalResponseCount : 15;

    var me=this;
        
    this.getParams=function()
        {
            p={
                jsFunc : this.jsFunc,
                baseXajaxFunc : this.baseXajaxFunc,
                refreshFname : this.refreshFname,
                refreshId : this.refreshId,
                infoDiv : this.infoDiv,
                description : this.description,
                fname : this.fname,
                re_id : this.re_id,
                s_id : this.s_id,
                ext_id : this.ext_id,
                LoadTxt : this.LoadTxt,
                requestUri : this.requestUri,
                infoDivMaster: this.infoDivMaster,
                infoDivClone: this.infoDivClone,                                
            }
            return p;
        }
    this.InitLoad=function(inner,p)
        {
            this.LoadTxt=inner;
            if(p!=undefined)
                {
                    if(p._windowLeft)
                        this._windowLeft=p._windowLeft;
                    if(p._windowTop)
                        this._windowTop=p._windowTop;
                    if(p._windowPadding)
                        this._windowPadding=p._windowPadding;
                    if(p._windowClass)
                        this._windowClass=p._windowClass;
                    if(p._infoPadding)
                        this._infoPadding=p._infoPadding;
                    if(p._infoClass)
                        this._infoClass=p._infoClass;
                    if(p._loadLeft)
                        this._loadLeft=p._loadLeft;
                    if(p._loadTop)
                        this._loadTop=p._loadTop;
                    if(p._objName)
                        this._objName=p._objName;
                }    
//            var b=document.getElementsByTagName('html');
            var b=document.getElementById('pagecontainer');
            var ediv=document.createElement("DIV");
            ediv.style.display='none';
            ediv.style.position='fixed';
            ediv.id=this.infoDiv+'_Ooper';
            ediv.style.zIndex=100;
            ediv.innerHTML='<div id='+this.infoDiv+'_oper class="'+this._infoClass+'" style="padding:'+this._infoPadding+'px;">'+inner+'</div>';
            b.appendChild(ediv);

            ediv=document.createElement("DIV");
            ediv.style.display='';
            ediv.style.position='absolute';
            ediv.id=this.baseWindow+'out';
            ediv.innerHTML='<div id='+this.baseWindow+' class="'+this._windowClass+'" style="padding:'+this._windowPadding+'px;display:none;position:relative;"></div>';
            ediv.innerHTML=ediv.innerHTML+'<div id='+this.baseWindow+'blur class="'+this._windowClass+'a" style="padding:'+this._windowPadding+'px;display:none;position:relative;background:#e0e0e0" onmouseup='+this.jsFunc+'.WM.SetWindowOnTop(\''+this.baseWindow+'\')></div>';
            b.appendChild(ediv);

        }
    this.StartMove=function(e)
        {
            this._move=true;
            __moveWindowFunc=this.jsFunc+'.Move';
            re = e.getBoundingClientRect();
            this._startX=getMouseX()-re.left+this._windowPadding+1;
            this._startY=getMouseY()-re.top+this._windowPadding+9;
        }
    this.StopMove=function(e)
        {
            this._move=false;
            __moveWindowFunc='';
        }
    this.Move=function(_ee)
        {
            if(this._move)
                {
                    var _e=_ee ? _ee : this.baseWindow;
                    var e=document.getElementById(_e);
                    e.style.top=0;
                    e.style.left=0;
                    re = e.getBoundingClientRect();

                    e.style.top=0-re.top+getMouseY()-this._startY+'px';
                    e.style.left=0-re.left+getMouseX()-this._startX+'px';
                }
        }
    this.SetXYofEl=function(_ee,_left,_top)
        {
            var _e=_ee ? _ee : this.baseWindow;
            var e=document.getElementById(_e);
            var el=document.getElementById(this.relId);
            e.style.top=0;
            e.style.left=0;
            re = e.getBoundingClientRect();
            if(el==null || el=='undefined')
                {
                    rel= {left:800,top:100,}
                    e.style.top=0-re.top+(window.innerHeight+e.clientHeight)/2-e.clientHeight+'px';
                    e.style.left=0-re.left+(window.innerWidth-re.left+e.clientWidth)/2-e.clientWidth+'px';
                }
            else
                {
                    rel = el.getBoundingClientRect();            
                    e.style.top=rel.top-re.top+(_top ? _top : this._windowTop)+'px';
                    e.style.left=rel.left-re.left+(_left ? _left : this._windowLeft)+'px';
                }
            re = e.getBoundingClientRect();
            if(re.left<0)
                e.style.left='0px';
            if(re.top<0)
                e.style.top='0px';
//                 || re.left>window.innerWidth || re.top>            
        }
    this.BlurWindow=function()
        {
            var e_id=this.baseWindow;
            o=document.getElementById(e_id);
            d=document.getElementById(e_id+'blur');
            d.style.zIndex=o.style.zIndex;
            d.style.display=o.style.display;
            d.style.position='absolute';
            d.style.top=o.style.top;
            d.style.left=o.style.left;
            d.style.width=o.clientWidth-(this._windowPadding*2)+'px';
            d.style.height=o.clientHeight-(this._windowPadding*2)+'px';
            d.style.opacity=0.5;
        }
    this.ActiveWindow=function(e)
        {
            var el=e ? e : this.baseWindow;
            d=document.getElementById(el+'blur');
            if(d)
                d.style.display='none';
        }
    this.xajaxWait=function(fexec)
        {
            if (this.xajaxFexec==false || this.xajaxFexec==undefined)
                {
                    this.xajaxFexec=fexec;
                    this.xajaxWaitTime=0;
                }
            if(this.xajaxWaitTime>20000)
                {
                    d=document.getElementById('alertglobal');
                    if(d)
                        d.innerHTML='<div class=alertLMSsolid>Przekroczono czas oczekiwania na dostęp do metody '+this.baseXajaxFunc+'(\''+this.fname+'\')</div>';
                }
                
            if(xajaxProcess)
                {
                    var time=Math.floor((Math.random() * 100) + 100);
                    this.xajaxWaitTime=this.xajaxWaitTime+time;
                    setTimeout(this.jsFunc+".xajaxWait()",time);
                    return true;
                }
            eval(this.xajaxFexec);
            this.xajaxFexec=false;
            return false;
        }
    this.ShowWindowXajax=function(fname,re_id,s_id,desc,load, loadprogress)
        {
            if(typeof xajax.uploadedOrigFields!='undefined')              
                xajax.uploadedOrigFields[this.jsFunc]='';
            
            var e_id=this.baseWindow;
            this.fname=fname ? fname : this.fname;
            this.re_id=re_id ? re_id : this.re_id;

            if(typeof s_id=='object')
                {
                    this.ext_id=JSON.stringify(s_id);
                    s_id=s_id.id;
                }
            else
                this.ext_id='';
            this.s_id=s_id ? s_id : this.s_id;
            this.description=desc ? desc : this.description;
            this._load=load==true || load==false ? load : true; 
            
            this._callback='xajax__'+this.baseXajaxFunc+'Show';
            
            this.ShowPanel(e_id,desc);
            if(this._load && loadprogress!=false)
                this.Load(true,this._globalreload);
            this.relId=this.re_id;
            if(document.getElementById(e_id).style.display=='')
                {
                    document.getElementById(e_id).innerHTML='';
                    this.HidePanel(e_id+'out');
                    var opt={
                        func:this.fname, 
                        jsFunc: this.jsFunc, 
                        baseWindow: this.baseWindow, 
                        infoDiv: this.infoDiv, 
                        formName: this.formName,
                        refreshId: this.refreshId,
                        SetXYofEl: true,
                        description: this.description,
                        infoDivMaster: this.infoDivMaster,
                    };
                    if(this._load)
                        {
//                            eval("xajax__"+this.baseXajaxFunc+"Show('"+e_id+"','"+(this.s_id != undefined ? this.s_id : 0 )+"','"+JSON.stringify(opt)+"','"+this.ext_id+"')");
                            this.xajaxWait("xajax__"+this.baseXajaxFunc+"Show('"+e_id+"','"+(this.s_id != undefined ? this.s_id : 0 )+"','"+JSON.stringify(opt)+"','"+this.ext_id+"')");
                        }
//                    this.WM.RegisterFunction(e_id,this.description,this.jsFunc);
                    if(this.re_id=='_center')
                        this.SetXYofEl();
                }
        }
    this.Reload=function(reload,globalreload, loadstatus)
        {
            this._callback='xajax__'+this.baseXajaxFunc+'Show';
            if(!loadstatus==false)
                {
                    this.Load(reload==true || reload==undefined ? true : false, globalreload==true || globalreload==undefined ? true : false);
                    this.BlurWindow();
                }
            var opt={
                func:this.fname, 
                jsFunc: this.jsFunc, 
                baseWindow: this.baseWindow, 
                infoDiv: this.infoDiv, 
                formName: this.formName,
                refreshId: this.refreshId,
                SetXYofEl: false,
                description: this.description,
                infoDivMaster: this.infoDivMaster,
                infoDivClone: this.infoDivClone,
                infoDivSave: reload==false ? false : true,
            };
            if(typeof xajax.uploadedOrigFields!='undefined')              
                xajax.uploadedOrigFields[this.jsFunc]='';
//            if(!xajaxRequest)            
                this.xajaxWait("xajax__"+this.baseXajaxFunc+"Show('"+this.baseWindow+"','"+(this.s_id != undefined ? this.s_id : 0 )+"','"+JSON.stringify(opt)+"','"+this.ext_id+"')");        
        }
    this.CallXajaxLoad=function(fname,refreshFname,refreshId,id)
        {
            this.Load();
            this.CallXajax(fname,refreshFname,refreshId,id);
        }
    this.CallXajax=function(fname,refreshFname,refreshId,id)
        {
            this._callback='xajax__'+this.baseXajaxFunc+'Set';
            refreshFname=refreshFname ? refreshFname : this.refreshFname;
            var f=document.forms[this.formName];
            if(this.infoDivMaster)
                this.ClearError(this.infoDivMaster);
            this.ClearError(this.baseWindow);
            this.ClearError('alertglobal',true);

            files=null;
            if(typeof xajax.uploadedOrigFields!='undefined')              
                files=xajax.uploadedOrigFields[this.jsFunc];
//nie moze tak byc, bo Reload korzysta z tego. kazdy _Set to jakby wykonanie operacji w tym okienku wiec nie mozna podmieniac.
//            this.fname=fname ? fname : this.fname;
            if(files)
                {
                    files=files.split("\n");
                    for(i=0;i<files.length;i++)
                        {
                            if(files[i] && files[i]!=" ")
                                {
                                    files[i]=JSON.parse(files[i]);
                                }
                            else
                                files[i]=null;
                        }
                }
            var opt={
                func:fname, 
                jsFunc: this.jsFunc, 
                baseWindow: this.baseWindow, 
                infoDiv: this.infoDiv, 
                formName: this.formName, 
                refreshFname: refreshFname, 
                refreshId: refreshId,
                infoDivMaster: this.infoDivMaster,
                closed : this._closed,
                files : files,
            };
            if(id==undefined)
                this.xajaxWait("xajax__"+this.baseXajaxFunc+"Set(JSON.stringify($('#"+this.formName+"').serializeObject()),'"+JSON.stringify(opt)+"')");
            else
                {
                    v={
                        id: id,
                        s_id : this.s_id,
                    }
                    this.xajaxWait("xajax__"+this.baseXajaxFunc+"Set('"+JSON.stringify(v)+"','"+JSON.stringify(opt)+"')");
                }
    }
    this.RegisterFunction=function()
        {
            this.WM.RegisterFunction(this.baseWindow,this.description,this.jsFunc);        
        }
    this.CheckForResponseTimeout=function()
        {   
            if(typeof xajaxCallbacks!='undefined')        
            if(this.LoadingData && xajaxCallbacks[this._callback]=='failure')
                {
                    d=document.getElementById('alertglobal');
                    if(d)
                        d.innerHTML='<div class=alertLMSsolid>Nie powiodło sie wykonanie operacji w okienku '+this.description+'</div>';
                    this.LoadDone();
                    this._load=false;
                    this.RollDownPanel();
                    return;
                }

            if(this.LoadingData && this.ResponseCount<=0)
                {
                    d=document.getElementById('alertglobal');
                    if(d)
                        d.innerHTML='<div class=alertLMSsolid>Przekroczono czas wykonywania operacji w okienku '+this.description+'</div>';
                    this.LoadDone();
                    this._load=false;
                    this.RollDownPanel();
                    return;
                }
            if(typeof xajaxCallbacks!='undefined')        
            if(this.LoadingData && xajaxCallbacks[this._callback]=='expire')
                {
                    d=document.getElementById('alertglobal');
                    if(d)
                        d.innerHTML='<div class=alertLMSsolid>Przekroczono czas wykonywania operacji w metodzie '+this._callback+'</div>';
                    this.LoadDone();
                    this._load=false;
                    this.RollDownPanel();
                    return;
                }
            
            this.ResponseCount--;
            if(this.LoadingData)
                var s=setTimeout(this.jsFunc+'.CheckForResponseTimeout()',this.ResponseTimeout);            
        }
    this.CheckForResponse=function()
        {
            this.ResponseTimeout=this.GlobalResponseTimeout;
            this.ResponseCount=this.GlobalResponseCount;
            var s=setTimeout(this.jsFunc+'.CheckForResponseTimeout()',this.ResponseTimeout);
        }
    this.Load=function(reload,globalreload)
        {
            this.LoadingData=true;
//            this.WM.progresslog(true,this._callback,this.baseXajaxFunc+this.fname);
            this.CheckForResponse();
            elO=document.getElementById(this.infoDiv+'_Ooper');
            el=document.getElementById(this.infoDiv+'_oper');
            if(el.innerHTML!='')
                elO.style.display='block';
            y=window.innerHeight/2;
            x=window.innerWidth/2;
            elO.style.top=y+this._loadTop+'px';
            elO.style.left=x+this._loadLeft+'px';
            elO.style.opacity=0.85;

            if(document.getElementById('lmsm'+this.baseWindow))
                document.getElementById('lmsm'+this.baseWindow).style.display='';

            this.ClearError();
            if(globalreload || globalreload==undefined)
                {
                    this.ClearError('alertglobal',true);
                    this.WM.SetStatus();
                }
            if(this.infoDivMaster && (!reload || reload==undefined))
                this.ClearError(this.infoDivMaster);
    }
    this.LoadDone=function()
        {
            if(!this.LoadingData)
                return;
            this.LoadingData=false;
            this.HidePanel(this.infoDiv+'_Ooper');
            if(document.getElementById('lmsm'+this.baseWindow))
                document.getElementById('lmsm'+this.baseWindow).style.display='none';
        }

    this.ClearError=function(id,global)
        {
            var el=id ? id : '';
            if(global!=true)
                var div=this.infoDiv+'_info'+el;
            else
                div=el;
            if(document.getElementById(div))
                document.getElementById(div).innerHTML='';
            if(id=='alertglobal')
                {
                    if(document.getElementById(id+'table'))
                        document.getElementById(id+'table').style.display='none';
                    if(document.getElementById('lmswsupoutsend'))
                        document.getElementById('lmswsupoutsend').innerHTML='';
                    if(document.getElementById('lmswsupout'))
                        document.getElementById('lmswsupout').innerHTML='';
                }
        }
//HidePanel ukrywa panel i usuwa z listy dostepnych;
    this.HidePanel=function(e)
        {
            var el=e ? e : this.baseWindow;
            if(el==this.baseWindow)
                {
                    if(eval('typeof '+this.jsFunc)!='undefined')
                        if(eval('typeof '+this.jsFunc+'.CallbackClose')=='function')
                            eval(this.jsFunc+".CallbackClose();");
                    if(eval('typeof xajax__'+this.baseXajaxFunc+'Close')=='function')
                        {
                            this.xajaxWait("xajax__"+this.baseXajaxFunc+"Close();");
                        }
                }
            if(document.getElementById(el))
                document.getElementById(el).style.display='none';
            this.WM.UnRegisterFunction(el);
        }        
    this.RestorePanel=function(e)
        {
            var el=e ? e : this.baseWindow;
            document.getElementById(el).style.display='';
        }        
    this.RollDownPanel=function(e)
        {
            var el=e ? e : this.baseWindow;
            document.getElementById(el).style.display='none';
            this.ActiveWindow(el);
            this.WM.UnactiveWindow(el);
            this.WM.ShowPanels();
        }        
    this.ShowPanel=function(el,desc)
        {
            document.getElementById(el).style.display='';
        }
    this.ShowPanelOut=function(e)
        {
            var el=e ? e : this.baseWindow+'out';
            document.getElementById(el).style.display='';
        }
}

document.addEventListener('mousemove', onMouseMove, false);

__moveWindowFunc='';
function onMouseMove(e)
    {
        __mouseX = e.clientX;
        __mouseY = e.clientY;    
        if (__moveWindowFunc)
            eval(__moveWindowFunc+"();");
    }
function getMouseX() 
    {
        return __mouseX;
    }
function getMouseY() 
    {
        return __mouseY;
    }

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
    