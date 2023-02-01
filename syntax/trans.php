<?php

/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
class syntax_plugin_translation_trans extends DokuWiki_Syntax_Plugin
{
    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    function getSort()
    {
        return 155;
    }

    /** @inheritdoc */
    function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~TRANS~~', $mode, 'plugin_translation_trans');
    }

    /** @inheritdoc */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return [];
    }

    /** @inheritdoc */
    function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format != 'xhtml') return false;
        // disable caching
        $renderer->nocache();

        /** @var helper_plugin_translation $hlp */
        $hlp = plugin_load('helper', 'translation');
        $renderer->doc .= $hlp->showTranslations();
        return true;
    }

}
