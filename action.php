<?php
/**
 * Info Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Guy Brand <gb@isis.u-strasbg.fr>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_translation extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * Registe the events
     */
    function register(&$controller) {
        // should the lang be applied to UI?
        if($this->getConf('translateui')){
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_hook');
        }
    }

    /**
     * Change the UI language in foreign language namespaces
     */
    function translation_hook(&$event, $args) {
        global $ID;
        global $lang;
        global $conf;

        // get an instance of the syntax plugin
        $translation = &plugin_load('syntax','translation');

        // check if we are in a foreign language namespace
        $lc = $translation->_currentLang();
        if(!$lc) return;

        if(file_exists(DOKU_INC.'inc/lang/'.$lc.'/lang.php')) {
          require(DOKU_INC.'inc/lang/'.$lc.'/lang.php');
        }
        $conf['lang_before_translation'] = $conf['lang']; //store for later access in syntax plugin
        $conf['lang'] = $lc;

        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
