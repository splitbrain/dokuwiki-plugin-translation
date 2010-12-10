<?php
/**
 * Options for the translation plugin
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */

$meta['translations']  = array('string','_pattern' => '/^(|[a-zA-Z\- ,]+)$/');
$meta['translationns'] = array('string','_pattern' => '/^(|[\w:\-]+)$/');
$meta['skiptrans']     = array('string');
$meta['dropdown']      = array('onoff');
$meta['dropdown2']     = array('onoff');
$meta['flags']         = array('onoff');
$meta['description']   = array('onoff');
$meta['blankflag']     = array('onoff');
$meta['translateui']   = array('onoff');
$meta['redirectstart'] = array('onoff');
$meta['checkage']      = array('onoff');
$meta['about']         = array('string','_pattern' => '/^(|[\w:\-]+)$/');
$meta['localabout']    = array('onoff');

$meta['display'] = array('multicheckbox',
                         '_choices' => array('lc','name','flag','title'));

