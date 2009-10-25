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
$meta['translateui']   = array('onoff');
$meta['redirectstart'] = array('onoff');
$meta['checkage']      = array('onoff');
$meta['about']         = array('string','_pattern' => '/^(|[\w:\-]+)$/');
