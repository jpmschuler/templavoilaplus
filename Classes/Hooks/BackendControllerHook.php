<?php
namespace Ppi\TemplaVoilaPlus\Hooks;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds TemplaVoilà! Plus related JavaScript InlineSettings to the backend
 */
class BackendControllerHook
{
    /**
     * Adds TemplaVoilà! Plus specific JavaScript InlineSettings
     *
     * @param array $configuration
     * @param BackendController $backendController
     */
    public function addInlineSettings(array $configuration, BackendController $backendController)
    {
    	$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    	
        $this->getPageRenderer()->addInlineSettingArray(
            'TemplaVoilaPlus',
            [
                'layoutModuleUrl' => $uriBuilder->buildUriFromRoute('web_txtemplavoilaplusLayout'),
                'mappingModuleUrl' => $uriBuilder->buildUriFromRoute('templavoilaplus_mapping'),
                'dislplayModuleUrl' => $uriBuilder->buildUriFromRoute('templavoilaplus_template_disply'),
                'newSiteWizardModuleUrl' => $uriBuilder->buildUriFromRoute('templavoilaplus_new_site_wizard'),
                'flexformCleanerModuleUrl' => $uriBuilder->buildUriFromRoute('templavoilaplus_flexform_cleaner'),
            ]
        );
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
