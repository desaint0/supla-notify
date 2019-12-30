                var xajaxRequest;
                LmsWindowsManagerShowMenu=false;
                lmswmenux=null;
                                
                function LmswShowMenu()
                    {
                        LmsWindowsManagerShowMenu=!LmsWindowsManagerShowMenu;
                        lm=document.getElementById('lmswmenu');
                        lmp=document.getElementById('lmswmenuParent');
                        pl=document.getElementById('pageleftbar');

                                                
                        pl.style.display=LmsWindowsManagerShowMenu ? '' : 'none';

                        if(LmsWindowsManagerShowMenu)
                            {
                                lm.innerHTML='<span onclick=LmswShowMenu();><img src=templates/LmsWindowManager/css/menu.png> <img src=templates/LmsWindowManager/css/arrow_down.png></span>';
                            }
                        else
                            {
                                lm.innerHTML='<span onclick=LmswShowMenu();><img src=templates/LmsWindowManager/css/menu.png> <img src=templates/LmsWindowManager/css/arrow_right.png></span>';
                            }                        
                        LmsWindowsManager.SetXYofEl('pageleftbar','lmswstatusinside',5,document.getElementById('lmswstatusinside').offsetHeight+5,true);
                    }
                function ShowMenu()
                    {
                        pl=document.getElementById('pageleftbar');
                        pc=document.getElementById('pagecontent');
                        addClass(pl,'lmswmenubluroldstyle');
                        pl.style.display='';
                    }
                function ChangeMenu(w)
                    {
                        body=document.getElementsByTagName('body');
                        pl=document.getElementById('pageleftbar');
                        pc=document.getElementById('pagecontent');
                        lm=document.getElementById('lmswmenu');
                        lmp=document.getElementById('lmswmenuParent');

                        if(w)
                            {
                                removeClass(pl,'lmswmenubluroldstyle');
                                removeClass(pl,'lmswmenubluroldstyleanimate');
                                addClass(pl,'lmswmenuwindow');
                                addClass(pl,'lmswmenubluranimate');
                                addClass(pl,'lmswmenublur');
                                pl.style.display='none';
                                pc.style.marginLeft='2px';
                                lm.innerHTML='<span onclick=LmswShowMenu();><img src=templates/LmsWindowManager/css/menu.png> <img src=templates/LmsWindowManager/css/arrow_right.png></span>';
                                lmp.style.display='';
                                lmswmenux=pl.style.left;
                                lmswmenuy=pl.style.top;                                
                            }
                        else
                            {
                                removeClass(pl,'lmswmenuwindow');
                                removeClass(pl,'lmswmenublur');
                                removeClass(pl,'lmswmenubluranimate');
                                addClass(pl,'lmswmenubluroldstyle');
                                addClass(pl,'lmswmenubluroldstyleanimate');
                                pl.style.display='';
                                pc.style.marginLeft='172px';
                                lm.innerHTML='';
                                lmp.style.display='none';
                                
                                if(lmswmenux!=undefined)
                                {
                                pl.style.left=lmswmenux;
                                pl.style.top=lmswmenuy;
                                pl.style.padding='0px';
                                }
                                LmsWindowsManagerShowMenu=false;
                            }
                    }
                function FChangeMenu()
                    {
                        pl=document.getElementById('pageleftbar');
                        pc=document.getElementById('pagecontent');
                        pl.style.display='';                    
                        pc.style.marginLeft='172px';
                    }
                function ShowContent()
                    {
                        pc=document.getElementById('pagecontent');
                        pc.style.marginLeft='2px';
                    }
                function ParseSupout()
                    {
                        s=document.getElementById('lmswsupout').innerHTML;
                        if(s)
                            {
                                d=document.getElementById('lmswsupoutsend');
                                d.innerHTML=d.innerHTML+' <a href=javascript:void(); onclick=\"LmsWindowsManager.Question(\'Czy na pewno chcesz wysłać plik dziennika błędów do supportu LMSa?\',\'xajax_LmswSupout(s)\');\"><img src=templates/LmsWindowManager/css/email.png onmouseover=\"popup(\'Wyślij dziennik błędów do supportu LMSa\');\" onmouseout=pophide();></a>';
                            }
                    }
                function lmswSearchAction(w)
                    {
                        if(w)
                            {
                                document.getElementById('quicksearchmodule').style.display='none';
                                document.getElementById('quicksearchmodule').innerHTML='';
                                LmswSearch(false);
                                LmswSearchWindow.RollDownPanel();
                                document.getElementById('smallsearchParent').style.display='';
                            }
                        else
                            {
                                document.getElementById('smallsearchParent').style.display='none';
                                document.getElementById('quicksearchmodule').style.display='';
                                LmswSearchWindow.RollDownPanel();    
                                LmswSearch(true);            
                            }
                        document.getElementById('autosuggest').style.position = document.getElementById('quicksearchmodule').style.display=='none' ? 'absolute' : 'fixed';                            
                    }

                function xajaxWait(fname)
                    {
                        if(xajaxProcess)
                            {
//jesli jest juz jakis process ajaxa, to opozniamy wywolanie o jakis random 0..0.1s+0.1sek
                                setTimeout(fname+'()',Math.floor((Math.random() * 100) + 100));
                                return true;
                            }
                        return false;
                    }
                function showBreakdowns()
                    {   
                        breakDowns.ShowWindowXajax('display','lmswstatusinside',0,'Komunikaty');
                    }

                LmswSearchSet=function()
                    {
                        var el=document.getElementById(LmswSearchWindow.baseWindow);
                        el.style.display=='none' ? LmsWindowsManager.SetWindowOnTop(LmswSearchWindow.baseWindow) : LmswSearchWindow.RollDownPanel();
                        document.getElementById('autosuggest').style.display='none';
                        LmsWindowsManager.SetXYofEl(LmswSearchWindow.baseWindow,'smallsearchParent',5,100,true);
                        removeClass(el,'lmswmenubluranimate');
                        addClass(el,'lmswmenubluranimate');
                        document.getElementById('smallsearch').innerHTML=document.getElementById(LmswSearchWindow.baseWindow).style.display=='none' ? '<img src=templates/LmsWindowManager/css/search.png> <img src=templates/LmsWindowManager/css/arrow_right.png>' : '<img src=templates/LmsWindowManager/css/search.png> <img src=templates/LmsWindowManager/css/arrow_down.png>';                        
                    }
                
                function LmswSearch(jak)
                    {
                        LmswSearchWindow._globalreload=false;
                        LmswSearchWindow.disableInWM=true;                    
                        if(jak)
                            {
                                if(xajaxWait('LmswSearch'))
                                    return;
                                LmswSearchWindow.LoadingData=true;
                                LmswSearchWindow._callback='xajax_LmswSearch';
                                LmswSearchWindow.description='Szukaj';
                                LmswSearchWindow.CheckForResponse();
                                xajax_LmswSearch('quicksearchmodule',true);
                            }
                        if(jak==false)
                            LmswSearchWindow.ShowWindowXajax('LmswSearch','lmswsearchParent',0,'Szukaj');
                    }                    

                function GetBreakDowns()
                    {
                        if(xajaxWait('GetBreakDowns'))
                            return;
                        breakDowns.LoadingData=true;
                        breakDowns._callback='xajax_GetBreakDowns';
                        breakDowns.description='breakDowns status';
                        breakDowns.CheckForResponse();
                        xajax_GetBreakDowns('breakdowns');
                    }

                function ShowNagiosStatus()
                    {   
                        NagiosStatus.ShowWindowXajax('?m=mikrotikgroupunavail','lmswstatusinside',0,'Nagios Info');
                    }
                function ShowNagiosStatusFail()
                    {   
                        NagiosStatusFail.ShowWindowXajax('?m=netdevnagiosnm','_center',0,'Nagios Info - lista niemonitorowanych');
                    }


                function GetNagiosStatus()
                    {
                        if(xajaxWait('GetNagiosStatus'))
                            return;
                        NagiosStatus.LoadingData=true;
                        NagiosStatus._callback='xajax_GetNagiosStatus';
                        NagiosStatus.description='NagiosStatus';
                        NagiosStatus.CheckForResponse();
                        xajax_GetNagiosStatus('nagiosstatus');
                    }
                function LmswStatus_BreakDowns(jak)
                    {
                        document.getElementById('breakdownsParent').style.display=jak ? '' : 'none';
                        if(!jak)
                            clearInterval(BreakDownsCountInterval);
                        else
                            GetBreakDowns();
                    }
                function LmswStatus_NagiosStatus(jak)
                    {
                        document.getElementById('nagiosstatusParent').style.display=jak ? '' : 'none';
                        if(!jak)
                            clearInterval(NagiosStatusCountInterval);
                        else
                            GetNagiosStatus();
                    }

                QueueStatsAddQueue=function()
                    {
                        var d= {}
                        WebSocketServerF.addQueue('QueueStats',d);
                    }

                function LmswStatusQueuesRun(p)
                    {
                        if (typeof p=='undefined')
                            p={};
                        var st=getCookie('lmswSettingsQueues');
                        document.getElementById('lmswqueuesp').style.display=st=='1' ? '' : 'none';
                        if(st=='1')
                            {
                                if(!p.reset)
                                    WebSocketServerF.afterAuthCall('QueueStatsAddQueue();','QueueStats');
                                xajax_QueueStats('lmswqueues',getCookie('lmswSettingsQueuesId'));   
                            }
                        else
                            WebSocketServerF.delQueue('QueueStats');
                    }
//Workaround for including LmsWindows in -git version. 
    function ResetTimeout()
        {
            if(typeof LmsResetTimeout !='function')
                return;
            LmsResetTimeout();
        }        