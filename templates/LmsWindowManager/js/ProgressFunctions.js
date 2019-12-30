function TimeRemained(p)
{
    this.PercentDiv=p.PercentDiv;      //div postepu %
    this.PercentClass=p.PercentClass ? p.PercentClass : ''; //css ststus bar postepu
    this.PercentBar=p.PercentBar==undefined ? true : p.PercentBar;   //czy pokazywac bar postepu
    this.ProgressTimeDiv=p.ProgressTimeDiv; //div dla czasu postepu 
    this.ProgressTime=p.ProgressTime==undefined ? true : p.ProgressTime; //czy pokazywac czas postepu, czas jaki pozostal
    this.ProgressTimeoutValue=p.ProgressTimeoutValue!=undefined ? p.ProgressTimeoutValue : 10000; //timeout pojedynczej operacji ajaxa
    this.ProgressElapsed=p.ProgressElapsed==undefined ? true : p.ProgressElapsed; // czy pokazywac czas jaki uplynal
        
    this.ProgressTimeout=null;
    this.TimeOut=false;
    this.TimeStart=0;
    this.TimeStartC=0;
    this.TimeStartS=0;
    this.czas=0;
    this.czgodz=0;
    this.czmin=0;
    this.czsek=0;
    this.curr=0;
    this.max=0;
    this.TimeCounter=0;
        
    var me=this;

    if(this.PercentBar)
        {
            e=document.getElementById(this.PercentDiv+'bar');
            t=document.createElement('table');
            t.style.width='100%';
            e.appendChild(t)
            var row=t.insertRow();
            var c1=row.insertCell(0);
            var c2=row.insertCell(1);
            c1.id=this.PercentDiv+'bar1';
            c2.id=this.PercentDiv+'bar2';
            c1.className=this.PercentClass+'1';
            c2.className=this.PercentClass+'2';            
            
            document.getElementById(this.PercentDiv+'bar1').style.display='none';
        }
            
    this.Time=function()
        {
            if(this.TimeStart==0)
                {        
                    this.TimeStart=new Date().getTime();
                    this.TimeStartS=this.TimeStart;
                    if(this.ProgressTime)
                        document.getElementById(this.ProgressTimeDiv).innerHTML='obliczanie...';
                }
            else
                this.TimeStart=this.TimeStartC;
            this.TimeStartC=new Date().getTime();                
        }
    this.TimeCount=function(czas)
        {
            if(czas<0)
                czas=0;
            this.czgodz=((czas-(czas%3600))/3600).toFixed(0);
            this.czmin=((czas-this.czgodz*3600));
            this.czmin=((this.czmin-this.czmin%60)/60).toFixed(0);
            this.czsek=(czas%60).toFixed(0);        
        }
    this.MakeTime=function(curr,max)
        {
            if(curr==0)
                return;

            var timeprev=this.czas*1000;
            var timecur=(this.TimeStartC-this.TimeStart)/(1)*(max-curr);            
            var timemid=(this.TimeStartC-this.TimeStartS)/(curr)*(max-curr);
//srednia z ostatniego czasu i biezacego
            var timeprevmid=(timeprev-0+(timecur))/2;
//generujemy czas ostatniej operacji i sredni z calego cyklu. biezemy pod uwage ten wiekszy
            this.czas=Math.max(timecur,timemid,timeprevmid);
                         
            this.czas=(this.czas/1000);
            this.TimeCount(this.czas);                            
            if(this.ProgressTime)
                {
                    if(this.TimeInterval==null)
                    {
                    document.getElementById(this.ProgressTimeDiv).innerHTML=this.Get();
                    this.TimeInterval=setInterval(function(){
                            me.czas--;
                            me.TimeCounter++;
                            me.TimeCount(me.czas);
                            document.getElementById(me.ProgressTimeDiv).innerHTML=me.Get();
                            if(me.ProgressElapsed)
                                {
                                    me.TimeCount(me.TimeCounter);
                                    document.getElementById(me.ProgressTimeDiv).innerHTML=document.getElementById(me.ProgressTimeDiv).innerHTML+'<BR>upłyneło: '+me.Get();
                                }
                        }
                    ,1000);
                    }            
                    if(curr==max)
                        {
                            clearInterval(this.TimeInterval);
                            document.getElementById(this.ProgressTimeDiv).innerHTML='---';
                        }
                }
        }
    
    this.Get=function()
        {
            return this.czgodz+' godzin, '+this.czmin+' minut, '+this.czsek+' sekund';        
        }
    this.ShowPercent=function(curr,max)
        {
            this.curr=curr;
            this.max=max;
            if(max==0)
                {
                    document.getElementById(this.PercentDiv).innerHTML=0;
                    if(this.PercentBar)
                        {
                            document.getElementById(this.PercentDiv+'bar1').style.width='0%';
                        }
                }
            if(curr<=max)
                {
                    var perc=((curr)/max*100).toFixed(0);
                    document.getElementById(this.PercentDiv).innerHTML=perc;
                    if(this.PercentBar)
                        {
                            document.getElementById(this.PercentDiv+'bar1').style.width=perc+'%';
                            document.getElementById(this.PercentDiv+'bar2').style.width=(100-perc)+'%';
                            if(perc>0)
                                document.getElementById(this.PercentDiv+'bar1').style.display='';
                            if(perc==100)
                                document.getElementById(this.PercentDiv+'bar2').style.display='none';
                        }
                }
        }
            
    this.ProgressTimeoutFunc=function(elem1,elem2)
        {
            me.ProgressTimeout=setTimeout(function(){
                if(elem1)
                    document.getElementById(elem1).innerHTML="<div class=alertLMS style='padding-top:7px;height:20px;width:99%'>Wystąpił błąd podczas wykonywania operacji "+(me.curr)+" z "+(me.max)+"</div>";
                if(elem2)
                    document.getElementById(elem2).innerHTML='<B>Błąd.</B>';
                me.TimeOut=true;
                clearTimeout(me.TimeInterval);
            }, this.ProgressTimeoutValue);
        }
    this.ClearTimeout=function(t)
        {
            clearTimeout(this.ProgressTimeout);
            if(t)
                {
                    this.TimeOut=true;        
                    clearTimeout(this.TimeInterval);
                }
        }
    this.Show=function(curr,max)
        {
            this.ShowPercent(curr,max);
            this.MakeTime(curr,max);
            if(curr==max)
                this.ClearTimeout(true);
        }
    
}
