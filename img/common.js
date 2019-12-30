// JavaScript Document
function addClass(theElem, theClass)
{
	theElem.className += ' ' + theClass;
}

function removeClass(theElem, theClass)
{
	regexp = new RegExp('\\s*' + theClass, 'ig');
	var str = theElem.className;
	theElem.className = str.replace(regexp, '');
}

SUPLA_MenuID='';
function SUPLA_Menu(id)
    {
        if(SUPLA_MenuID)
            {
                var d=document.getElementById(SUPLA_MenuID);
                if(d)
                    {
                        removeClass(d,'active');
                    }
            }
        addClass(document.getElementById(id),'active');
        SUPLA_MenuID=id;
//        document.getElementById('page_content').innerHTML='';
    }
lms_sticky_popup=false;
function popup(content, frame, sticky, offset_x, offset_y,scrolling,ifid)
{
    $('#overDiv').stop(true,true);
    if (scrolling==null) {scrolling='no';}
    if (ifid==null) {ifid='autoiframe';}
    if (lms_sticky_popup)
        return;

    if (frame) {
        content = '<iframe id="'+ifid+'" width=100 height=10 frameborder=0 scrolling='+scrolling+' '
            +'src="'+content+'&popup=1"></iframe>';
    }
            
    if (!offset_x) offset_x = 15;
    if (!offset_y) offset_y = 15;
    ol_followmouse=1;
    if (sticky) {
//ponizej na koncu MOUSEOFF - zamyka okienko jak myszka zjedzie z niego
        overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y, STICKY);
       	var body = document.getElementsByTagName('BODY')[0];
        body.onmousedown = function () { popclick(); };
        lms_sticky_popup = 1;
        $('#overDiv').show();
    }
    else {
        overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y);
        d=document.getElementById('overDiv');
        od=d.getElementsByTagName('div');
        od[0].className='overlibsmall';
        $('#overDiv').show();
        overlib(o3_text);
        od[0].className='overlibsmall';
    }
}

// Hide non-sticky popup
function pophide()
{
    if (lms_sticky_popup) {
        return;
    }
    ol_followmouse=0;
    overlib(o3_text);
        d=document.getElementById('overDiv');
        od=d.getElementsByTagName('div');
        od[0].className='overlibsmallna';
    $('#overDiv').fadeOut(500);
//    return nd();
}

// Hide sticky popup
function popclick()
{
    $('#overDiv').fadeOut(500);
    lms_sticky_popup = 0;
    o3_removecounter++;
//    return nd();
}

function setCookie(name, value)
{
        document.cookie = name + '=' + escape(value);
}

function getCookie(name) 
{
        var cookies = document.cookie.split(';');
	for (var i=0; i<cookies.length; i++) 
	{
		var a = cookies[i].split('=');
                if (a.length == 2)
		{
            		a[0] = a[0].trim();
                	a[1] = a[1].trim();
                	if (a[0] == name)
			{
                    		return unescape(a[1]);
			}
                }
        }
        return null;
}
    