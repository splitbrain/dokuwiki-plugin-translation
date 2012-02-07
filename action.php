<?php
/**
 * Translation Plugin: Simple multilanguage plugin
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
     * for th helper plugin
     */
    var $hlp = null;

    var $locale;

    /**
     * Constructor. Load helper plugin
     */
    function action_plugin_translation(){
        $this->hlp =& plugin_load('helper', 'translation');
    }

    /**
     * Registe the events
     */
    function register(&$controller) {
        // should the lang be applied to UI?
        if($this->getConf('translateui')){
            $scriptName = basename($_SERVER['PHP_SELF']);

            switch ($scriptName) {
                case 'js.php':
                    $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'translation_js');
                    break;

                case 'ajax.php':
                    $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'translate_media_manager');
                    break;

                case 'mediamanager.php':
                    $controller->register_hook('MEDIAMANAGER_STARTED', 'BEFORE', $this, 'translation_hook');
                    $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'setJsCacheKey');
                    break;

                default:
                    $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_hook');
                    $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'setJsCacheKey');
            }
        }
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'translation_search');
    }

    function setJsCacheKey(&$event, $args) {
        if (!isset($this->locale)) return false;
        $count = count($event->data['script']);
        for ($i = 0; $i<$count; $i++) {
            if (strpos($event->data['script'][$i]['src'], '/lib/exe/js.php') !== false) {
                $event->data['script'][$i]['src'] .= "&cacheKey=$this->locale";
            }
        }

        return false;
    }

    function translation_js(&$event, $args) {
        global $conf;
        if (!isset($_GET['cacheKey'])) return false;

        $key = $_GET['cacheKey'];
        $event->data = $key;
        $conf['lang'] = $key;
        return false;
    }

    function translate_media_manager(&$event, $args) {
        global $conf;
        if (isset($_REQUEST['ID'])) {
            $id = getID();
            $lc = $this->hlp->getLangPart($id);
        } elseif (isset($_SESSION[DOKU_COOKIE]['translationlc'])) {
            $lc = $_SESSION[DOKU_COOKIE]['translationlc'];
        } else {
            return false;
        }
        $conf['lang'] = $lc;
        $event->data = $lc;
        return false;
    }

    /**
     * Change the UI language in foreign language namespaces
     */
    function translation_hook(&$event, $args) {
        global $ID;
        global $lang;
        global $conf;
        global $ACT;
        // redirect away from start page?
        if($this->conf['redirectstart'] && $ID == $conf['start'] && $ACT == 'show'){
            $lc = $this->hlp->getBrowserLang();
            if(!$lc) $lc = $conf['lang'];
            header('Location: '.wl($lc.':'.$conf['start'],'',true,'&'));
            exit;
        }

        // check if we are in a foreign language namespace
        $lc = $this->hlp->getLangPart($ID);

        // store language in session (for page related views only)
        if(in_array($ACT,array('show','recent','diff','edit','preview','source','subscribe'))){
            $_SESSION[DOKU_COOKIE]['translationlc'] = $lc;
        }
        if(!$lc) $lc = $_SESSION[DOKU_COOKIE]['translationlc'];
        if(!$lc) return;

        if(file_exists(DOKU_INC.'inc/lang/'.$lc.'/lang.php')) {
          require(DOKU_INC.'inc/lang/'.$lc.'/lang.php');
        }
        $conf['lang_before_translation'] = $conf['lang']; //store for later access in syntax plugin
        $conf['lang'] = $lc;
        $this->locale = $lc;

        return true;
    }

    /**
     * Resort page match results so that results are ordered by translation, having the
     * default language first
     */
    function translation_search(&$event, $args) {

        if($event->data['has_titles']){
            // sort into translation slots
            $res = array();
            foreach($event->result as $r => $t){
                $tr = $this->hlp->getLangPart($r);
                if(!is_array($res["x$tr"])) $res["x$tr"] = array();
                $res["x$tr"][] = array($r,$t);
            }
            // sort by translations
            ksort($res);
            // combine
            $event->result = array();
            foreach($res as $r){
                foreach($r as $l){
                    $event->result[$l[0]] = $l[1];
                }
            }
        }else{
            # legacy support for old DokuWiki hooks

            // sort into translation slots
            $res = array();
            foreach($event->result as $r){
                $tr = $this->hlp->getLangPart($r);
                if(!is_array($res["x$tr"])) $res["x$tr"] = array();
                $res["x$tr"][] = $r;
            }
            // sort by translations
            ksort($res);
            // combine
            $event->result = array();
            foreach($res as $r){
                $event->result = array_merge($event->result,$r);
            }
        }
    }

}

//Setup VIM: ex: et ts=4 :
