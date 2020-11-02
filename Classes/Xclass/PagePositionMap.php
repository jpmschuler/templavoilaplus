<?php

namespace Ppi\TemplaVoilaPlus\Xclass;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PagePositionMap extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap
{
    /**
     * Creates the onclick event for the insert-icons.
     *
     * @param mixed $row The record. If this is not an array with the record data the insert will be for the first position
     * in the column
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @param int $sys_lang System language (not used currently)
     * @return string
     */
    public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0)
    {
    	$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $location = $uriBuilder->buildUriFromRoute(
            'web_txtemplavoilaplusLayout',
            [
                'cmd' => 'crPage',
                'positionPid' => $pid,
// what value should come here?
                'id' => $newPagePID,
            ]
        );
        return $this->clientContext . '.location.href=' . GeneralUtility::quoteJSvalue($location) . '; return false;';
    }
}
