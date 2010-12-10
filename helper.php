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
    var $LN          = array(); // hold native names

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

        // load language names
        $this->LN = confToHash(dirname(__FILE__).'/lang/langnames.txt');

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
        list($lng) = $this->getTransParts($id);
        return $lng;
    }

    /**
     * Check if the given ID is a translation and return the language code and
     * the id part.
     */
    function getTransParts($id){
        $rx = '/^'.$this->tns.'('.join('|',$this->trans).'):(.*)/';
        if(preg_match($rx,$id,$match)){
            return array($match[1],$match[2]);
        }
        return array('',$id);
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
     * Return the (localized) about link
     */
    function showAbout() {
        global $ID;
        global $conf;
        global $INFO;

        $this->checkage(); //FIXME why is this here?

        $about = $this->getConf('about');
        if($this->getConf('localabout')){
            list($lc,$idpart) = $this->getTransParts($about);
            list($about,$name) = $this->buildTransID($conf['lang'],$idpart);
            $about = cleanID($about);
        }

        $out = '';
        $out .= '<sup>';
        $out .= html_wikilink($about,'?');
        $out .= '</sup>';

        return $out;
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


        $rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        $out  = '<div class="plugin_translation">';

        //show text
        if ($this->getConf('description')){
            $out .= '<span>'.$this->getLang('translations');
            if ($this->getConf('about')) $out .= $this->showAbout();
            $out .= ':</span> ';
        }

        if($this->getConf('dropdown')){ // use dropdown fixme move to own functions
            if($INFO['exists']){
                $class = 'wikilink1';
            }else{
                $class = 'wikilink2';
            }

            $out2 = ""; //FIXME ugly name
            foreach($this->trans as $t){
                list($link,$name) = $this->buildTransID($t,$idpart);
                $link = cleanID($link);
                if($ID == $link){
                    $sel = ' selected="selected"';
                    if($this->getConf('dropdown2')) { //FIXME ugly name
                        $out .= $this->makecountrylink($LN, $idpart, $t, false);
                        $out .= "&nbsp;";
                    }
                }else{
                    $sel = '';
                }
                if(page_exists($link,'',false)){
                    $class = 'wikilink1';
                }else{
                    $class = 'wikilink2';
                }

                //linktitle
                $linktitle = '';
                if (strlen($LN[$name]) > 0){
                    $linktitle = $LN[$name];
                } else{
                    $linktitle = hsc($name);
                }

                $out2 .= '<option value="'.$link.'"'.$sel.' class="'.$class.'" title="'.$linktitle.'">'.hsc($name).'</option>';
            }
            $out .= '<form action="'.wl().'" id="translation__dropdown">';
            $out .= '<select name="id" class="'.$class.'">';
            $out .= $out2;
            $out .= '</select>';
            $out .= '<input name="go" type="submit" value="&rarr;" />';
            $out .= '</form>';

            //link to about (right)
            if (!$this->getConf('description') && $this->getConf('about')) {
                $out .= '&nbsp';
                $out .= $this->showAbout();
            }
        }else{ // use list
            $out .= '<ul>';

            // FIXME what's this?
            if (!$this->getConf('description') && $this->getConf('about')) {
                $out .= '&nbsp';
                $out .= $this->showAbout();
            }

            foreach($this->trans as $t){
                $out .= $this->makecountrylink($LN, $idpart, $t, true);
            }
            $out .= '</ul>';
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Create the link or option for a single translation
     *
     * @fixme bad name - translations are not about countries
     * @param $LN string      The language
     * @param $idpart string  The ID of the translated page
     * @param $t  FIXME
     * @param $div bool  true for lists, false for dropdown FIXME
     * @returns FIXME
     */
    function makecountrylink($LN, $idpart, $t, $div) {
        global $ID;
        global $conf;
        global $INFO;

        require(DOKU_PLUGIN.'translation/flags/langnames.php');

        list($link,$name) = $this->buildTransID($t,$idpart);
        $link = cleanID($link);
        if(page_exists($link,'',false)){
            $class = 'wikilink1';
        }else{
            $class = 'wikilink2';
        }

        //linktitle
        $linktitle = '';
        if (strlen($LN[$name]) > 0){
            $linktitle = $LN[$name];
        } else{
            $linktitle = hsc($name);
        }

        //if (show flag AND ((flag exist) OR (flag not exist AND show blank flag))
        if (($langflag[hsc($name)] != NULL && strlen($langflag[hsc($name)]) > 0 && $this->getConf('flags')) || $this->getConf('flags') && $this->getConf('blankflag')) {

            resolve_pageid(getNS($ID),$link,$exists);
            if ($div) {
                if ($exists){ //solid box
                    $out .= '  <li><div class="li">';
                } else{ //50% transparent box (50% transparent flag)
                    $out .= '  <li><div class="flag_not_exists">';
                }
            }

            //html_wikilink works very slow for images
            //$flag['title'] = $langname[$name];
            //$flag['src'] = DOKU_URL.'lib/plugins/translation/flags/'.$langflag[$name];
            //$out .= html_wikilink($link,$flag);

            $out .= '<a href="'.wl($link).'"';
            $out .= 'title="'.$linktitle.'"';
            //class for image
            $out .= 'class="wikilink3"'; //FIXME WTF?
            $out .= '>';

            //show flag
            if ($langflag[hsc($name)] != NULL && strlen($langflag[hsc($name)]) > 0){
                $out .= '<img src="'.DOKU_URL.'lib/plugins/translation/flags/'.$langflag[hsc($name)].'" alt='.$linktitle.'" border="0">';
            } else{ //show blank flag
                //$out .= '<img src="'.DOKU_BASE.'lib/images/blank.gif'.'" width=15 height=11 alt="'.$linktitle.'" border="0">';
                $out .= '<img src="'.DOKU_BASE.'lib/plugins/translation/flags/blankflag.gif'.'" width=15 height=11 alt="'.$linktitle.'" border="0">';
            }
            $out .= '</a>';

        } else{ //show text (also if flag not exist and blankflag=false)
            if ($div) {
                $out .= '  <li><div class="li">';
            }
            $out .= html_wikilink($link,hsc($name));
        }
        if ($div) {
            $out .= '</div></li>';
        }

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
