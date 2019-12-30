    function LmsTimeout()
        {
            if(lmsTimeoutLocal)
                {
                    if(lmsTimeoutCounter!=getCookie('lmsTimeoutSess'))
                        lmsTimeoutLocal=false;
                    else
                        {
                            lmsTimeoutCounter--;
                            setCookie('lmsTimeoutSess',lmsTimeoutCounter);
                        }
                }
            else
                {
                    if(lmsTimeoutCounter==getCookie('lmsTimeoutSess'))
                        lmsTimeoutLocal=true;
                    lmsTimeoutCounter=getCookie('lmsTimeoutSess');
                }
            if(lmsTimeoutCounter<0)
        	{
        	    clearInterval(lmsTimeoutInterval);
            	    window.location.href='?m=logout&is_sure=1';
            	}
            else
                LmsTimeoutWrite(lmsTimeoutCounter);
        }
        
    function LmsTimeoutWrite(tmptout)
        {
            var min=(tmptout-(tmptout%60))/60;
            var sec=tmptout%60;
            $('#lmswsesstimer').html((min<10 ? '0' : '')+min.toFixed(0)+':'+(sec<10 ? '0' : '')+sec);        
        }
    function LmsResetTimeout()
        {
            if (!lmsSessionControl)
                return;
            clearInterval(lmsTimeoutInterval);
            lmsTimeoutLocal=true;
            lmsTimeoutCounter=lmsTimeout;
            setCookie('lmsTimeoutSess',lmsTimeout);
            xajax_UpdateSession();
            LmsTimeoutWrite(lmsTimeoutCounter);
            lmsTimeoutInterval=setInterval('LmsTimeout()',1000);
        }

    LmsTimeoutWrite(lmsTimeout);
    var lmsTimeoutCounter=lmsTimeout;
    var lmsTimeoutLocal=true;
    var lmsTimeoutInterval;
    var lmsSessionControl=false;
