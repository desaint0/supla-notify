SuplaGrids=function(params)
    {
// params:
// ExecChannelName, ElemParams
        this.params=params;
        this.Edited=false;
        this.Moved=false;
        this.ExecChannel=function(action,idx)
            {
                if(this.Edited)
                    return;
                eval(this.params.ExecChannelName+'('+action+',\''+this.params.ElemParams[idx].etype+':'+this.params.ElemParams[idx].idx+'\',\''+this.params.objName+'\','+idx+')');
                var d=document.getElementById('gridContent_'+this.params.objName+'_'+idx).querySelectorAll(`[data-name="spin"]`);
                $.each(d, function(key,value) {
                    addClass(this,"lmswprogressbar lmswprogresssmall");
                    var me=this;
                    setTimeout(function() {
                        removeClass(me,"lmswprogressbar lmswprogresssmall");
                    },10000);
                });
            }
        this.GetNewSaved=function()
            {
                var SavedParams={
                    parameters: { },
                    content: [],
                }
                return SavedParams;
            }
        this.SetTemplate=function(t)
            {
                try {
                        this.SavedParams=JSON.parse(t);
                    }
                catch (err)
                    {
                        this.SavedParams=this.GetNewSaved();
                    }
                if(this.SavedParams==null)
                    this.SavedParams=this.GetNewSaved();
                document.getElementById(this.params.gridDivName+'_main').style.display=this.SavedParams.parameters.panel ? 'none' : '';
                document.getElementById('SuplaChannelSizerVal').value=this.SavedParams.parameters.sizer,
                suplaSetPanelBtn(this.params.gridDivName);
                this.EditGridInternal();
            }
        this.SaveGrid=function()
            {
                $grid=this.grid;
                var elems = $grid.packery('getItemElements');
                this.SavedParams=this.GetNewSaved();
                this.SavedParams.parameters = {
                    panel : document.getElementById(this.params.gridDivName+'_main').style.display ? true : false,
                    sizer: document.getElementById('SuplaChannelSizerVal').value,
                };
                for (var j=0;j<elems.length;j++)
                    {
                        var eId=elems[j].id;
                        var regex=/gridContent_[^_]*_(\d+)/gi;
                        var id=regex.exec(eId);
                        var idx=$(elems[j]).attr('data-name');
                        this.SavedParams.content[this.SavedParams.content.length]={ id: this.params.ElemParams[id[1]].id, hide: this.params.ElemParams[id[1]].hide, idx: idx };
                    }
                var name=document.getElementById('templateName').value ? document.getElementById('templateName').value : document.getElementById('templateId').value;
                eval(this.params.SaveName+'(\''+this.params.objName+'\',\''+name+'\',\''+JSON.stringify(this.SavedParams)+'\')');
                this.SaveSelected(name);
            }
        this.SaveSelected=function(name)
            {
                setCookie('SuplaScenesGrid'+this.params.objName,name);
            }
        this.EditGrid=function()
            {
                this.Edited=this.Edited ? false : true;
                if(this.Edited)
                    {
                        var e=document.getElementById('edit-grid');
                        removeClass(e,'btn-outline-secondary');
                        addClass(e,'btn-outline-danger');
                    }
                else
                    {
                        var e=document.getElementById('edit-grid');
                        removeClass(e,'btn-outline-danger');
                        addClass(e,'btn-outline-secondary');
                    }
                this.EditGridInternal();
            }
        this.EditGridInternal=function()
            {
                $grid=this.grid;
                for (var i=0;i<this.params.ElemParams.length;i++)
                    {
                        var elem=document.getElementById('gridContent_'+this.params.objName+'_'+i);
                        $grid.packery( 'remove', elem ).packery('shiftLayout');
                    }
                document.getElementById(this.params.gridDivName).innerHTML='';
                this.grid=$grid;
                this.MakeGrid();  
            }
        this.SceneClick=function(id)
            {
                if(this.Edited && !this.Moved)
                    {
                        if(this.params.ElemParams[id])
                            this.params.ElemParams[id].hide=this.params.ElemParams[id].hide ? false : true;
                        var e=document.getElementById('gridContent_'+this.params.objName+'_'+id);
                        if(this.params.ElemParams[id].hide)
                            {
                                removeClass(e,"btn-outline-primary");
                                removeClass(e,"alert-primary");
                                addClass(e,"btn-outline-danger");
                                addClass(e,"alert-danger");
                            }
                        else
                            {
                                addClass(e,"btn-outline-primary");
                                addClass(e,"alert-primary");
                                removeClass(e,"btn-outline-danger");
                                removeClass(e,"alert-danger");
                            }
                    }
                this.Moved=false;
            }

        this.MakeGrid=function()
            {
                if(document.getElementById(this.params.gridDivName+'_main').style.display=='none')
                    {
                    return;
                    }
                var elems=this.SavedParams.content;
                var Indexes=new Array();
                var Unindexes=new Array();

                $grid=this.grid;
                for (var j=0;j<elems.length;j++)
                    {
                        if(!elems[j])
                            continue;
                        for(var i=0;i<this.params.ElemParams.length;i++)
                        if(this.params.ElemParams[i].idx==elems[j].idx)
                            {
// aktualizujemy id elementu ktory bedzie pobierany z tablicy zgodnie z tym co sprawdzilismy - 
// jak dojda nowe elementy beda na innych pozycjach w tablicy ElemParams, a w pliku mamy zapisane stare pozycje.
                                elems[j].id=i;
                                Indexes[Indexes.length]=elems[j];
                                Unindexes[elems[j].id]=true;
                            }
                    }
                for(var i=0;i<this.params.ElemParams.length;i++)
                    {
                        if(!Unindexes[i])
                            Indexes[Indexes.length]={ id: i, hide: true };
                    }
                for(var idx=0;idx<Indexes.length;idx++)
                    {
                        var i=Indexes[idx].id;
                        this.params.ElemParams[i].hide=Indexes[idx].hide==false ? false : true;
                        var aclass="";
                        var eclass="btn-outline-primary alert-primary";
                        if(this.params.ElemParams[i].hide)
                            eclass="btn-outline-danger alert-danger";
                        if(this.params.ElemParams[i].height==2)
                            aclass='';
                
                        if(this.Edited || (!this.Edited && !this.params.ElemParams[i].hide))
                        {
                            var settings=this.params.showActions ? '<div class="row " style="position:absolute;right:20px;"><div class="hand mr-1" data-name="info" onclick="SuplaScenes.ShowWindowXajax(\'Charts\',\'_center\',{ id: \''+this.params.ElemParams[i].idx+'\' }, \'Wykres\');"><img src=img/chart.png width=14></div><div class="hand" data-name="info" onclick="SuplaScenes.ShowWindowXajax(\'Log\',\'_center\',{ id: \''+this.params.ElemParams[i].idx+'\' }, \'Log\');"><img src=img/settings.png width=14></div></div>' : '';
                        var $items = $('<div id=gridContent_'+this.params.objName+'_'+i+' class="grid-item btn '+eclass+' '+aclass+' m-1 '+this.params.ElemParams[i].width+'" onclick="'+this.params.objName+'.SceneClick('+i+');" onmousemove="Move=true;" data-name="'+this.params.ElemParams[i].idx+'" style="height:123px"><div style="position:absolute" class="" data-name="spin"></div>'+settings+this.params.ElemParams[i].content+'<div class="pt-1" id=gridContent_'+this.params.objName+'_channel_supla_cmd:'+this.params.ElemParams[i].idx+'></div></div>');
//                        if(!this.Edited && this.params.ElemParams[i].hide)
//                            var $items = $('<div id=gridContent_'+this.params.objName+'_'+i+' class="grid-item font-weight-bold alert alert-light m-2 p-2 '+this.params.ElemParams[i].width+'" data-name="'+this.params.ElemParams[i].idx+'" style="height:100px"></div>');
                        $grid.append( $items ).packery( 'appended', $items );
                        }
                    }
                        $grid.on( 'pointerDown',
                            function( event, draggedItem ) {
                            }
                        );
                        $grid.on( 'pointerMove',
                            function( event, draggedItem ) {
                            }
                        );
                if(this.Edited)
                    $grid.find('.grid-item').each( function( i, gridItem ) {
                        var draggie = new Draggabilly( gridItem );
                        $grid.packery( 'bindDraggabillyEvents', draggie );
                    });
                this.grid=$grid;
            }
        this.Run=function()
            {
                try{
                    var tpl=getCookie('SuplaScenesGrid'+this.params.objName);
                    }
                catch (err)
                    {
                    }
                if(!tpl)
                    tpl='';
//                if(this.SavedParams==null)
                this.SavedParams=this.GetNewSaved();

                document.getElementById(this.params.gridDivName).innerHTML='';
                $grid = $('.'+this.params.gridDivName).packery({
                    itemSelector: '.grid-item',
                    columnWidth: 50,
                });
                this.grid=$grid;
                this.MakeGrid();
                xajax_TemplatesLoad(this.params.objName,tpl);
                return tpl;
            }
        this.UpdateState=function(data,payload)
            {
                var content='';
                data=JSON.parse(data);
                payload=JSON.parse(payload);
                switch(data.type)
                    {
                        case 'LIGHTSWITCH' :
                            content ='<div class="text-center small alert-xs alert-light">'+(payload.on ? "włączone" : "wyłączone")+' '+(payload.on ? "<img src=img/light_on.png>" : "<img src=img/light_off.png>")+'</div>';
                            break;
                        case 'POWERSWITCH' :
                            content ='<div class="text-center small alert-xs alert-light">'+(payload.on ? "włączone" : "wyłączone")+' '+(payload.on ? "<img src=img/power_on.png>" : "<img src=img/power_off.png>")+'</div>';
                            break;
                        case 'CONTROLLINGTHEGATEWAYLOCK':
                        case 'CONTROLLINGTHEGATE':
                            content ='<div class="text-center small alert-xs alert-light">'+(payload.sensor_1 ? "zamknięta" : "otwarta")+' '+(payload.sensor_1 ? "<img src=img/gate_close.png>" : "<img src=img/gate_open.png>")+'</div>';
                            break;
                        case 'THERMOMETER':
                            content ='<h4><div class="text-center alert-xs alert-dark"><img src=img/temperature.png> '+payload.temperature.toFixed(2)+' <sup>o</sup>C</div></h4>';
                            break;
                    }
                var so=document.getElementById('gridContent_'+this.params.objName+'_channel_supla_cmd:'+data.id);
                if(so)
                    so.innerHTML=content;
            }
    }            
