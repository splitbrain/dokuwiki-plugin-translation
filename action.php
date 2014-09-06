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

class action_plugin_translation extends DokuWiki_Action_Plugin {

    /**
     * for th helper plugin
     * @var helper_plugin_translation
     */
    var $hlp = null;

    var $locale;

    /**
     * Constructor. Load helper plugin
     */
    function action_plugin_translation() {
        $this->hlp =& plugin_load('helper', 'translation');
    }

    /**
     * Registe the events
     */
    function register(&$controller) {
        // should the lang be applied to UI?
        $scriptName = basename($_SERVER['PHP_SELF']);

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
        if($this->getConf('copytrans') && $this->hlp->istranslatable($ID, false)) {
            // look for existing translations
            $translations = $this->hlp->getAvailableTranslations($ID);
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
                        $links[] = '<a href="' . wl($ID, array('do' => 'edit', 'fromlang' => $t)) . '">' . $this->hlp->getLocalName($t) . '</a>';
                    }

                    msg(
                        sprintf(
                            $this->getLang('transloaded'),
                            $this->hlp->getLocalName($orig),
                            join(', ', $links)
                        )
                    );
                }

            }
        }

        // apply placeholders
        $event->data['tpl'] = str_replace('@LANG@', $this->hlp->realLC(''), $event->data['tpl']);
        $event->data['tpl'] = str_replace('@TRANS@', $this->hlp->getLangPart($ID), $event->data['tpl']);
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
        if(!in_array($_GET['lang'], $this->hlp->trans)) return;
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
            if(strpos($event->data['script'][$i]['src'], '/lib/exe/js.php') !== false) {
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
        if(!in_array($_GET['lang'], $this->hlp->trans)) return;

        $lang = $_GET['lang'];
        // reuse the constructor to reinitialize the cache key
        $event->data->cache(
            $event->data->key . $lang,
            $event->data->ext
        );
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
            $lc = $this->hlp->getLangPart($id);
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
            $lc = $this->hlp->getBrowserLang();
            if(!$lc) $lc = $conf['lang'];
            header('Location: ' . wl($lc . ':' . $conf['start'], '', true, '&'));
            exit;
        }

        // check if we are in a foreign language namespace
        $lc = $this->hlp->getLangPart($ID);

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
                $tr = $this->hlp->getLangPart($r);
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
                $tr = $this->hlp->getLangPart($r);
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
     * Hook Callback. Display original language if translation not found
     */
    function translation_show_main_trans(&$event, $param) {
        global $ACT;
        if($ACT != 'show') return false;

        global $ID;
        if(page_exists($ID)) return false;

        // check if main namespace is a valid translation
        $mainNamespace = split(":", $ID)[0];
        if(array_search($mainNamespace, split(" ", $this->getConf('translations'))) !== false) {
            // get id of not translated page
            $notransID = ltrim($ID, "$mainNamespace:");
            // if not translated page exist
            if(page_exists($notransID)){
                // display 'totranslate.txt' warning
                echo p_render('xhtml',p_get_instructions(io_readFile($this->localFN('totranslate'))));
                // display not translated page
                $event->data = p_wiki_xhtml($notransID,'',false);
            }
        }
        return true;
    }
    
}

//Setup VIM: ex: et ts=4 :
