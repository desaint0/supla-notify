var ShmMgmClass=function(p)
    {
        this.id=p.id;
        this.key=p.key;
        this.hash=p.hash;
        this.shmtype=p.shmtype;
        this.confirmProgress=p.confirmProgress;
        this.dataLog=p.dataLog;
        this.jqajax=false;
        this.commit=false;
        this.Timeout=null;
        this.GlobalTimeout=null;
        this.TimeoutValue=p.Timeout!=undefined ? p.Timeout : 1;
        this.GlobalTimeoutValue=p.GlobalTimeoutValue!=undefined ? p.GlobalTimeoutValue : 10 //seconds
        this.errors=false;
        this.GlobalError=false;
        
        this.url=p.url!=undefined ? p.url : 'aplmsshm.php';
        var me=this;
        
        this.ResetFunc=function()
            {
                if(this.cfp || this.cfp==undefined)
                    document.getElementById(this.confirmProgress).className='';            
            }
        this.ResetShm=function(cfp)
            {
                this.cfp=cfp;
                this.CallAjax(
                    {
                        reset: 'true',
                        cfp: cfp,
                        sfunc: 'ResetFunc',
                    }
                );
            }
        this.CheckShmFunc=function(data)
            {
                var res = JSON.parse(data);
                if(res)
                    {
                        this.jqajax=false;
                        this.errors=false;
                    }
                var that=this;
                ih=document.getElementById(this.dataLog);
                if(res.result!=null)
                    ih.innerHTML=ih.innerHTML+res.result;
                if(!this.commit || !this.jqajax)
                    {
                        if(res.errors==1 && res.params.commit!=true)
                            ih.innerHTML=ih.innerHTML+'<B><span class=red>Ooops... (przekroczono limit czasu odczytu informacji)</span></B><BR>';
                        if(res.errors==2)
                            ih.innerHTML=ih.innerHTML+'<B><span class=red>Błąd transportu danych.</span></B><BR>';
                        if(res.errors==3)
                            ih.innerHTML=ih.innerHTML+'<B><span class=red>Niepoprawny klucz sesji</span></B><BR>';
                        this.errors=res.errors;
                    }

                if(res.errors)
                    this.jqajax=true;
                if(res.params.commit==true)
                    {
                        this.jqajax=true;
                        this.commit=true;
                    }
                if(!this.jqajax)
                    this.Timeout=setTimeout(function(){that.CheckShm();},this.TimeoutValue);
                else                
                    this.ResetShm();
            }
        this.CheckShm=function()
            {
                this.CallAjax(
                    {
                        reset: '',
                        sfunc: 'CheckShmFunc',
                    }
                );
            }
        this.CallAjax=function(param)
            {
                var that=this;
                this.GlobalTimeout=setTimeout(function(){that.GlobalTimeoutFunc();},this.GlobalTimeoutValue*1000);
                $.ajax({ 
                    type: 'GET', 
                    url: this.url, 
                    data: { 
                        reset: param.reset,
                        hash: this.hash,
                        id: this.id,
                        key: this.key,
                        shmtype: this.shmtype,
                    }, 
                    success: function (data) {
                        clearTimeout(me.GlobalTimeout);
                        eval("me."+param.sfunc+"(data)");
                        }
                });
            }
        this.GlobalTimeoutFunc=function()
            {
                this.jqajax=true;
                ih=document.getElementById(this.dataLog);
                ih.innerHTML=ih.innerHTML+'<B><span class=red>Przekroczono limit czasu zdalengo hosta.</span></B><BR>';                
                clearTimeout(this.Timeout);
                this.GlobalError=true;
            }
    }
