<?php
namespace Ppi\TemplaVoilaPlus\Xclass;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Script Class for the New Content element wizard
 */
class NewContentElementController extends \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
{
    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     * @return void
     */
    function prepareContent(string $clientContext): void
    {
        //$lang = $this->getLanguageService();
        $this->getButtons();
        $hasAccess = $this->id && is_array($this->pageInfo);
        if ($hasAccess) {
            // If a column is pre-set
            if (isset($this->colPos)) {
            	// if opened from Templavoila page module (must check, because it brakes classic/flux new content wizard)
        	    if (strstr($_REQUEST['returnUrl'], 'templavoila'))	{
            		// Init position map object:
		            $posMap = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap::class);
		            $posMap->cur_sys_language = $this->sys_language;
	
	                if ($this->uid_pid < 0) {
	                    $row = [];
	                    $row['uid'] = abs($this->uid_pid);
	                } else {
	                    $row = '';
	                }
	                $onClickEvent = $posMap->onClickInsertRecord(
	                    $row,
	                    $this->colPos,
	                    '',
	                    $this->uid_pid,
	                    $this->sys_language
	                );
                }
				// default typo behaviour
                else	{
                	$onClickEvent = $this->onClickInsertRecord($clientContext);
                }
				// tv mod end
            } else {
                $onClickEvent = '';
            }
            // ***************************
            // Creating content
            // ***************************
            //$this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
            // Wizard
            $wizardItems = $this->getWizards();
            // Wrapper for wizards
            //$this->elementWrapper['section'] = ['', ''];
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] as $classData) {
                    if (version_compare(TYPO3_version, '9.0.0', '>=')) {
                        $hookObject = GeneralUtility::makeInstance($classData);
                    } else {
                        $hookObject = GeneralUtility::getUserObj($classData);
                    }
                    if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                        throw new \UnexpectedValueException(
                            $classData . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                            1227834741
                        );
                    }
                    $hookObject->manipulateWizardItems($wizardItems, $this);
                }
            // Add document inline javascript


            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $cc = ($key = 0);
            $menuItems = [];

            $this->view->assignMultiple([
                'hasClickEvent' => $onClickEvent !== '',
                'onClickEvent' => 'function goToalt_doc() { ' . $onClickEvent . '}',
            ]);

            foreach ($wizardItems as $wizardKey => $wInfo) {
                $wizardOnClick = '';
                if (isset($wInfo['header'])) {
                    $menuItems[] = [
                        // 'label' => htmlspecialchars($wInfo['header']),
                        'label' => $wInfo['header'] ?: '-',
                        'content' => '',//$this->elementWrapper['section'][0]
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    //$content = '';

                    if (!$onClickEvent) {
                        // Radio button:
                        $wizardOnClick = 'document.editForm.defValues.value=unescape(' . GeneralUtility::quoteJSvalue(rawurlencode($wInfo['params'])) . ');window.location.hash=\'#sel2\';';

                        //$content .= '<div class="media-left"><input type="radio" name="tempB" value="' . htmlspecialchars($wizardKey) . '" onclick="' . htmlspecialchars($wizardOnClick) . '" /></div>';
                        // Onclick action for icon/title:
                        $aOnClick = 'document.getElementsByName(\'tempB\')[' . $cc . '].checked=1;' . $wizardOnClick . 'return false;';
                    } else {
                        $aOnClick = "document.editForm.defValues.value=unescape('" . rawurlencode($wInfo['params']) . "');goToalt_doc();";
                    }

                    // Go to DataHandler directly instead of FormEngine
                    if ($wInfo['saveAndClose'] ?? false) {
                        $urlParams = [];
                        $id = StringUtility::getUniqueId('NEW');
                        parse_str($wInfo['params'], $urlParams);
                        $urlParams['data']['tt_content'][$id] = $urlParams['defVals']['tt_content'] ?? [];
                        $urlParams['data']['tt_content'][$id]['colPos'] = $this->colPos;
                        $urlParams['data']['tt_content'][$id]['pid'] = $this->uid_pid;
                        $urlParams['data']['tt_content'][$id]['sys_language_uid'] = $this->sys_language;
                        $urlParams['redirect'] = GeneralUtility::_GP('returnUrl');
                        unset($urlParams['defVals']);
                        $url = $this->uriBuilder->buildUriFromRoute('tce_db', $urlParams);
                        $aOnClick = 'list_frame.location.href=' . GeneralUtility::quoteJSvalue((string)$url) . '; return false';
                    }
                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wInfo['iconIdentifier'])->render();

                    $this->menuItemView->assignMultiple([
                        'onClickEvent' => $onClickEvent,
                        'aOnClick' => $aOnClick,
                        'wizardInformation' => $wInfo,
                        'icon' => $icon,
                        'wizardOnClick' => $wizardOnClick,
                        'wizardKey' => $wizardKey
                    ]);
                    $menuItems[$key]['content'] .= $this->menuItemView->render();
                    $cc++;
                }
            }
            // Add closing section-tag
            /*foreach ($menuItems as $key => $val) {
                $menuItems[$key]['content'] .= $this->elementWrapper['section'][1];
            }*/
            // Add the wizard table to the content, wrapped in tabs
            //$code = '<p>' . $lang->getLL('sel1', 1) . '</p>' . $this->moduleTemplate->getDynamicTabMenu(
            $this->view->assign('renderedTabs', $this->moduleTemplate->getDynamicTabMenu(
                $menuItems,
                'new-content-element-wizard'
            ));

            // $this->content .= !$this->onClickEvent ? '<h2>' . $lang->getLL('1_selectType', true) . '</h2>' : '';
            // $this->content .= '<div>' . $code . '</div>';

            // If the user must also select a column:
            if (!$onClickEvent) {
            	$this->definePositionMapEntries($clientContext);
            }
        } /*else {
            // In case of no access:
            $this->content = '';
            $this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
        }*/
        $this->view->assign('hasAccess', $hasAccess);
        //$this->content .= '</form>';
        // Setting up the buttons and markers for docheader
        //$this->getButtons();
    }
}
