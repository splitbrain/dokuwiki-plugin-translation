<?php

use dokuwiki\Extension\AdminPlugin;

/**
 * DokuWiki Plugin translation (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */
class admin_plugin_translation extends AdminPlugin
{
    /** @inheritdoc */
    public function forAdminOnly()
    {
        return false;
    }

    /** @inheritdoc */
    public function handle()
    {
    }

    /** @inheritdoc */
    public function html()
    {

        /** @var helper_plugin_translation $helper */
        $helper = plugin_load('helper', "translation");
        $default_language = $helper->defaultlang;

        /** @var Doku_Renderer_xhtml $xhtml_renderer */
        $xhtml_renderer = p_get_renderer('xhtml');

        echo "<h1>" . $this->getLang("menu") . "</h1>";
        echo "<table id='outdated_translations' class=\"inline\">";
        echo "<tr><th>default: $default_language</th>";
        if ($this->getConf('show_path')) {
            echo "<th>" . $this->getLang('path') . "</th>";
        }
        foreach ($helper->translations as $t) {
            if ($t === $default_language) {
                continue;
            }
            echo "<th>$t</th>";
        }
        echo "</tr>";

        $pages = $helper->getAllTranslatablePages();
        foreach ($pages as $page) {
            // We have an existing and translatable page in the default language
            $showRow = false;
            $row = "<tr><td>" . $xhtml_renderer->internallink($page['id'], null, null, true) . "</td>";
            if ($this->getConf('show_path')) {
                $row .= "<td>" . $xhtml_renderer->internallink($page['id'], $page['id'], null, true) . "</td>";
            }

            [, $idpart] = $helper->getTransParts($page["id"]);

            foreach ($helper->translations as $t) {
                if ($t === $default_language) {
                    continue;
                }

                [$translID, ] = $helper->buildTransID($t, $idpart);

                $difflink = '';
                if (!page_exists($translID)) {
                    $class = "missing";
                    $title = $this->getLang("missing");
                    $showRow = true;
                } else {
                    $translfn = wikiFN($translID);
                    if ($page['mtime'] > filemtime($translfn)) {
                        $class = "outdated";
                        $difflink = " <a href='";
                        $difflink .= $helper->getOldDiffLink($page["id"], $page['mtime']);
                        $difflink .= "'>(diff)</a>";
                        $title = $this->getLang('old');
                        $showRow = true;
                    } else {
                        $class = "current";
                        $title = $this->getLang('current');
                    }
                }
                $row .= "<td class='$class'>" . $xhtml_renderer->internallink(
                    $translID,
                    $title,
                    null,
                    true
                ) . $difflink . "</td>";
            }
            $row .= "</tr>";

            if ($showRow) {
                echo $row;
            }
        }
        echo "</table>";
    }

}
