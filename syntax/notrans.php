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
 * Class syntax_plugin_translation_notrans
 */
class syntax_plugin_translation_notrans extends DokuWiki_Syntax_Plugin {

    /**
     * for th helper plugin
     * @var helper_plugin_translation
     */
    var $hlp = null;

    /**
     * Constructor. Load helper plugin
     */
    function __construct(){
        $this->hlp = plugin_load('helper', 'translation');
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }

    /**
     * Connect pattern to lexer
     *
     * @param string $mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~NOTRANS~~',$mode,'plugin_translation_notrans');
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
    function handle($match, $state, $pos, Doku_Handler $handler){
        return array('notrans');
    }

    /**
     * Create output
     *
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data
     * @return bool
     */
    function render($format, Doku_Renderer $renderer, $data) {
        // store info in metadata
        if($format == 'metadata'){
            /** @var Doku_Renderer_metadata $renderer */
            $renderer->meta['plugin']['translation']['notrans'] = true;
        }
        return false;
    }

    // for backward compatibility
    /**
     * @return string
     */
    function _showTranslations(){
        return $this->hlp->showTranslations();
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
