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

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'action.php');

class action_plugin_autotranslation extends DokuWiki_Action_Plugin {

    /**
     * For the helper plugin
     * @var helper_plugin_autotranslation
     */
    var $helper = null;

    var $locale;

    /**
     * Constructor. Load helper plugin
     */
    function __construct() {
        $this->helper =& plugin_load('helper', 'autotranslation');
    }

    /**
     * Register the events
     */
    function register(Doku_Event_Handler $controller) {
        $scriptName = basename($_SERVER['PHP_SELF']);

        // should the lang be applied to UI?
        if($this->getConf('translateui')) {
            switch($scriptName) {
                case 'js.php':
                    $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'translation_js');
                    $controller->register_hook('JS_CACHE_USE', 'BEFORE', $this, 'translation_jscache');
                    break;

                case 'ajax.php':
                    $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'translate_media_manager');
                    break;

                case 'mediamanager.php':
                    $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'setJsCacheKey');
                    break;

                default:
                    $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'setJsCacheKey');
            }
        }

        if($scriptName !== 'js.php' && $scriptName !== 'ajax.php') {
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'translation_hook');
            $controller->register_hook('MEDIAMANAGER_STARTED', 'BEFORE', $this, 'translation_hook');
        }

        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'translation_search');
        $controller->register_hook('COMMON_PAGETPL_LOAD', 'AFTER', $this, 'page_template_replacement');
    }

    /**
     * Hook Callback. Make current language available as page template placeholder and handle
     * original language copying
     *
     * @param $event
     * @param $args
     */
    function page_template_replacement(&$event, $args) {
        global $ID;

        // load orginal content as template?
        if($this->getConf('copytrans') && $this->helper->istranslatable($ID, false)) {
            // look for existing translations
            $translations = $this->helper->getAvailableTranslations($ID);
            if($translations) {
                // find original language (might've been provided via parameter or use first translation)
                $orig = (string) $_REQUEST['fromlang'];
                if(!$orig) $orig = array_shift(array_keys($translations));

                // load file
                $origfile = $translations[$orig];
                $event->data['tpl'] = io_readFile(wikiFN($origfile));

                // prefix with warning
                $warn = io_readFile($this->localFN('totranslate'));
                if($warn) $warn .= "\n\n";
                $event->data['tpl'] = $warn . $event->data['tpl'];

                // show user a choice of translations if any
                if(count($translations) > 1) {
                    $links = array();
                    foreach($translations as $t => $l) {
                        $links[] = '<a href="' . wl($ID, array('do' => 'edit', 'fromlang' => $t)) . '">' . $this->helper->getLocalName($t) . '</a>';
                    }

                    msg(
                        sprintf(
                            $this->getLang('transloaded'),
                            $this->helper->getLocalName($orig),
                            join(', ', $links)
                        )
                    );
                }

            }
        }

        // apply placeholders
        $event->data['tpl'] = str_replace('@LANG@', $this->helper->realLC(''), $event->data['tpl']);
        $event->data['tpl'] = str_replace('@TRANS@', $this->helper->getLangPart($ID), $event->data['tpl']);
    }

    /**
     * Hook Callback. Load correct translation when loading JavaScript
     *
     * @param $event
     * @param $args
     */
    function translation_js(&$event, $args) {
        global $conf;
        if(!isset($_GET['lang'])) return;
        if(!in_array($_GET['lang'], $this->helper->translations)) return;
        $lang = $_GET['lang'];
        $event->data = $lang;
        $conf['lang'] = $lang;
    }

    /**
     * Hook Callback. Pass language code to JavaScript dispatcher
     *
     * @param $event
     * @param $args
     * @return bool
     */
    function setJsCacheKey(&$event, $args) {
        if(!isset($this->locale)) return false;
        $count = count($event->data['script']);
        for($i = 0; $i < $count; $i++) {
            if(!empty($event->data['script'][$i]['src']) && strpos($event->data['script'][$i]['src'], '/lib/exe/js.php') !== false) {
                $event->data['script'][$i]['src'] .= '&lang=' . hsc($this->locale);
            }
        }

        return false;
    }

    /**
     * Hook Callback. Make sure the JavaScript is translation dependent
     *
     * @param $event
     * @param $args
     */
    function translation_jscache(&$event, $args) {
        if(!isset($_GET['lang'])) return;
        if(!in_array($_GET['lang'], $this->helper->translations)) return;

        $lang = $_GET['lang'];
        // reuse the constructor to reinitialize the cache key
        if(method_exists($event->data, '__construct')) {
            // New PHP 5 style constructor
            $event->data->__construct(
                $event->data->key . $lang,
                $event->data->ext
            );
        } else {
            // Old PHP 4 style constructor - deprecated
            $event->data->cache(
                $event->data->key . $lang,
                $event->data->ext
            );
        }
    }

    /**
     * Hook Callback. Translate the AJAX loaded media manager
     *
     * @param $event
     * @param $args
     */
    function translate_media_manager(&$event, $args) {
        global $conf;
        if(isset($_REQUEST['ID'])) {
            $id = getID();
            $lc = $this->helper->getLangPart($id);
        } elseif(isset($_SESSION[DOKU_COOKIE]['translationlc'])) {
            $lc = $_SESSION[DOKU_COOKIE]['translationlc'];
        } else {
            return;
        }
        if(!$lc) return;

        $conf['lang'] = $lc;
        $event->data = $lc;
    }

    /**
     * Hook Callback. Change the UI language in foreign language namespaces
     */
    function translation_hook(&$event, $args) {
        global $ID;
        global $lang;
        global $conf;
        global $ACT;
        // redirect away from start page?
        if($this->conf['redirectstart'] && $ID == $conf['start'] && $ACT == 'show') {
            $lc = $this->helper->getBrowserLang();
            if(!$lc) $lc = $conf['lang'];
            $this->_redirect($lc.':'.$conf['start']);
            exit;
        }

        // Check if we can redirect
        if($this->getConf('redirectlocalized')){
            $this->translation_redirect_localized();
        }

        // check if we are in a foreign language namespace
        $lc = $this->helper->getLangPart($ID);

        // store language in session (for page related views only)
        if(in_array($ACT, array('show', 'recent', 'diff', 'edit', 'preview', 'source', 'subscribe'))) {
            $_SESSION[DOKU_COOKIE]['translationlc'] = $lc;
        }
        if(!$lc) $lc = $_SESSION[DOKU_COOKIE]['translationlc'];
        if(!$lc) return;
        $this->locale = $lc;

        if(!$this->getConf('translateui')) {
            return true;
        }

        if(file_exists(DOKU_INC . 'inc/lang/' . $lc . '/lang.php')) {
            require(DOKU_INC . 'inc/lang/' . $lc . '/lang.php');
        }
        $conf['lang_before_translation'] = $conf['lang']; //store for later access in syntax plugin
        $conf['lang'] = $lc;

        return true;
    }

    /**
     * Hook Callback.  Resort page match results so that results are ordered by translation, having the
     * default language first
     */
    function translation_search(&$event, $args) {

        if($event->data['has_titles']) {
            // sort into translation slots
            $res = array();
            foreach($event->result as $r => $t) {
                $tr = $this->helper->getLangPart($r);
                if(!is_array($res["x$tr"])) $res["x$tr"] = array();
                $res["x$tr"][] = array($r, $t);
            }
            // sort by translations
            ksort($res);
            // combine
            $event->result = array();
            foreach($res as $r) {
                foreach($r as $l) {
                    $event->result[$l[0]] = $l[1];
                }
            }
        } else {
            # legacy support for old DokuWiki hooks

            // sort into translation slots
            $res = array();
            foreach($event->result as $r) {
                $tr = $this->helper->getLangPart($r);
                if(!is_array($res["x$tr"])) $res["x$tr"] = array();
                $res["x$tr"][] = $r;
            }
            // sort by translations
            ksort($res);
            // combine
            $event->result = array();
            foreach($res as $r) {
                $event->result = array_merge($event->result, $r);
            }
        }
    }

    /**
     * Redirects to the localized version of the page when showing and browser says so and translation was explicitly requested
     **/
    function translation_redirect_localized() {
        global $ID;
        global $conf;
        global $ACT;

        // redirect to localized page?
        if( $ACT != 'show' ) { return; }

        $override = isset($_REQUEST['tns']); // override enabled - comes from the bottom bar.
        $lang = !empty($conf['lang_before_translation']) ? $conf['lang_before_translation'] : $conf['lang']; // Check for original language

        // get current page language - if empty then default;
        $currentSessionLanguage = $_SESSION[DOKU_COOKIE]['translationcur'];
        $pageLang = $this->helper->getLangPart($ID);

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
            $currentSessionLanguage = $this->helper->getBrowserLang();

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
        list($newPage,$name) = $this->helper->buildTransID($pageLang,$this->helper->getIDPart($ID));
        $newPage = cleanID($newPage);

        // Check if Page exists
        if ( $newPage != $ID && page_exists($newPage, '', false) ) {
            // $newPage redirect
             
            if ( auth_quickaclcheck($newPage) < AUTH_READ ) { return; }

            session_write_close();
            $this->_redirect($newPage);
        }
        else
        if ( $override ) {
            // cleanup redirect
            session_write_close();
             
            if ( auth_quickaclcheck($newPage) < AUTH_READ ) { return; }

            $this->_redirect($ID);
        }

        // no redirect;
    }


    function _redirect($url)
    {
        unset($_GET['id']);
        $more = array();

        if ( !empty($_GET) ) {
            $params = '';
            foreach( $_GET as $key => $value ) {
                // Possible multiple encodings.
                $more[$key] = $value;
            }
        }
        
        if ( wl( $url, $more, true, '&') != DOKU_URL . substr($_SERVER['REQUEST_URI'], 1) ) {
            header('Location: ' . wl( $url, $more, true, '&'), 302);
            exit;
        }
    }
}

//Setup VIM: ex: et ts=4 :
