<?php

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class admin_plugin_translation extends DokuWiki_Admin_Plugin {
    function forAdminOnly() {
        return false;
    }

    function handle() {
    }

    function html() {
        // 1. find the default language and the approbiate namespace
        /** @var helper_plugin_translation $helper */
        $helper = plugin_load('helper', "translation");
        $default_language = $helper->defaultlang;

        echo "<h1>" . $this->getLang("outdated translations") . "</h1>";
        // 2. search for all pages in the default language that should be translated
        $pages = $this->getAllPages();
        /** @var Doku_Renderer_xhtml $xhtml_renderer */
        $xhtml_renderer = p_get_renderer('xhtml');
        echo "<table id='outdated_translations'>";

        echo "<tr><th>default: $default_language</th>";
        foreach ($helper->translations as $t) {
            if($t === $default_language) {
                continue;
            }
            echo "<th>$t</th>";
        }
        echo "</tr>";

        foreach ($pages as $page) {
            if ($helper->getLangPart($page["id"]) === $default_language &&
                $helper->istranslatable($page["id"], false) &&
                page_exists($page["id"])
            ) {
                $row = "<tr><td>" . $xhtml_renderer->internallink($page['id'],null,null,true) . "</td>";
                $showRow = false;

                list($lc, $idpart) = $helper->getTransParts($page["id"]);

                foreach ($helper->translations as $t) {
                    if ($t === $default_language) {
                        continue;
                    }

                    list($transl, $name) = $helper->buildTransID($t, $idpart);

                    // 3. check if the translated pages exist & their age compared to the original
                    $difflink = '';
                    if(!page_exists($transl)) {
                        $class = "missing";
                        $title = $this->getLang("missing");
                        $showRow = true;
                    } else {
                        $translfn = wikiFN($transl);
                        if($page['mtime'] > @filemtime($translfn)) {
                            $class = "outdated";
                            $difflink = " <a href='";
                            $difflink .= $helper->getOldDiffLink($page["id"], $page['mtime']);
                            $difflink .= "'>(diff)</a>";
                            $title = $this->getLang("outdated");
                            $showRow = true;
                        } else {
                            $class = "current";
                            $title = $this->getLang('current');
                        }
                    }
                    $row .= "<td class='$class'>" . $xhtml_renderer->internallink($transl,$title,null,true) . $difflink . "</td>";
                }
                $row .= "</tr>";

                // 4. print a table if the translation may be outdated
                if ($showRow) {
                    echo $row;
                }
            }
        }
        echo "</table>";

    }

    function getAllPages() {
        global $conf;
        $namespace = $this->getConf("translationns");
        $dir = $conf['datadir'] . '/' . str_replace(':', '/', $namespace);
        $pages = array();
        search($pages, $dir, 'search_allpages',array());
        return $pages;
    }
}