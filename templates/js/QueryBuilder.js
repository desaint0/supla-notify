// JavaScript Document
QueryBuilder=function(obj)
    {
        this._element=obj.elem;
        this._argOnClick=obj.argOnClick;
        this._id=obj.id;
        this._obj=obj.obj;
        
        document.getElementById(this._element).innerHTML='';
        this.run=function(data)
            {
                if(!data)
                    this.addGroup(this._element);
                else
                for(var i=0;i<data.length;i++)
                    {
                        this.addGroup(this._element,data[i]);
                    }
            }
        this.addRule=function(e,data)
            {
                var elem=document.getElementById(e);
                var length = elem.childElementCount;
                var id=e+'_rule'+length;
                var eDiv=document.createElement("DIV");
                eDiv.className='alert-dark mt-1 alert-xs ';
                eDiv.id=id;
                var att=document.createAttribute("data-name");                 
                att.value='QB_rule';
                eDiv.setAttributeNode(att);
                
                var d='';
                d=d+'<div class="row pt-1 pb-1 small">';
                d=d+'<div class="col ml-1 nobr">';
                d=d+'<form id="'+id+'-form" class="">';

                d=d+'<div class="btn btn-xs btn-dark nobr small " data-toggle="buttons">';
                d=d+'<input type="checkbox" name="cond_NOT" class="small "> NOT';
                d=d+'</div> ';

                d=d+'<input name=arg type="text" size=35 class="p-1 form-control form-control-xs" placeholder="wybierz zmienną do badania..." value="" autocomplete="off" onkeyup="'+(this._argOnClick ? this._argOnClick+'.onKeyUp(this,event);' : '')+'" onfocus="'+(this._argOnClick ? this._argOnClick+'_onFocus(this);' : '')+'" onblur="'+(this._argOnClick ? this._argOnClick+'.onBlur(this);' : '')+'" data-name="QB_name">';
                d=d+' <select name=comp class="p-1 form-control form-control-xs">';
                d=d+'<option value="<"><';
                d=d+'<option value="<="><=';
                d=d+'<option value="==">==';
                d=d+'<option value=">=">>=';
                d=d+'<option value=">">>';
                d=d+'</select>';
                d=d+' <input name=value type=text size=5 class="p-1 form-control form-control-xs" placeholder="wprowadź wartość do porówania">';
                d=d+'</form>';
                d=d+'</div>';
                
                d=d+'<div class="col text-right">';
                d=d+' <a href=javascript:void(); class="btn btn-xs btn-danger " onclick="'+this._obj+'.delete(\''+id+'\');">usuń</a>';
                d=d+'</div>';
                d=d+'</div>';
                eDiv.innerHTML=d;
                elem.appendChild(eDiv);

                $.each(data,function(key,value) 
                    {
                        if(document.getElementById(id+'-form').elements[key].type=='checkbox')
                            {
                                addClass(document.getElementById(id+'-form').elements[key].parentNode,'active');
                                document.getElementById(id+'-form').elements[key].checked=value ? true : false;
                            }
                        else
                            document.getElementById(id+'-form').elements[key].value=value;
                    }
                );
                this.enDisCond(e);
            }
        this.addGroup=function(e,data)
            {
                var elem=document.getElementById(e);
                var length = elem.childElementCount;
                var id=e+'_group'+length;
                var eDiv=document.createElement("DIV");
                eDiv.className='pl-3 mt-1 alert-sm alert-warning border border-warning';
                eDiv.id=id;
                var att=document.createAttribute("data-name");                 
                att.value='QB_group';
                eDiv.setAttributeNode(att);
//                eDiv.style.borderColor='#d00070'; 

                var d='';
                d=d+'<div class="row pt-1 pb-1 nobr">';
                d=d+'<div class="col float-right  ">';

                d=d+'<div class="btn btn-xs btn-dark nobr small " data-toggle="buttons">';
                d=d+'<input type="checkbox" id="'+id+'cond_NOT" class="small " id="customCheck1"> NOT';
                d=d+'</div>';

                d=d+'<div class="btn-group btn-group-toggle " data-toggle="buttons">';
                d=d+'<label class="btn btn-xs btn-info disabled" onclick="document.getElementById(\''+id+'opt2\').value=\'AND\';">';
                d=d+'<input type="radio" name="cond" id="'+id+'cond_AND" autocomplete="off" data-name=QB_cond value="AND"> AND';
                d=d+'</label>';
                d=d+'<input type=hidden id="'+id+'opt2" value="">';
                d=d+'<label class="btn btn btn-xs btn-info disabled" onclick="document.getElementById(\''+id+'opt2\').value=\'OR\';">';
                d=d+'<input type="radio" name="cond" id="'+id+'cond_OR" autocomplete="off" data-name=QB_cond value="OR"> OR';
                d=d+'</label>';
                d=d+'</div>';                

                d=d+'</div>';
                d=d+'<div class="btn-group col float-right ">';
                d=d+' <a href=javascript:void(); class="btn btn-xs btn-success" onclick="'+this._obj+'.addRule(\''+id+'\');">dodaj regułę</a>';
                d=d+' <a href=javascript:void(); class="btn btn-xs btn-primary" onclick="'+this._obj+'.addGroup(\''+id+'\');">dodaj grupę</a>';
                d=d+' <a href=javascript:void(); class="btn btn-xs btn-danger" onclick="'+this._obj+'.delete(\''+id+'\');">usuń</a>';
//                d=d+'</div>';
                d=d+'</div>';
                d=d+'</div>';
                eDiv.innerHTML=d;
                elem.appendChild(eDiv);                
                this.enDisCond(e);

                if(data)
                    {
                        if(data.cond)
                            {
                                addClass(document.getElementById(id+'cond_'+data.cond).parentNode,'active');
                                document.getElementById(id+'opt2').value=data.cond;
                            }
                        if(data.not)
                            {
                                document.getElementById(id+'cond_NOT').checked=true;
                                addClass(document.getElementById(id+'cond_NOT').parentNode,'active');
                            }
                        for(var i=0;i<data.rules.length;i++)
                            {
                                if(!data.rules[i].rules)
                                    this.addRule(id,data.rules[i]);
                                else
                                    this.addGroup(id,data.rules[i]);
                            }
                    }
            }
        this.delete=function(e)
            {
                var d=document.getElementById(e);
                var p=d.parentNode;
                p.removeChild(d);
                this.enDisCond(p.id);
            }
        this.enDisCond=function(e)
            {
                var x=document.getElementById(e).querySelectorAll(`[data-name="QB_group"]`);
                var y=document.getElementById(e).querySelectorAll(`[data-name="QB_rule"]`);
                var length=x.length+y.length;                
                var k=document.getElementById(e).querySelectorAll(`[data-name="QB_cond"]`);
                $.each(k,function(key,value)
                    {
                            var re = new RegExp(e+'cond',"gi");
                            if(re.exec(value.id))
                            {
                                if(length<1)
                                    {
                                        addClass(value.parentNode,'disabled');
                                        value.parentNode.disabled=true;
                                    }
                                else
                                    {
                                        removeClass(value.parentNode,'disabled');
                                        value.parentNode.disabled=false;
                                    }
                            }
                    }
                );                                
            }
        this.parse=function(d)
            {
                var _array=new Array();
                var ch=d.childNodes;
                for (var i = 0; i < ch.length; i++) {
                  if($(ch[i]).attr('data-name')=='QB_rule')
                    {
                        var _rule=JSON.stringify($('#'+ch[i].id+'-form').serializeObject());
                        _array.push(JSON.parse(_rule));
                    }
                  if($(ch[i]).attr('data-name')=='QB_group')
                    {
                        var cond=document.getElementById(ch[i].id+'opt2').value;
                        var cond_not=document.getElementById(ch[i].id+'cond_NOT').checked;
                        var r=this.parse(ch[i]);
                        _array.push({ cond: cond, not: cond_not, rules: r});
                    }
                  
                }
                return _array;
            }
        this.save=function()
            {
                var f=document.getElementById('fin');
                var _array=this.parse(document.getElementById(this._element));

                return JSON.stringify(_array);
            }
    }