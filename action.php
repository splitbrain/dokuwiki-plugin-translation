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
        if($this->getConf('redirectstart')){
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_redirect_start');
        }
        if($this->getConf('redirectlocalized')){
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_redirect_localized');
        }
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'translation_search');
    }

    function translation_redirect_start(&$event, $args) {
        global $ID;

        // redirect away from start page?
        if( $ID == $conf['start'] && $ACT == 'show' ){
            $lc = $this->hlp->getBrowserLang();
            if(!$lc) $lc = $conf['lang'];
            header('Location: '.wl($lc.':'.$conf['start'],'',true,'&'));
            exit;
        }
    }

    function translation_redirect_localized(&$event, $args) {
        global $ID;
        global $conf;
        global $ACT;

        // redirect to localized page?
        if( $ACT != 'show' ) { return; }

        $override = (!empty($_REQUEST['s']) && $_REQUEST['s'] == 'translation_true'); // override enabled - comes from the bottom bar.
        $lang = $conf['lang_before_translation'] ? $conf['lang_before_translation'] : $conf['lang']; // Check for original language

        // get current page language - if empty then default;
        $currentSessionLanguage = $_SESSION[DOKU_COOKIE]['translationcur'];
        $pageLang = $this->hlp->getLangPart($ID);

        if ( empty($pageLang) ) {
            $pageLang = $lang;
        }

        // If both match, we're fine.
        if ( $currentSessionLanguage == $pageLang ) {
            return;
        }

        // check current translation
        if ( empty( $currentSessionLanguage ) && !$override ) {

            // If not set - we must just have entered - set the browser language
            $currentSessionLanguage = $this->hlp->getBrowserLang();

            // if no browser Language set, take entered namespace language - empty for default.
            if ( !$currentSessionLanguage ) {
                $currentSessionLanguage = $pageLang;
            }

            // Set new Language
            $_SESSION[DOKU_COOKIE]['translationcur'] = $currentSessionLanguage;

            // Write Language back
            $pageLang = $currentSessionLanguage;
        }


        if ( $override && $pageLang != $currentSessionLanguage ) {
            // Set new Language
            $currentSessionLanguage = $pageLang;
            $_SESSION[DOKU_COOKIE]['translationcur'] = $currentSessionLanguage;
        } else if ( !$override ) {
            // Write Language back
            $pageLang = $currentSessionLanguage;
        }

        // If this is the default language, make empty
        if ( $pageLang == $lang ) {
            $pageLang = '';
        }

        // Generate new Page ID
        $rx = '/^'.$this->hlp->tns.'('.join('|',$this->hlp->trans).'):/';
        $idpart = preg_replace($rx,'',$ID);
        list($newPage,$name) = $this->hlp->buildTransID($pageLang,$idpart);
        $newPage = cleanID($newPage);

        // Check if Page exists
        if ( $newPage != $ID && page_exists($newPage, '', false) ) {
            // $newPage redirect
            	
            if ( auth_quickaclcheck($newPage) < AUTH_READ ) { return; }

            session_write_close();
            header('Location: '.wl($newPage,'',true,'&'));
            exit;
        }
        else
        if ( $override ) {
            // cleanup redirect
            session_write_close();
            	
            if ( auth_quickaclcheck($newPage) < AUTH_READ ) { return; }

            header('Location: '.wl($ID,'',true,'&'), 301);
            exit;
        }

        // no redirect;
    }

    /**
     * Change the UI language in foreign language namespaces
     */
    function translation_hook(&$event, $args) {
        global $ID;
        global $lang;
        global $conf;
        global $ACT;

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

//Setup VIM: ex: et ts=4 enc=utf-8 :
