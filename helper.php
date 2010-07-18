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
    var $trans       = array();
    var $tns         = '';
    var $defaultlang = '';

    /**
     * Initialize
     */
    function helper_plugin_translation(){
        global $conf;
        require_once(DOKU_INC.'inc/pageutils.php');
        require_once(DOKU_INC.'inc/utf8.php');

        // load wanted translation into array
        $this->trans = strtolower(str_replace(',',' ',$this->getConf('translations')));
        $this->trans = array_unique(array_filter(explode(' ',$this->trans)));
        sort($this->trans);

        // get default translation
        if(!$conf['lang_before_translation']){
          $dfl = $conf['lang'];
        } else {
          $dfl = $conf['lang_before_translation'];
        }
        if(in_array($dfl,$this->trans)){
            $this->defaultlang = $dfl;
        }else{
            $this->defaultlang = '';
            array_unshift($this->trans,'');
        }


        $this->tns = cleanID($this->getConf('translationns'));
        if($this->tns) $this->tns .= ':';
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
     * Returns the browser language if it matches with one of the configured
     * languages
     */
    function getBrowserLang(){
        $rx = '/(^|,|:|;|-)('.join('|',$this->trans).')($|,|:|;|-)/i';
        if(preg_match($rx,$_SERVER['HTTP_ACCEPT_LANGUAGE'],$match)){
            return strtolower($match[2]);
        }
        return false;
    }


    /**
     * Returns the ID and name to the wanted translation, empty
     * $lng is default lang
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

    /**
     * Check if current ID should be translated and any GUI
     * should be shown
     */
    function istranslatable($id,$checkact=true){
        global $ACT;

        if($checkact && $ACT != 'show') return false;
        if($this->tns && strpos($id,$this->tns) !== 0) return false;
        $skiptrans = trim($this->getConf('skiptrans'));
        if($skiptrans &&  preg_match('/'.$skiptrans.'/ui',':'.$id)) return false;
        $meta = p_get_metadata($id);
        if($meta['plugin']['translation']['notrans']) return false;

        return true;
    }

    /**
     * Displays the available and configured translations. Needs to be placed in the template.
     */
    function showTranslations(){
        global $ID;
        global $conf;
        global $INFO;

        if(!$this->istranslatable($ID)) return;

        $this->checkage();

        $LN = confToHash(dirname(__FILE__).'/lang/langnames.txt');

        $rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        $out  = '<div class="plugin_translation">';
        $out .= '<span>'.$this->getLang('translations');
        if($this->getConf('about')){
            $out .= '<sup>'.html_wikilink($this->getConf('about'),'?').'</sup>';
        }
        $out .= ':</span> ';

        if($this->getConf('dropdown')){ // use dropdown
            if($INFO['exists']){
                $class = 'wikilink1';
            }else{
                $class = 'wikilink2';
            }
            $out .= '<form action="'.wl().'" id="translation__dropdown">';
            $out .= '<select name="id" class="'.$class.'">';
            foreach($this->trans as $t){
                list($link,$name) = $this->buildTransID($t,$idpart);
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
                $out .= '<option value="'.$link.'"'.$sel.' class="'.$class.'" title="'.$LN[$name].'">'.hsc($name).'</option>';
            }
            $out .= '</select>';
            $out .= '<input name="go" type="submit" value="&rarr;" />';
            $out .= '</form>';
        }else{ // use list
            $out .= '<ul>';
            foreach($this->trans as $t){
                list($link,$name) = $this->buildTransID($t,$idpart);
		$link = cleanID($link);
                if(page_exists($link,'',false)){
                    $class = 'wikilink1';
                }else{
                    $class = 'wikilink2';
                }
                $out .= '  <li><div class="li"><a href="'.wl($link).'" class="'.$class.'" title="'.$LN[$name].'">'.hsc($name).'</a></div></li>';
            }
            $out .= '</ul>';
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Checks if the current page is a translation of a page
     * in the default language. Displays a notice when it is
     * older than the original page. Tries to lin to a diff
     * with changes on the original since the translation
     */
    function checkage(){
        global $ID;
        global $INFO;
        if(!$this->getConf('checkage')) return;
        if(!$INFO['exists']) return;
        $lng = $this->getLangPart($ID);
        if($lng == $this->defaultlang) return;

        $rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        // compare modification times
        list($orig,$name) = $this->buildTransID($this->defaultlang,$idpart);
        $origfn = wikiFN($orig);
        if($INFO['lastmod'] >= @filemtime($origfn) ) return;

        // get revision from before translation
        $orev = 0;
        $revs = getRevisions($orig,0,100);
        foreach($revs as $rev){
            if($rev < $INFO['lastmod']){
                $orev = $rev;
                break;
            }
        }

        // see if the found revision still exists
        if($orev && !page_exists($orig,$orev)) $orev=0;

        // build the message and display it
        $orig = cleanID($orig);	
        $msg = sprintf($this->getLang('outdated'),wl($orig));
        if($orev){
            $msg .= sprintf(' '.$this->getLang('diff'),
                    wl($orig,array('do'=>'diff','rev'=>$orev)));
        }

        echo '<div class="notify">'.$msg.'</div>';
    }
}
