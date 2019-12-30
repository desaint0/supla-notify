function SearchField(elem,ileSzukac,divname,fnname,timeout,notfoundTxt)
    {             
//elem - document form element
//ileSzukac - how many characters in search filed to start searching...
//divname - an id of element to which ajax response (write data)
//fnname - a fucntion name to execute (ajax)
//timeout - timeout to start searching after typeing last letter.
//          if enter pressed, searching right now.
 
        this.SZ=0;
        this.unicode='';
        this.actualkey='';
        this.timeout=timeout;
        this.elem=elem;
        this.divname=divname;
        this.fnname=fnname;
        this.ileSzukac=ileSzukac;
        this.notfoundTxt=notfoundTxt ? notfoundTxt : '';
        var me=this;
        var FajaxTimeOut=null;
        var tmp=fnname.split(".");
        this.functionName=tmp[0];
        
        this.Clear=function()
            {
                clearTimeout(me.FajaxTimeOut);
            }
        this.Search=function(e,opt){
        if(opt.match(/&/))
            opt=this.UriToJSON(opt);
        if (e!='')
            {
                this.unicode=e.keyCode? e.keyCode : e.charCode;
                this.actualkey=String.fromCharCode(this.unicode);
            }
        var timeout=me.timeout;
        var ileSzukac=me.ileSzukac;
        clearTimeout(me.FajaxTimeOut);
        if ((me.elem.value.length>me.ileSzukac && me.unicode!=8 ) || (me.unicode==39 && me.ileSzukac==0) || (me.unicode==8 && me.elem.value.length>me.ileSzukac) || e=='')
            {
                if(me.unicode>31) {me.SZ=1;}
                if(me.unicode==8) {me.SZ=1;}
                if(me.unicode==13) {me.SZ=1;timeout=1;}
                if (me.unicode==undefined) {me.SZ=0;}
                if(e=='') {me.SZ=1;ileSzukac=-1;timeout=1;}
                if(this.SZ>0)
                    {  
                        me.FajaxTimeOut=setTimeout(function(){
                        if (me.elem.value.length>ileSzukac || (me.unicode==39 && ileSzukac==0) )
                            {
                                document.getElementById(me.divname+'load').innerHTML='<span id=lmswprogressbar class="lmswprogresssmall text-center"></span>'; 
                                eval(me.fnname+"('"+me.elem.value+"','"+me.divname+"','"+opt+"',me.elem)");
                            }
                        else
                            {                                  
                                document.getElementById(me.divname+'load').innerHTML='';            
                                document.getElementById(me.divname).innerHTML=me.notfoundTxt;
                            }         
                        },timeout);
                    }
            }
        else
            {          
                if (me.unicode==8 && me.elem.value.length==0)
                    {
                        if(me.notfoundTxt)
                            eval(me.fnname+"('"+me.elem.value+"','"+me.divname+"','"+opt+"')");
                        else
                            document.getElementById(me.divname).innerHTML=me.notfoundTxt;
                    }
            }
        }
        
        this.UriToJSON=function(opt)
            {
                arr=opt.split('&');
                obj={};
                for(i=0;i<arr.length;i++)
                    {
                        val=arr[i].split('=');
                        obj[val[0]]=val[1];                        
                    }
                return JSON.stringify(obj);   
            }
        this.MergeJSON=function(opt1,opt2)
            {        
                opt='';
                if(opt2.match(/&/))
                    opt2=this.UriToJSON(opt2);                
                if(opt1!='[]' && opt1!='')
                    {
                        opt=opt1.substring(0,opt1.length-1);
                        opt2=opt2.substring(1);
                    }
                if(opt)
                    opt=opt+',';
                opt=opt+opt2;
                return opt;
            }

        this.onOver=0;
        this.lastEl=null;
        this.onMouseOut=function()
            {
                if(this.onOver==2)
                    {
                        this.onOver=null;
                        var me=this;                        
                        setTimeout(function() {
                            me.onBlur()
                        },100);
                    }
                else
                    this.onOver=2;
            }
        this.onMouseOver=function()
            {
                this.onOver=1;
            }    
        this.onKeyUp=function(elem,e)
            {
                this.elem=elem;
                this.Search(e,'&id=');
            }
        this.onFocus=function(elem)
            {
                this.Clear();
                if(this.lastEl && this.lastEl!=elem)
                    this.onBlur();

                this.onOver=2;
                var eDiv=document.createElement("DIV");
                eDiv.style.display='block';
                eDiv.innerHTML=document.getElementById(this.divname+'2').innerHTML;
                eDiv.className=" position-absolute";
                eDiv.style.zIndex=10;
                eDiv.id=this.divname+'3';
                elem.parentNode.appendChild(eDiv);

                this.lastEl=elem;
                this.onKeyUp(elem,'');
            }
        this.onBlur=function(elem)
            {
                if(this.onOver!=1 || this.onOver==null)
                    {
                        if(document.getElementById(this.divname+'3'))
                            document.getElementById(this.divname+'3').parentNode.removeChild(document.getElementById(this.divname+'3'));        
                    }
                else
                    this.onOver=2;        
            }
        this.Update=function(v)
            {
                this.elem.value=v.value;
                this.onOver=2;
                this.onBlur();
                if(eval('typeof '+this.functionName+' == \'object\''))
                    {
                        eval(this.functionName+'.Update(v);');
                    }
            }
    }
