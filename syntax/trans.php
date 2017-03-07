<?php
/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_translation_trans
 */
class syntax_plugin_translation_trans extends DokuWiki_Syntax_Plugin {
    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 155;
    }

    /**
     * Connect pattern to lexer
     *
     * @param string $mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~TRANS~~', $mode, 'plugin_translation_trans');
    }

    /**
     * Handler to prepare matched data for the rendering process
     *
     * @param   string       $match   The text matched by the patterns
     * @param   int          $state   The lexer state for the match
     * @param   int          $pos     The character position of the matched text
     * @param   Doku_Handler $handler The Doku_Handler object
     * @return  bool|array Return an array with all data you want to use in render, false don't add an instruction
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        return array();
    }

    /**
     * Handles the actual output creation.
     *
     * @param string          $format   output format being rendered
     * @param Doku_Renderer   $renderer the current renderer object
     * @param array           $data     data created by handler()
     * @return  boolean                 rendered correctly? (however, returned value is not used at the moment)
     */
    function render($format, Doku_Renderer $renderer, $data) {
        if($format != 'xhtml') return false;
        // disable caching
        $renderer->nocache();

        /** @var helper_plugin_translation $hlp */
        $hlp =  plugin_load('helper', 'translation');
        $renderer->doc .= $hlp->showTranslations();
        return true;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
