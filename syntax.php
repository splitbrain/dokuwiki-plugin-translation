<?php
/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_translation extends DokuWiki_Syntax_Plugin {

    /**
     * for th helper plugin
     */
    var $hlp = null;

    /**
     * Constructor. Load helper plugin
     */
    function syntax_plugin_translation(){
        $this->hlp =& plugin_load('helper', 'translation');
    }

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
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
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~NOTRANS~~',$mode,'plugin_translation');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        return array('notrans');
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        // store info in metadata
        if($format == 'metadata'){
            $renderer->meta['plugin']['translation']['notrans'] = true;
        }
        return false;
    }

    /**
     * Displays the available and configured translations. Needs to be placed in the template.
     */
    function _showTranslations(){
        global $ACT;
        global $ID;
        global $conf;

        if($ACT != 'show') return;
        if($this->hlp->tns && strpos($ID,$this->hlp->tns) !== 0) return;
        $skiptrans = trim($this->getConf('skiptrans'));
        if($skiptrans &&  preg_match('/'.$skiptrans.'/ui',':'.$ID)) return;
        $meta = p_get_metadata($ID);
        if($meta['plugin']['translation']['notrans']) return;

        $rx = '/^'.$this->hlp->tns.'(('.join('|',$this->hlp->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        $out  = '<div class="plugin_translation">';
        $out .= '<span>'.$this->getLang('translations');
        if($this->getConf('about')){
            $out .= '<sup>'.html_wikilink($this->getConf('about'),'?').'</sup>';
        }
        $out .= ':</span> ';

        /* needs some java script...
        $out .= '<form action="'.DOKU_SCRIPT.'">';
        $out .= '<select name="id">';
        foreach($this->hlp->trans as $t){
            list($link,$name) = $this->hlp->buildTransID($t,$idpart);
            $link = cleanID($link);
            if($ID == $link){
                $sel = ' selected="selected"';
            }else{
                $sel = '';
            }
            if(page_exists($link,'',false)){
                $class = 'wikilink1';
            }else{
                $class = 'wikilink2';
            }
            $out .= '<option value="'.hsc($link).'"'.$sel.' class="'.$class.'">'.hsc($name).'</option>';
        }
        $out .= '</select>';
        $out .= '<input type="submit" value="&rarr;" />';
        $out .= '</form>';
        */


        $out .= '<ul>';
        foreach($this->hlp->trans as $t){
            list($link,$name) = $this->hlp->buildTransID($t,$idpart);
            $out .= '  <li><div class="li">'.html_wikilink($link,$name).'</div></li>';
        }
        $out .= '</ul>';
        $out .= '</div>';


        return $out;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
