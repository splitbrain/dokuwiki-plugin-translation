<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
class syntax_plugin_translation_trans extends SyntaxPlugin
{
    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 155;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~TRANS~~', $mode, 'plugin_translation_trans');
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return [];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
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
