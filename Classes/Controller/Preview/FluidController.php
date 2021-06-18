<?php
namespace Ppi\TemplaVoilaPlus\Controller\Preview;


use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Text controller
 */
class FluidController
{

    /**
     * @var mixed
     */
    protected $parentObj;
    
    protected $pageinfo = [];
	
	/**
     * @var IconFactory
     */
    protected $iconFactory;
    
    /**
     * @var Clipboard
     */
    protected $clipboard;


    /**
     * @param array $row
     * @param string $table
     * @param string $output
     * @param boolean $alreadyRendered
     * @param object $ref
     *
     * @return string
     */
    public function render_previewContent($row, $table, $output, $alreadyRendered, &$ref)
    {
    	// $row['CType'] = 'fluidfoundationtheme_NNNNN';
    	
	    
	    // make a classic page-module's view object 
        $view = GeneralUtility::makeInstance(PageLayoutView::class);
        
        // return ->tt_content_drawItem() basically does the job, but try to do it better, like they do there
 

        $singleElementHTML = '<div class="t3-page-ce-body-inner">' . $view->tt_content_drawItem($row) . '</div>';
        
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->pageinfo = BackendUtility::readPageAccess($ref->id, '');
        $this->initializeClipboard();


        // include paste buttons
        $elFromTable = $this->clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->isContentEditable()) {
            $pasteItem = substr(key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecord('tt_content', (int)$pasteItem);
            $pasteTitle = $pasteRecord['header'] ?: $pasteItem;
            $copyMode = $this->clipboard->clipData['normal']['mode'] ? '-' . $this->clipboard->clipData['normal']['mode'] : '';
            $inlineJavaScript = '
                     top.pasteIntoLinkTemplate = '
                . $this->tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-into', 'pasteIntoColumn')
                . ';
                    top.pasteAfterLinkTemplate = '
                . $this->tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-after', 'pasteAfterRecord')
                . ';';
        } else {
            $inlineJavaScript = '
                top.pasteIntoLinkTemplate = \'\';
                top.pasteAfterLinkTemplate = \'\';';
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsInlineCode('pasteLinkTemplates', $inlineJavaScript);
        
        
        // include javascripts from original Page module
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Localization');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/DragDrop');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/Paste');
        // that one sometimes causes errors in tv module, so include own modded version
	    // update: then we need to include all the rest too. leaving for now for tests

	    /*$fullJsPath = PathUtility::getRelativePath(
            defined('PATH_typo3') ? PATH_typo3 : Environment::getPublicPath(),
            GeneralUtility::getFileAbsFileName('EXT:templavoilaplus/Resources/Public/JavaScript/LayoutModule')
        );
        $pageRenderer->addRequireJsConfiguration([
            'paths' => [
                // 'FluidTypo3/Flux/FluxCollapse' => $fullJsPath . 'fluxCollapse',
                'Ppi/TemplaVoilaPlus/LayoutModule/Paste' => '/' . $fullJsPath . 'Paste',
            ],
        ]);*/
        // $pageRenderer->loadRequireJsModule('Ppi/TemplaVoilaPlus/LayoutModule/Paste');
	    
        return $singleElementHTML;
    }
    
    
    /**
     * Draw a paste icon either for pasting into a column or for pasting after a record
     *
     * @param int $pasteItem ID of the item in the clipboard
     * @param string $pasteTitle Title for the JS modal
     * @param string $copyMode copy or cut
     * @param string $cssClass CSS class to determine if pasting is done into column or after record
     * @param string $title title attribute of the generated link
     *
     * @return string Generated HTML code with link and icon
     */
    protected function tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, $cssClass, $title)
    {
        $pasteIcon = json_encode(
            ' <a data-content="' . htmlspecialchars($pasteItem) . '"'
            . ' data-title="' . htmlspecialchars($pasteTitle) . '"'
            . ' data-severity="warning"'
            . ' class="t3js-paste t3js-paste' . htmlspecialchars($copyMode) . ' ' . htmlspecialchars($cssClass) . ' btn btn-default btn-sm"'
            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL($title)) . '">'
            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
            . '</a>'
        );
        return $pasteIcon;
    }
    
    /**
     * Initializes the clipboard for generating paste links
     *
     *
     * @see \TYPO3\CMS\Backend\Controller\ContextMenuController::clipboardAction()
     * @see \TYPO3\CMS\Filelist\Controller\FileListController::indexAction()
     */
    protected function initializeClipboard()
    {
        // Start clipboard
        $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);

        // Initialize - reads the clipboard content from the user session
        $this->clipboard->initializeClipboard();

        // This locks the clipboard to the Normal for this request.
        $this->clipboard->lockToNormal();

        // Clean up pad
        $this->clipboard->cleanCurrent();

        // Save the clipboard content
        $this->clipboard->endClipboard();
    }
    
    /**
     * Check if content can be edited by current user
     *
     * @param int|null $languageId
     * @return bool
     */
    protected function isContentEditable(?int $languageId = null)
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock']
            && $this->hasContentModificationAndAccessPermissions()
            && ($languageId === null || $this->getBackendUser()->checkLanguageAccess($languageId));
    }
    
    /**
     * Returns the language service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
    
    /**
     * Check if current user has modification and access permissions for content set
     *
     * @return bool
     */
    protected function hasContentModificationAndAccessPermissions(): bool
    {
        return $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT);
    }
    
    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
