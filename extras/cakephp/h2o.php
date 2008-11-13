<?php

/*

    H2oView for CakePHP 1.2

    Install : 
        please drop me into your app/view/ folder of your cake install

    Usage :
    
        // PagesController
          class PagesController extends AppController {
            var $view = 'h2o';
          
          }

*/

App::import('Vendor', array('h2o/h2o', 'cms_h2o_extension'));

class H2oView extends View {
    var $h2oAutoRender = false;
    var $h2oEnableCache = true;
    var $ext = '.html';
    var $autoLayout = false;
        
    function _render($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
    	
        $dirParts = explode(DS, $___viewFn);
        array_pop($dirParts);
        $templatePath = implode(DS, $dirParts);
        
        $options = array('template_path'    =>  $templatePath, 
                      'compile_path'    =>  H2O_COMPILE_PATH,
                      'auto_render' =>  $this->h2oAutoRender, 
                      'cache'           =>  $this->h2oEnableCache, 
                      'safeClass' => array('SimplePie_Item'),
        );
        $h2o = new H2O($___viewFn, $options);
        $viewVars =     &$___dataForView;
        $passVarsH2o = explode(',','here,base,action,name,pageTitle,layoutPath,webroot');
        foreach ($this->__passedVars as $var ){
            if (in_array($var, $passVarsH2o)){
                $viewVars[Inflector::underscore($var)] =  $this->{$var};
            }
        }
        $viewVars['data'] = $this->data;
        
        if ($this->helpers != false && $loadHelpers === true) {
            $loadedHelpers = array();
            $loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);

            foreach (array_keys($loadedHelpers) as $helper) {
                $replace = strtolower(substr($helper, 0, 1));
                $camelBackedHelper = preg_replace('/\\w/', $replace, $helper, 1);

                ${$camelBackedHelper} =& $loadedHelpers[$helper];

                if (is_array(${$camelBackedHelper}->helpers) && !empty(${$camelBackedHelper}->helpers)) {
                    $subHelpers = ${$camelBackedHelper}->helpers;
                    foreach ($subHelpers as $subHelper) {
                        ${$camelBackedHelper}->{$subHelper} =& $loadedHelpers[$subHelper];
                    }
                }
                $helper = $this->loaded[$camelBackedHelper] =& ${$camelBackedHelper};
                $h2o->set($camelBackedHelper, $helper);
                //$h2o->storage['helpers'][$camelBackedHelper] =  $helper;
            }
        }
        if ($this->helpers != false && $loadHelpers === true) {
            foreach ($loadedHelpers as $helper) {
                if (is_object($helper)) {
                    if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
                        $helper->beforeRender();
                    }
                }
            }
        }
        if(isset($myPaginate))
          $paginator = $myPaginate;
        $paginator->h2o_safe = array('prev', 'numbers', 'next');
        $viewVars['paginate'] = $paginator;

        
        $h2o->evaluate($viewVars);
        $___viewFn = $h2o->compiled_file;
        
        
        
        extract($___dataForView, EXTR_SKIP);
        $BASE = $this->base;
        $params =& $this->params;
        $page_title = $this->pageTitle;

        ob_start();

        if (Configure::read() > 0) {
            include ($___viewFn);
        } else {
            @include ($___viewFn);
        }

        if ($this->helpers != false && $loadHelpers === true) {
            foreach ($loadedHelpers as $helper) {
                if (is_object($helper)) {
                    if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
                        $helper->afterRender();
                    }
                }
            }
        }

        $out = ob_get_clean();

        if (isset($this->loaded['cache']) && (($this->cacheAction != false)) && (defined('CACHE_CHECK') && CACHE_CHECK === true)) {
            if (is_a($this->loaded['cache'], 'CacheHelper')) {
                $cache =& $this->loaded['cache'];

                if ($cached === true) {
                    $cache->view = &$this;
                }

                $cache->base            = $this->base;
                $cache->here            = $this->here;
                $cache->helpers         = $this->helpers;
                $cache->action          = $this->action;
                $cache->controllerName  = $this->name;
                $cache->layout  = $this->layout;
                $cache->cacheAction     = $this->cacheAction;
                $cache->cache($___viewFn, $out, $cached);
            }
        }
        
        //$this->output = $out;
        
        return $out;
    }
    
}
?>