if (xajax)
{
    xajax.uploaded=new Array();
    xajax.uploadedOrigFields=new Array();
    xajax.uploadedOrigFieldsPR=new Array();
    
    xajax.initFileInputs = function (wName)
    {
        xajax.uploaded[wName]=0;
        inputs = document.getElementsByTagName('input');
        for( var i=0; i < inputs.length; i++)
        {
            inp=inputs[i];
            if (!inp.className)
                continue; //doesnt have a class defined
            if (inp.className.indexOf('xajax_file')==-1)
                continue; //not an xajax file upload
            if (inp.style.visibility=='hidden')
                continue; //already converted this file upload
            xajax.newFileUpload(inp.id, inp.id+'-'+xajax.workId,wName);
            inp.style.visibility = 'hidden';
            inp.style.height = '0';
            inp.style.width = '0';
        }
    }
    xajax.newFileUpload = function(sParentId, sId, wName)
    {
        this.uploaded[wName]++;
        xajax.dom.insertAfter(sParentId, 'iframe', sId);
        newFrame = xajax.$(sId);
        newFrame.name=sId;
        newFrame.style.height="35px";
        newFrame.style.height="35";
        newFrame.style.width="300";
        newFrame.style.overflow="hidden";
        newFrame.position="relative";
        newFrame.scrolling="no";
        newFrame.allowtransparency=true;
        newFrame.style.backgroundColor="transparent";
        //need to wait for Mozilla to notice there's an iframe
        setTimeout('xajax._fileUploadContinue("'+sId+'","'+sParentId+'","'+wName+'");', 20);
    }
    xajax._fileUploadContinue = function(sId,sParentId, wName)
    {
        //uploadIframe = window.frames[sId];
        uploadIframe = xajax.$(sId);
        if (!uploadIframe.contentDocument)
        {
            //fix for internet explorer
            uploadIframe.contentDocument = window.frames[sId].document;
        }
        uploadIframe.contentDocument.body.style.backgroundColor="transparent";
        uploadIframe.contentDocument.xajax=this;
//        uploadIframe.contentDocument.body.innerHTML='<span id="workId" style="font-size:0px;height: 0px;position:absolute;">'+xajax.workId+'</span><form style="position:absolute;top:0;left:0;height:98%;width:98%;margin:0;padding:0;overflow:hidden;" name="iform" action="'+xajax.config.requestURI+'" method="post" enctype="multipart/form-data"><input id="file" type="file" name="file" onchange="document.xajax._fileUploading(\''+sParentId+'\', \''+sId+'\',\''+wName+'\');"/><input type="hidden" name="xajax" value="XajaxFileUpload" /></form>';
        uploadIframe.contentDocument.body.innerHTML='<link href="img/style.css" rel="stylesheet" type="text/css"><span id="workId" style="font-size:0px;height: 0px;position:absolute;">'+xajax.workId+'</span><form style="position:absolute;top:0;left:0;height:98%;width:98%;margin:0;padding:0;" name="iform" action="'+xajax.config.requestURI+'" method="post" enctype="multipart/form-data"><div class=upload><input id="file" type="file" name="file" onchange="document.xajax._fileUploading(\''+sParentId+'\', \''+sId+'\',\''+wName+'\');document.iform.submit();" onmouseoutx="if(this.value)document.iform.submit();"/></div><input type="hidden" name="xajax" value="XajaxFileUpload" /></form>';
        uploadIframe.style.border='0';
    }
    xajax._fileUploading = function(sParentId,sId,wName)
    {
        uploadIframe = xajax.$(sId);
        xajax.dom.insertAfter(sId, 'div', sId+'-progress');
        uploadProgress = xajax.$(sId+'-progress');
        uploadIframe.style.visibility='hidden';
        uploadIframe.style.width='0';
        uploadIframe.style.height='0';
        uploadProgress.innerHTML='Trwa ladowanie pliku... <img src=img/wait1.gif>';
        uploadProgress.className="xajax_file";
        setTimeout('xajax._fileProgressCheck("'+sParentId+'","'+sId+'","'+wName+'");', 100);
    }
    xajax._fileProgressCheck = function(sParentId,sId,wName)
    {
        uploadIframe[wName] = xajax.$(sId);
        if (!uploadIframe[wName].contentDocument)
        {
            //fix for internet explorer
            uploadIframe[wName].contentDocument = window.frames[sId].document;
        }
        uploadProgress = xajax.$(sId+'-progress');
        if (uploadIframe[wName].contentDocument.body )
            {
                this.uploadedOrigFieldsPR[wName]=uploadIframe[wName].contentDocument.body.innerHTML;
                if (uploadIframe[wName].contentDocument.body.innerHTML.indexOf('UPLOAD DONE') !== -1)
                    {
                        this.uploaded[wName]--;
                        this.uploadedOrigFieldsPR[wName]=this.uploadedOrigFieldsPR[wName].replace('UPLOAD DONE','');
                        if(this.uploadedOrigFieldsPR[wName])
                            this.uploadedOrigFields[wName]=this.uploadedOrigFields[wName] + '\n'+this.uploadedOrigFieldsPR[wName];
                        this.uploadedOrigFieldsPR[wName]=JSON.parse(this.uploadedOrigFieldsPR[wName]);
                        if(this.uploadedOrigFieldsPR[wName].file.error==0)
                            uploadProgress.innerHTML='<span class="lmswsymbols lmswborder1 lmswok"></span> Pomyslnie zaladowano plik <B>'+this.uploadedOrigFieldsPR[wName].file.name+'</B>';
                        else
                            uploadProgress.innerHTML='<span class="lmswsymbols lmswborder1 lmswerr"></span> Nie powiodlo sie zaladowanie pliku <B>'+this.uploadedOrigFieldsPR[wName].file.name+'</B>';
                        return;
                    } 
                else
                    {
                        setTimeout('xajax._fileProgressCheck("'+sParentId+'","'+sId+'","'+wName+'");', 300);
                        return;
                    }
            }
    }
}