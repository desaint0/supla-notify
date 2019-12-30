// JavaScript Document

SuplaElements=function(p)
    {
        this._params=p;
        
        this.Q=new Array;
        this.AddAction=function(id,del,c)
            {
                var f=this._params.elem+'_actions'+id;
                var elemvalue=c ? c : {
                    action: '', 
                    delay: '',
                    value: '',
                };
                par=document.getElementById(f);
                var eDiv=document.createElement("DIV");
                eDiv.className="list-group-item list-group-item-light ";
                var zaw3=document.getElementById(this._params.elem+'_actionsnew').innerHTML;
                zaw3=zaw3.replace(/%action%/gi,elemvalue.action);
                zaw3=zaw3.replace(/%delay%/gi,elemvalue.delay);
                zaw3=zaw3.replace(/%value%/gi,elemvalue.value);
                zaw3=zaw3.replace(/%description%/gi,elemvalue.description ? elemvalue.description+'<BR>' : '');
                eDiv.innerHTML=zaw3;
                par.appendChild(eDiv);
            }
        this.DelAction=function(id)
            {
                par=id.parentNode;
                par.parentNode.removeChild(par);
            }  
        this.DelElement=function(id)
            {
                var e=document.getElementById(this._params.elem+'_element['+id+']');
                e.parentNode.removeChild(e);
            }  

        this.AddElement=function(element)
            {
                var id=this.Q.length;
                var f=this._params.elem+'_elements';
                var elemid=element ? element.id : '';
                par=document.getElementById(f);
                var eDiv=document.createElement("DIV");
                var zaw3=document.getElementById(this._params.elem+'_elementsNew').innerHTML;
                zaw3=zaw3.replace(/%id%/gi,id);
                zaw3=zaw3.replace(/%elemid%/gi,elemid);
                eDiv.innerHTML=zaw3;
                eDiv.id=this._params.elem+'_element['+id+']';
                par.appendChild(eDiv);
                
                this.Q[id]=new QueryBuilder( { elem: 'd_condition'+id, argOnClick: 'SearchChannels', obj: this._params.obj+'.Q'+'['+id+']' } );
                var cfg='';
                try
                    {
                        cfg=JSON.parse(element.condition);
                    }
                catch(err)
                    {
                        cfg='';
                    }
                this.Q[id].run(cfg);

                document.getElementById(this._params.elem+'_actions'+id).innerHTML='';
                
                var me=this;
                if(element)
                $.each(element.actions, function(key,value) 
                    {
                        $.each(QBE.publish, function(skey,svalue)
                            {
                                var re = new RegExp(svalue.cmd,"gi");
                                if(re.exec(value.action))
                                    value.description=svalue.devname+' / '+svalue.description;
                            }
                        );
                        me.AddAction(id,true,value);
                    }
                );
            }
    }