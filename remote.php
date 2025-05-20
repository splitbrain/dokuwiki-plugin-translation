<?php

use dokuwiki\Extension\RemotePlugin;
use dokuwiki\plugin\translation\OutdatedTranslationApiResponse;
use dokuwiki\Remote\AccessDeniedException;

/**
 * DokuWiki Plugin translation (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Andreas Gohr <andi@splitbrain.org>
 */
class remote_plugin_translation extends RemotePlugin
{
    /**
     * Get outdated or missing translations
     *
     * This function returns a list of all pages that are either missing or are older than their
     * corresponding page in the default language.
     *
     * Only available to managers and superusers.
     *
     * @param string $namespace The namespace to check, empty for all
     * @return OutdatedTranslationApiResponse[]
     */
    public function getOutdated($namespace = '')
    {
        if (!auth_ismanager()) {
            throw new AccessDeniedException('You are not allowed to access this endpoint', 111);
        }

        /** @var helper_plugin_translation $helper */
        $helper = plugin_load('helper', 'translation');

        $namespace = cleanID($namespace);
        $pages = $helper->getAllTranslatablePages($namespace);

        $result = [];
        foreach ($pages as $page) {
            [, $idpart] = $helper->getTransParts($page["id"]);
            foreach ($helper->translations as $t) {
                if ($t === $helper->defaultlang) continue;
                [$translID,] = $helper->buildTransID($t, $idpart);

                if (!page_exists($translID)) {
                    $status = 'missing';
                } else if ($page['mtime'] > filemtime(wikiFN($translID))) {
                    $status = 'outdated';
                } else {
                    $status = '';
                }

                if ($status) {
                    $result[] = new OutdatedTranslationApiResponse(
                        $page,
                        cleanID($translID),
                        filemtime(wikiFN($translID)),
                        $t,
                        $status
                    );
                }
            }
        }

        return $result;
    }
}
