<?php
/**
 * Info Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_translation extends DokuWiki_Syntax_Plugin {

    var $trans = array();
    var $tns   = '';

    /**
     * Initialize
     */
    function syntax_plugin_translation(){
        // load wanted translation into array
        $this->trans = strtolower(str_replace(',',' ',$this->getConf('translations')));
        $this->trans = array_unique(array_filter(explode(' ',$this->trans)));
        sort($this->trans);

        $this->tns = cleanID($this->getConf('translationns'));
        if($this->tns) $this->tns .= ':';
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
     * Check if the current ID is a translation and return the language code
     */
    function _currentLang(){
        global $ID;

        $rx = '/^'.$this->tns.'('.join('|',$this->trans).'):/';
        if(preg_match($rx,$ID,$match)){
            return $match[1];
        }
        return '';
    }

    /**
     * Returns a link to the wanted translation, empty $lng is default lang
     */
    function _buildTransLink($lng,$idpart){
        global $conf;
        global $saved_conf;
        if($lng){
            $link = ':'.$this->tns.$lng.':'.$idpart;
            $name = $lng;
        }else{
            $link = ':'.$this->tns.$idpart;
            if(!$conf['lang_before_translation']){
              $name = $conf['lang'];
            } else {
              $name = $conf['lang_before_translation'];
            }
        }
        return html_wikilink($link,$name);
    }

    /**
     * Displays the available and configured translations. Needs to be placed in the template.
     */
    function _showTranslations(){
        global $ACT;
        global $ID;
        global $conf;

        if($ACT != 'show') return;
        if($this->tns && strpos($ID,$this->tns) !== 0) return;
        $meta = p_get_metadata($ID);
        if($meta['plugin']['translation']['notrans']) return;

        $rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        $out  = '<div class="plugin_translation">';
        $out .= '<span>'.$this->getLang('translations');
        if($this->getConf('about')){
            $out .= '<sup>'.html_wikilink($this->getConf('about'),'?').'</sup>';
        }
        $out .= ':</span> ';
        $out .= '<ul>';
        $out .= '  <li><div class="li">'.$this->_buildTransLink('',$idpart).'</div></li>';
        foreach($this->trans as $t){
            $out .= '  <li><div class="li">'.$this->_buildTransLink($t,$idpart).'</div></li>';
        }
        $out .= '</ul>';
        $out .= '</div>';

        return $out;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
