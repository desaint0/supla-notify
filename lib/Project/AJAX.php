<?php
namespace Supla\Project;

class AJAX
{
    private static $obj=null;
    function __construct()
        {
            $this->InitXajax();
            self::$obj=$this;
        }
    
    public static function getObj()
        {
            return self::$obj;
        }
    public function InitXajax()
    {
        if (!$this->xajax) {
            require(LIB_DIR . '/xajax/xajax_core/xajax.inc.php');
            $this->xajax = new \xajax();
            $this->xajax->configure('errorHandler', true);
            $this->xajax->configure('javascript URI', 'img');
        }
    }

    public function RunXajax()
    {
        $xajax_js = NULL;
        if ($this->xajax) {
            $xajax_js = $this->xajax->getJavascript();
            $this->xajax->processRequest();
        }
        return $xajax_js;
    }
    public function RegisterXajaxFunction($funcname,$conf=null)
    {
        if ($this->xajax) {
            if (is_array($funcname))
                foreach ($funcname as $func)
                    $this->xajax->register(XAJAX_FUNCTION, $func, $conf);
            else
                $this->xajax->register(XAJAX_FUNCTION, $funcname, $conf);
        }
    }

   public function RegisterHook($hook_name, $callback)
    {
        $this->hooks[] = array(
            'name' => $hook_name,
            'callback' => $callback,
        );
    }

    public function ExecHook($hook_name, $vars = null)
    {
        foreach ($this->hooks as $hook) {
            if ($hook['name'] == $hook_name) {
                $vars = call_user_func($hook['callback'], $vars);
            }
        }

        return $vars;
    }

    /**
     * Sets plugin manager
     * 
     * @param LMSPluginManager $plugin_manager Plugin manager
     */
    public function setPluginManager(LMSPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Executes hook
     * 
     * @param string $hook_name Hook name
     * @param mixed $hook_data Hook data
     * @return mixed Modfied hook data
     */
    public function executeHook($hook_name, $hook_data = null)
    {
        return $this->plugin_manager->executeHook($hook_name, $hook_data);
    }
        
}
?>