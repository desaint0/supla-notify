<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

class LMSSmarty extends Smarty {
	private $plugin_manager;
    var $_LMSWindowManagerActive=false;
    var $_LMSWindowTemplate=false;

	public function setPluginManager(LMSPluginManager $plugin_manager) {
		$this->plugin_manager = $plugin_manager;
	}

	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
        global $LMS, $LmsWindowManager;
		$layout = $this->getTemplateVars('layout');
		if (!empty($layout) && array_key_exists('module', $layout))
			$this->plugin_manager->ExecuteHook($layout['module'] . '_before_module_display',
				array('smarty' => $this));

#        if(!$this->getTemplateVars('xajax'))
#            if(!$this->_LMSWindowManagerActive)
#                $xajax=$LMS->RunXajax();
        if($xajax)
            $this->assign('xajax',$xajax);						                            
        if(is_object($LmsWindowManager) && (!$this->_LMSWindowManagerActive))
            if($lx=$LmsWindowManager->Run())
                $this->assign('lmswin_xajax',$lx);

        if($this->_LMSWindowManagerActive)
            {
                if($template=='noaccess.html')
                    $template='noaccessshort.html';
                if($template=='notfound.html')
                    $template='notfoundshort.html';

                $res=$this->fetch($template);
                $this->assign("LmsWindowsIncludeContent",$res);
                $template='LmsWindowManager/ModuleWindow.html';
                if($this->_LMSWindowTemplate)
                    $template='LmsWindowManager/'.$this->_LMSWindowTemplate.'.html';
            }
        if($error=$this->getTemplateVars('error'))
            {
                $e=new Exception();
                ErrorLogger::write(__FILE__,__LINE__,$e,$error);
            }
            
		parent::display($template, $cache_id, $compile_id, $parent);
	}
}

?>
