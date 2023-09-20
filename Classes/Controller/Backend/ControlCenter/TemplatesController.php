<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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

use Psr\Http\Message\ResponseInterface;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class TemplatesController extends ActionController
{
    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter/Template.xlf'
        );
    }

    /**
     * List all available configurations for templates
     */
    public function listAction(): ResponseInterface
    {
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('templavoilaplus');

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $templatePlace = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($templatePlace);
        $templatePlacesByScope = $placesService->reorderPlacesByScope($templatePlace);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Templates List');

        $this->view->assign('templatePlacesByScope', $templatePlacesByScope);

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($GLOBALS['TYPO3_REQUEST']);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
        $this->registerDocheaderButtons($moduleTemplate);
        $moduleTemplate->setContent($this->view->render('List'));

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Show information about one template configuration
     *
     * @param string $placeIdentifier Uuid of TemplatePlace
     * @param string $configurationIdentifier Identifier inside the dataStructurePlace
     */
    public function infoAction($placeIdentifier, $configurationIdentifier): ResponseInterface
    {
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('templavoilaplus');

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $templatePlace = $placesService->getPlace(
            $placeIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlace($templatePlace);
        $templateConfiguration = $templatePlace->getConfiguration($configurationIdentifier);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Templates Info');

        $this->view->assign('templatePlace', $templatePlace);
        $this->view->assign('templateConfiguration', $templateConfiguration);

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($GLOBALS['TYPO3_REQUEST']);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
        $this->registerDocheaderButtons($moduleTemplate);
        $moduleTemplate->setContent($this->view->render('Info'));

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons(ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $getVars = $this->request->getArguments();

        if (isset($getVars['action']) && ($getVars['action'] === 'list' || $getVars['action'] === 'info')) {
            $backButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'backButton'])
                ->setHref($this->uriBuilder->uriFor('show', [], 'Backend\ControlCenter'))
                ->setTitle(TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.goBack'))
                ->setIcon($iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }
    }
}
