<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Ajax;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Controller]
class ExtendedNewContentElementController extends NewContentElementController
{
    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getWizardsByRequest(ServerRequestInterface $request): array
    {
        $this->handleRequest($request);
        $wizardItems = $this->getWizards();
        $wizardHooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] ?? [];

        // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
        // Yes, that's done outside the function wich gathers the wizards!
        foreach ($wizardHooks as $className) {
            /** @var NewContentElementWizardHookInterface */
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                throw new \UnexpectedValueException(
                    $className . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                    1227834741
                );
            }
            $hookObject->manipulateWizardItems($wizardItems, $this);
        }

        return $wizardItems;
    }

    public function getPageId(): int
    {
        return $this->id;
    }
}
