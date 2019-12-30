WebSocketServer=function(p)
    {                        
        this.WS=null;
        this.WSInterval=null;
        this.params=p;
        this.Authenticated='';
        this.afterAuth=new Array;
        this.afterAuthList=new Array;

        this.afterAuthCall=function(f,o)
            {
                var p=this.afterAuth.length;
                this.afterAuth[p]=f;
                this.afterAuthList[p]=o;
            }                
        this.addCMD=function(c,m,p)
            {
                if(this.Authenticated!='true')
                    return;
                var msg = {
                    cmd: 'cmd',
                    class: c,
                    method: m,
                    authID: this.params.authID,
                    data: p,
                }
                this.WS.send(JSON.stringify(msg));                    
            }
        this.addQueue=function(n,d)
            {
                if(this.Authenticated!='true')
                    return;
                var msg = {
                    cmd: 'addqueue',
                    authID: this.params.authID,
                    name: n,
                    value: d,
                }
                this.WS.send(JSON.stringify(msg));
                console.log(msg);                    
            }
        this.delQueue=function(n)
            {
                if(this.Authenticated!='true')
                    return;
                var msg = {
                    cmd: 'delqueue',
                    authID: this.params.authID,
                    name: n,
                }
                this.WS.send(JSON.stringify(msg));
                console.log(msg);                    
                
                var afterAuthList=new Array();
                var afterAuth=new Array();
                for(var i=0;i<this.afterAuthList.length;i++)
                    if(this.afterAuthList[i]!=n)
                        {
                            afterAuthList[i]='';
                            afterAuth[i]='';
                        }
                this.afterAuth=afterAuth;
                this.afterAuthList=afterAuthList;
            }
        this.Init=function()
            {
//                this.afterAuth=new Array;
//                this.afterAuthList=new Array;
                var params=this.params;
                if(!this.params.WSen || this.params.WSen=='0')
                    return;  
                if(!this.params.WSUri)
                    {
                        document.getElementById(params.lmswWSStatus).className='';
                        addClass(document.getElementById(params.lmswWSStatus),'alert alert-dark');
                        return;
                    } 	
                var WS = new WebSocket(this.params.WSUri);            
                var me=this;
                addClass(document.getElementById(params.lmswWSStatus),'alert alert-dark');
                    
                WS.onopen = function(ev) 
                    { 
                        clearInterval(this.WSInterval);
                        var msg = {
                            cmd: 'init',
                            var: [ 
                                {
                                    var: '_login',
                                    value: params.login
                                },
                                {
                                    var: '_pass',
                                    value: params.pass
                                },
                                {
                                    var: '_salt',
                                    value: params.salt
                                },
                            ]
                        }
                        console.log('Initializing WS...');
                        this.send(JSON.stringify(msg));
                    }

                WS.onclose = function (evt)
                    {
                        if(me.Authenticated=='false')
                            return;
                        document.getElementById(params.lmswWSStatus).className='';
                        addClass(document.getElementById(params.lmswWSStatus),'alert alert-dark');
                        this.WSInterval=setTimeout(function(){me.Init();},5000);
                    };
                WS.onmessage = function (evt)
                    {
                        var msg=JSON.parse(evt.data);
                        if(msg.script)
                            eval(msg.script);
                        if(msg.type=='auth')
                            {
                                document.getElementById(params.lmswWSStatus).className='';
                                    if(msg.value=='OK')
                                        {
                                            addClass(document.getElementById(params.lmswWSStatus),'alert alert-success');
                                            me.Authenticated='true';                                                
                                            console.log('WS auth OK');                                                
console.log(me.afterAuth);
                                            for(var i=0;i<me.afterAuth.length;i++)
                                                eval(me.afterAuth[i]);
                                        }
                                    else
                                        {
                                            addClass(document.getElementById(params.lmswWSStatus),'alert alert-danger');                                                
                                            me.Authenticated='false';
                                            console.log('WS auth failed');                                                
                                        }
                            }
                    };
                WS.onerror = function (evt)
                    {
                        console.log('WS error.');
                        document.getElementById(params.lmswWSStatus).className='';
                        addClass(document.getElementById(params.lmswWSStatus),'alert alert-danger');
                    };
                this.WS=WS;
            }
        this.Close=function()
            {
                if(!this.WS)
                    return;
                this.WS.close(1000,'');
            }
    }
