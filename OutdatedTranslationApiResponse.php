<?php

namespace dokuwiki\plugin\translation;

use dokuwiki\Remote\Response\ApiResponse;

class OutdatedTranslationApiResponse extends ApiResponse
{
    /** @var string The page ID */
    public $id;

    /** @var string The language code of this translation */
    public $lang;

    /** @var int The unix timestamp of the last modified date of this page, 0 if it does not exist */
    public $lastModified;

    /** @var string The page ID of the original page */
    public $originalID;

    /** @var int The unix timestamp of the last modified date of this page, 0 if it does not exist */
    public $originalModified;

    /** @var string The status of this translation. Either "missing" or "outdated" */
    public $status;

    public function __construct($origPage, $transID, $transMod, $transLang, $status)
    {
        $this->id = $transID;
        $this->lang = $transLang;
        $this->lastModified = (int) $transMod;
        $this->originalID = $origPage['id'];
        $this->originalModified = $origPage['mtime'];
        $this->status = $status;
    }


    public function __toString()
    {
        return $this->id;
    }
}
