<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
class syntax_plugin_translation_notrans extends SyntaxPlugin
{
    /** @var helper_plugin_translation */
    protected $hlp;

    /**
     * Constructor. Load helper plugin
     */
    public function __construct()
    {
        $this->hlp = plugin_load('helper', 'translation');
    }

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
        $this->Lexer->addSpecialPattern('~~NOTRANS~~', $mode, 'plugin_translation_notrans');
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return ['notrans'];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        // store info in metadata
        if ($format == 'metadata') {
            /** @var Doku_Renderer_metadata $renderer */
            $renderer->meta['plugin']['translation']['notrans'] = true;
        }
        return false;
    }

    /**
     * for backward compatibility only
     *
     * @deprecated
     * @return string
     */
    public function _showTranslations()
    {
        dbg_deprecated(helper_plugin_translation::class . '::showTranslations()');
        return $this->hlp->showTranslations();
    }
}
