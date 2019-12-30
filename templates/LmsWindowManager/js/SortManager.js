//params;
//    ExecFunc: funkcja jaka ma byc wykonana po kliknieciu w naglowek kolumny, 
//    Elem: id glownego elementu w ktorym sa naglowki i dane, 
//    SortFunc: funkcja sortujaca - obiekt sort, 
//    SortName: nazwa pola po ktorym bedzie sortowanie
//    SortOrder: asc|desc, 
    
SortManager=function(params)
    {
        this.params=params;
        this.SortName=params.SortName ? params.SortName : '';
        this.SortOrder=params.SortOrder ? params.SortOrder : '';
        this.SortField={};

        this.ExecFunc=params.ExecFunc;
        this.Elem=params.Elem;
        this.Fields=params.Fields;
        this.SortFunc=params.SortFunc;

        this.makeSortField=function()
            {
                this.SortField={
                        name: this.SortName,
                        order: this.SortOrder,
                };            
            }
        this.Sort=function(n,o)
            {
                if(n)
                    this.SortName=n;
                if(o)
                    this.SortOrder=o;
                this.makeSortField();
                if(eval('typeof '+this.ExecFunc)=='function')
                    eval(this.ExecFunc+'()');        
            }
        this.getSortField=function()
            {
                return this.SortField;
            }
        this.CreateSortField=function()
            {
                var sort=this.SortName;
                var order=this.SortOrder;
                var e=document.getElementById(this.Elem);
                var table=e.getElementsByTagName("TABLE");
                var tr=table[0].getElementsByTagName("TR");
                for(var i=0;i<this.Fields.length;i++)
                    {
                        var eDiv=document.createElement("TD");
                                        
                        if(this.Fields[i].enabled)
                            {
                                eDiv.innerHTML="<A href=javascript:void(); onclick=\""+this.SortFunc+".Sort('"+this.Fields[i].id+"','"+(sort==this.Fields[i].id && order=='desc' ? 'asc' : 'desc')+"');\">"+this.Fields[i].name+"</A> ";
                                if(sort==this.Fields[i].id)
                                    eDiv.innerHTML+=order=='desc' ? '<img src=img/desc_order.gif>' : '<img src=img/asc_order.gif>';  
                            }
                        else
                            {
                                eDiv.innerHTML=this.Fields[i].name;
                            }
                        tr[0].appendChild(eDiv);
                    }
            }
        this.makeSortField();
    }
