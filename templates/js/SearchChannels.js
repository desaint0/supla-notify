// JavaScript Document
SearchChannelsClass=function(list,update)
    {
        this.list=list;
        this.update=update;
        this.Search=function(q,name,opt,elem)
            {
                opt=JSON.parse(opt);
                document.getElementById(name).innerHTML='';
                var buffer='';
                this.elem=elem;
                var me=this;
                q=q.replace('MQTT.','');
                q=q.replace('VIRTUAL.','');
                if($(elem).attr('data-name')=='actions-action')
                    var toSearch=this.list.publish;                
                else                
                    var toSearch=this.list.subscribe;

                $.each(toSearch,function(key,value) {
console.log(key,value);                
                    var re = new RegExp(q,"gi");
                    $.each(value.payload,function(pkey,pvalue) {                    
                    var re2 = new RegExp('![^!]*!',"gi");
                        var displayString=value.devname+' / '+value.description+' / '+me.getType(pkey);
                        if(pvalue==-1)
                            displayString=displayString+' (dowolna)';
                        else
                            displayString=displayString+' ('+pvalue+')';
                        var execString='%'+value.cmd+'.'+pkey+'%';
                        if($(elem).attr('data-name')!='QB_name')
                            if(re2.exec(execString))
                                return true;
                        if((re.exec(displayString) || re.exec(execString)) && q)
                            {
                                buffer+='<li class="list-group-item list-group-item-light"><a href=javascript:void(); onclick="'+me.update+'.Update({ type: \''+value.type+'\', value: \''+execString+'\', payload: \''+pkey+'\' });">'+displayString+'<BR><span class="text-dark font-weight-bold">'+execString+'</span></a></li>';
                            }
                    });
                });
        
                document.getElementById(name).innerHTML=buffer ? '<UL class="list-group">'+buffer+'</UL>' : 'Brak wyników';
                document.getElementById(name+'load').innerHTML='';
            }
        this.Update=function(v)
            {
                var attr=$(this.elem).attr('data-name');
                if(attr=='UpdateElement')
                    {
                        this.elem.value=this.elem.value.replace(/\%/gi,"");
                        var e=this.elem.value.split(".");
                        this.elem.value=e[0];
                        var e=document.getElementById('d_elem');
                        e.value=v.payload;
                        addClass(e,'alert-success');
                    }
                if(attr=='actions-action')
                    {
                        this.elem.value=this.elem.value.replace(/\%/gi,"");
                        this.elem.value=v.type+'.'+this.elem.value;
                        addClass(this.elem,'alert-success');
                    }
            }
        this.getType=function(type)
            {
                switch (type) {
                    case 'hi' :
                        return 'włącz/wyłącz';
                    case 'on':
                        return 'przekaźnik';
                    case 'sensor_1':
                        return 'czujnik zamknięcia';
                    case 'temperature':
                        return 'czujnik temperatury';
                    default :
                        return '--';
                }
            }

    }
