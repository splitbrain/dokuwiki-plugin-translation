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

	function showAbout()
	{
	        global $ID;
        	global $conf;
        	global $INFO;

		$this->checkage();

		$LN = confToHash(dirname(__FILE__).'/lang/langnames.txt');

		$rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
		$idpart = preg_replace($rx,'',$ID);

		$out = '';
		$out .= '<sup>';
		if($this->getConf('localabout')){
		
			//$out .= '<sup>'.html_wikilink($this->getConf('about'),'?').'</sup>';

			//http://localhost/dokuwiki/doku.php?id=pl:test:podtest
			//$out .= '[';
			//$out .= getNS(cleanID(getID())); //pl:test
			//$out .= $INFO['namespace']; //pl:test
			//$out .= cleanID($ID); //pl:test:podtes
			//$out .= getID(); //pl:test:podtest
			//$out .= getNS($ID); //pl:test
			//$out .= ']';

			$lc = '';

			//try main lang namespace
			foreach($this->trans as $t){
				list($link,$name) = $this->buildTransID($t,$idpart);
				$link = cleanID($link);
				if($ID == $link){
				    $lc = hsc($name);
				}
				if ($lc) break;
			}

			//try browser language
            		if(!$lc) $lc = $this->getBrowserLang();

			//try wiki language
            		if(!$lc) $lc = $conf['lang'];

			if(!$lc) { //can't find language
				$localabout = $this->getConf('about'); //http://localhost/dokuwiki/doku.php?id=translation:about
			} else { //i found language!
            			$localabout = $lc.':'.$this->getConf('about'); //http://localhost/dokuwiki/doku.php?id=en:translation:about
			}
			
			//make link
			$out .= html_wikilink($localabout,'?');
	    	} else
		{
			$out .= html_wikilink($this->getConf('about'),'?');
		}
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

        $LN = confToHash(dirname(__FILE__).'/lang/langnames.txt');

        $rx = '/^'.$this->tns.'(('.join('|',$this->trans).'):)?/';
        $idpart = preg_replace($rx,'',$ID);

        $out  = '<div class="plugin_translation">';

	//scx
	//show text
	if ($this->getConf('description')){
		$out .= '<span>'.$this->getLang('translations');
		if ($this->getConf('showabout')) $out .= $this->showAbout();
		$out .= ':</span> ';
	}

        if($this->getConf('dropdown')){ // use dropdown
            if($INFO['exists']){
                $class = 'wikilink1';
            }else{
                $class = 'wikilink2';
            }

	    //scx
	    //link to about (left)
	    //if (!$this->getConf('description') && $this->getConf('showabout')) {
	    //	//$out .= '&nbsp';
	    //	$out .= $this->showAbout();
	    //	$out .= '&nbsp';
	    //}
		
            ////$out .= '<form action="'.wl().'" id="translation__dropdown">';
            ////$out .= '<select name="id" class="'.$class.'">';
	    $out2 = "";
            foreach($this->trans as $t){
                list($link,$name) = $this->buildTransID($t,$idpart);
                $link = cleanID($link);
                if($ID == $link){
                    $sel = ' selected="selected"';
		    if($this->getConf('dropdown2'))
		    {
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

		//scx
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


	    //scx
	    //link to about (right)
	    if (!$this->getConf('description') && $this->getConf('showabout')) {
	    	$out .= '&nbsp';
	    	$out .= $this->showAbout();
	    	//$out .= '&nbsp';
	    }
        }else{ // use list
	    //scx
	    //require(DOKU_PLUGIN.'translation/flags/langnames.php');
            $out .= '<ul>';

	    if (!$this->getConf('description') && $this->getConf('showabout')) {
	    	$out .= '&nbsp';
	    	$out .= $this->showAbout();
	    	//$out .= '&nbsp';
	    }

            foreach($this->trans as $t){
                $out .= $this->makecountrylink($LN, $idpart, $t, true);
            }
            $out .= '</ul>';
        }

        $out .= '</div>';

        return $out;
    }


	function makecountrylink($LN, $idpart, $t, $div)
	{
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

		//$out .= 'link='.$link; //link=de:start
		//$out .= 'wl(link)='.wl($link); //wl(link)=/dokuwiki/doku.php?id=de:start
	 	//$out .= 'class='.$class; //class=wikilink2
		//$out .= 'name='.$name; //name=de
		//$out .= 'LN[name]='.$LN[$name]; //LN[name]=Deutsch
		//$out .= 'hsc(name)='.hsc($name); //hsc(name)=de
		//$out .= 'linktitle='.$linktitle; //linktitle=Deutsch
		
		//if (show flag AND ((flag exist) OR (flag not exist AND show blank flag))
		if (($langflag[hsc($name)] != NULL && strlen($langflag[hsc($name)]) > 0 && $this->getConf('flags')) || $this->getConf('flags') && $this->getConf('blankflag')) {
			//$out .= '  <li><div class="li"><a href="'.wl($link).'" class="'.$class.'" title="'.$LN[$name].'">'.hsc($name).'</a></div></li>';
			
			resolve_pageid(getNS($ID),$link,$exists);
			if ($div)
			{
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
			$out .= 'class="wikilink3"';
			$out .= '>';
			
			//show flag
			if ($langflag[hsc($name)] != NULL && strlen($langflag[hsc($name)]) > 0){
				$out .= '<img src="'.DOKU_URL.'lib/plugins/translation/flags/'.$langflag[hsc($name)].'" alt='.$linktitle.'" border="0">';
			} else{ //show blank flag
				//$out .= '<img src="'.DOKU_BASE.'lib/images/blank.gif'.'" width=15 height=11 alt="'.$linktitle.'" border="0">';
				$out .= '<img src="'.DOKU_BASE.'lib/plugins/translation/flags/blankflag.gif'.'" width=15 height=11 alt="'.$linktitle.'" border="0">';
			}
			$out .= '</a>';

		}
		else{ //show text (also if flag not exist and blankflag=false)
			//$out .= '  <li><div class="li"><a href="'.wl($link).'" class="'.$class.'" title="'.$LN[$name].'">'.hsc($name).'</a></div></li>';

			//$out .= '<a href="'.wl($link);
			//$out .= '" class="'.$class.'" title="'.$LN[$name].'">';
			//$out .= hsc($name);
			//$out .= '</a>';
			if ($div)
			{
				$out .= '  <li><div class="li">';
			}
			$out .= html_wikilink($link,hsc($name));
		}
		if ($div)
		{
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
