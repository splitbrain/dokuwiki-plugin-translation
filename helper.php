<?php
/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class helper_plugin_translation extends DokuWiki_Plugin {
    var $trans = array();
    var $tns   = '';

    /**
     * Initialize
     */
    function helper_plugin_translation(){
        require_once(DOKU_INC.'inc/pageutils.php');
        require_once(DOKU_INC.'inc/utf8.php');

        // load wanted translation into array
        $this->trans = strtolower(str_replace(',',' ',$this->getConf('translations')));
        $this->trans = array_unique(array_filter(explode(' ',$this->trans)));
        sort($this->trans);
        array_unshift($this->trans,'');

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
     * Check if the given ID is a translation and return the language code.
     */
    function getLangPart($id){
        $rx = '/^'.$this->tns.'('.join('|',$this->trans).'):/';
        if(preg_match($rx,$id,$match)){
            return $match[1];
        }
        return '';
    }

    /**
     * Returns the ID and name to the wanted translation, empty $lng is default lang
     */
    function buildTransID($lng,$idpart){
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
        return array($link,$name);
    }



}
