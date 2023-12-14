<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Guy Brand <gb@isis.u-strasbg.fr>
 */
class action_plugin_translation extends ActionPlugin
{
    /**
     * For the helper plugin
     * @var helper_plugin_translation
     */
    protected $helper;

    /**
     * Constructor. Load helper plugin
     */
    public function __construct()
    {
        $this->helper = plugin_load('helper', 'translation');
    }

    /**
     * Registers a callback function for a given event
     *
     * @param EventHandler $controller
     */
    public function register(EventHandler $controller)
    {
        if ($this->getConf('translateui')) {
            $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'translateUI');
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'translateJS');
            $controller->register_hook('JS_CACHE_USE', 'BEFORE', $this, 'translateJSCache');
        } else {
            $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'addLanguageAttributes');
        }

        if ($this->getConf('redirectstart')) {
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'redirectStartPage');
        }

        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'addHrefLangAttributes');
        $controller->register_hook('COMMON_PAGETPL_LOAD', 'AFTER', $this, 'handlePageTemplates');
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'sortSearchResults');
    }

    /**
     * Hook Callback. Set the language for the UI
     *
     * @param Event $event INIT_LANG_LOAD
     */
    public function translateUI(Event $event)
    {
        global $conf;
        global $ACT;
        global $INPUT;

        // $ID is not set yet, so we have to get it ourselves
        $id = getID();

        // For ID based access, get the language from the ID, try request param or session otherwise
        if (
            isset($ACT) &&
            in_array(act_clean($ACT), ['show', 'recent', 'diff', 'edit', 'preview', 'source', 'subscribe'])
        ) {
            $locale = $this->helper->getLangPart($id ?? '');
            $_SESSION[DOKU_COOKIE]['translationlc'] = $locale; // IDs always reset the language
        } elseif ($INPUT->has('lang')) {
            $locale = $INPUT->str('lang');
        } else {
            $locale = $_SESSION[DOKU_COOKIE]['translationlc'] ?? '';
        }

        // ensure a valid locale was given by using it as fake ID
        $locale = $this->helper->getLangPart("$locale:foo");

        // if the language is not the default language, set the language
        if ($locale && $locale !== $conf['lang']) {
            $conf['lang_before_translation'] = $conf['lang']; //store for later access in syntax plugin
            $event->data = $locale;
            $conf['lang'] = $locale;
        }
    }

    /**
     * Hook Callback. Pass language code to JavaScript dispatcher
     *
     * @param Event $event TPL_METAHEADER_OUTPUT
     */
    public function translateJS(Event $event)
    {
        global $conf;

        $count = count($event->data['script']);
        for ($i = 0; $i < $count; $i++) {
            if (
                array_key_exists('src', $event->data['script'][$i]) &&
                strpos($event->data['script'][$i]['src'], '/lib/exe/js.php') !== false
            ) {
                $event->data['script'][$i]['src'] .= '&lang=' . hsc($conf['lang']);
            }
        }
    }

    /**
     * Hook Callback. Cache JavaScript per language
     *
     * @param Event $event JS_CACHE_USE
     */
    public function translateJSCache(Event $event)
    {
        global $conf;

        // reuse the constructor to reinitialize the cache key
        $event->data->__construct(
            $event->data->key . $conf['lang'],
            $event->data->ext
        );
    }

    /**
     * Hook Callback. Add lang and dir attributes when UI isn't translated
     *
     * @param Event $event TPL_CONTENT_DISPLAY
     */
    public function addLanguageAttributes(Event $event)
    {
        global $ID;
        global $conf;

        if (!$this->helper->istranslatable($ID)) return;
        $locale = $this->helper->getLangPart($ID ?? '');

        if ($locale && $locale !== $conf['lang']) {
            if (file_exists(DOKU_INC . 'inc/lang/' . $locale . '/lang.php')) {
                $lang = [];
                include(DOKU_INC . 'inc/lang/' . $locale . '/lang.php');
                $direction = $lang['direction'] ?? 'ltr';

                $event->data = '<div lang="' . hsc($locale) . '" dir="' . hsc($direction) . '">' .
                    $event->data .
                    '</div>';
            }
        }
    }

    /**
     * Hook Callback. Redirect to translated start page
     *
     * @param Event $event DOKUWIKI_STARTED
     */
    public function redirectStartPage(Event $event)
    {
        global $ID;
        global $ACT;
        global $conf;

        if ($ID == $conf['start'] && $ACT == 'show') {
            $lc = $this->helper->getBrowserLang();

            [$translatedStartpage, ] = $this->helper->buildTransID($lc, $conf['start']);
            if (cleanID($translatedStartpage) !== cleanID($ID)) {
                send_redirect(wl(cleanID($translatedStartpage), '', true));
            }
        }
    }

    /**
     * Hook Callback. Add hreflang attributes to the page header
     *
     * @param Event $event TPL_METAHEADER_OUTPUT
     */
    public function addHrefLangAttributes(Event $event)
    {
        global $ID;
        global $conf;

        if (!$this->helper->istranslatable($ID)) return;

        $translations = $this->helper->getAvailableTranslations($ID);
        if ($translations) {
            foreach ($translations as $lc => $translation) {
                $event->data['link'][] = [
                    'rel' => 'alternate',
                    'hreflang' => $lc,
                    'href' => wl(cleanID($translation), '', true),
                ];
            }
        }

        $default = $conf['lang_before_translation'] ?? $conf['lang'];
        $defaultlink = $this->helper->buildTransID($default, ($this->helper->getTransParts($ID))[1])[0];
        $event->data['link'][] = [
            'rel' => 'alternate',
            'hreflang' => 'x-default',
            'href' => wl(cleanID($defaultlink), '', true),
        ];
    }

    /**
     * Hook Callback. Make current language available as page template placeholder and handle
     * original language copying
     *
     * @param Event $event COMMON_PAGETPL_LOAD
     */
    public function handlePageTemplates(Event $event)
    {
        global $ID;

        // load orginal content as template?
        if ($this->getConf('copytrans') && $this->helper->istranslatable($ID, false)) {
            // look for existing translations
            $translations = $this->helper->getAvailableTranslations($ID);
            if ($translations) {
                // find original language (might've been provided via parameter or use first translation)
                $orig = (string)$_REQUEST['fromlang'];
                if (!$orig) $orig = array_key_first($translations);

                // load file
                $origfile = $translations[$orig];
                $event->data['tpl'] = io_readFile(wikiFN($origfile));

                // prefix with warning
                $warn = io_readFile($this->localFN('totranslate'));
                if ($warn) $warn .= "\n\n";
                $event->data['tpl'] = $warn . $event->data['tpl'];

                // show user a choice of translations if any
                if (count($translations) > 1) {
                    $links = [];
                    foreach (array_keys($translations) as $t) {
                        $links[] = '<a href="' . wl($ID, ['do' => 'edit', 'fromlang' => $t]) . '">' .
                            $this->helper->getLocalName($t) .
                            '</a>';
                    }

                    msg(
                        sprintf(
                            $this->getLang('transloaded'),
                            $this->helper->getLocalName($orig),
                            implode(', ', $links)
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
     * Hook Callback.  Resort page match results so that results are ordered by translation, having the
     * default language first
     *
     * @param Event $event SEARCH_QUERY_PAGELOOKUP
     */
    public function sortSearchResults(Event $event)
    {
        // sort into translation slots
        $res = [];
        foreach ($event->result as $r => $t) {
            $tr = $this->helper->getLangPart($r);
            if (!isset($res["x$tr"]) || !is_array($res["x$tr"])) {
                $res["x$tr"] = [];
            }
            $res["x$tr"][] = [$r, $t];
        }
        // sort by translations
        ksort($res);
        // combine
        $event->result = [];
        foreach ($res as $r) {
            foreach ($r as $l) {
                $event->result[$l[0]] = $l[1];
            }
        }
    }
}
