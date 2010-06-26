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
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_hook');
        }
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'translation_search');
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

        // store language in session
        if($ACT == 'show') $_SESSION[DOKU_COOKIE]['translationlc'] = $lc;
        if(!$lc) $lc = $_SESSION[DOKU_COOKIE]['translationlc'];
        if(!$lc) return;

        if(file_exists(DOKU_INC.'inc/lang/'.$lc.'/lang.php')) {
          require(DOKU_INC.'inc/lang/'.$lc.'/lang.php');
        }
        $conf['lang_before_translation'] = $conf['lang']; //store for later access in syntax plugin
        $conf['lang'] = $lc;

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

//Setup VIM: ex: et ts=4 enc=utf-8 :
