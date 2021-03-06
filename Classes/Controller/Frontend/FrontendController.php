<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

use Ppi\TemplaVoilaPlus\Domain\Model\DataStructure;
use Ppi\TemplaVoilaPlus\Domain\Model\MappingConfiguration;
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;
use Ppi\TemplaVoilaPlus\Service\ApiService;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\ApiHelperUtility;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class FrontendController extends AbstractPlugin
{
    /**
     * Same as class name
     * @TODO Rename?
     *
     * @var string
     */
    public $prefixId = 'tx_templavoilaplus_pi1';

    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'templavoilaplus';

    /**
     * Main function for rendering of Flexible Content elements of TemplaVoila
     *
     * @param string $content Standard content input. Ignore.
     * @param array $conf TypoScript array for the plugin.
     *
     * @return string HTML content for the Flexible Content elements.
     */
    public function renderPage($content, $conf)
    {
        /** @var ApiService */
        $apiService = GeneralUtility::makeInstance(ApiService::class, 'pages');

        // Current page record which we MIGHT manipulate a little:
        $pageRecord = $GLOBALS['TSFE']->page;

        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$pageRecord['tx_templavoilaplus_map']) {
            $pageRecord['tx_templavoilaplus_map'] = $apiService->getMapIdentifierFromRootline($GLOBALS['TSFE']->rootLine);
        }

        return $this->renderElement($pageRecord, 'pages');
    }

    public function renderContent($content, $conf)
    {
        return $this->renderElement($this->cObj->data, 'tt_content');
    }

    /**
     * Common function for rendering of the Flexible Content / Page Templates.
     * For Page Templates the input row may be manipulated to contain the proper reference to a data structure (pages can have those inherited which content elements cannot).
     *
     * @param array $row Current data record, either a tt_content element or page record.
     * @param string $table Table name, either "pages" or "tt_content".
     *
     * @throws \RuntimeException
     *
     * @return string HTML output.
     */
    public function renderElement($row, $table)
    {
try {
        $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($row['tx_templavoilaplus_map']);
        // getDS from Mapping
        $dataStructure = ApiHelperUtility::getDataStructure($mappingConfiguration->getCombinedDataStructureIdentifier());

        // getTemplateConfiguration from MappingConfiguration
        $templateConfiguration = ApiHelperUtility::getTemplateConfiguration($mappingConfiguration->getCombinedTemplateConfigurationIdentifier());

        // getDSdata from flexform field with DS
        $flexformData = [];
        if (!empty($row['tx_templavoilaplus_flex'])) {
            $flexformData = GeneralUtility::xml2array($row['tx_templavoilaplus_flex']);
        }
        if (is_string($flexformData)) {
            throw new \Exception('Could not load flex data: "' . $flexformData . '"');
        }
        $flexformValues = $this->getFlexformData($dataStructure, $flexformData);

        // Run TypoScript over DSdata and include TypoScript vars while mapping into TemplateData
        /** @TODO Do we need flexibility here? */
        /** @var \Ppi\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler */
        $mappingHandler = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler::class, $mappingConfiguration);
        $processedValues = $mappingHandler->process($flexformValues, $table, $row);

        // get renderer from templateConfiguration
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $renderHandlerIdentifier = $templateConfiguration->getRenderHandlerIdentifier();
        $renderer = $configurationService->getHandler($renderHandlerIdentifier);

        // Manipulate header data
        // @TODO The renderer? Not realy or?
        $renderer->processHeaderInformation($templateConfiguration);

        // give TemplateData to renderer and return result
        return $renderer->renderTemplate($templateConfiguration, $processedValues, $row);
} catch (\Exception $e) {
    var_dump($e->getMessage());
    var_dump($e);
    die('Error message shown');
}
    }

    public function getFlexformData(DataStructure $dataStructure, array $flexformData)
    {
        $flexformValues = [];

        /** @TODO sheet selection */
        $renderSheet = 'sDEF';

        list($dataStruct, $sheet, $singleSheet) = TemplaVoilaUtility::resolveSheetDefInDS($dataStructure->getDataStructureArray(), $renderSheet);

        /** @TODO Language selection */
        $lKey = 'lDEF';
        $vKey = 'vDEF';

        $flexformLkeyValues = [];
        if (isset($flexformData['data'][$sheet][$lKey]) && is_array($flexformData['data'][$sheet][$lKey])) {
            $flexformLkeyValues = $flexformData['data'][$sheet][$lKey];
        }

        $flexformValues = $this->processDataValues($flexformLkeyValues, $dataStruct['ROOT']['el'], $vKey);

        return $flexformValues;
    }

    public function processDataValues(array $dataValues, array $DSelements, $valueKey = 'vDEF')
    {
        $processedDataValues = [];
        foreach ($DSelements as $key => $dsConf) {
            /** @TDOD DSelement processing */
        }

        foreach ($dataValues as $field => $fieldData) {
            $processedDataValues[$field] = $fieldData[$valueKey];
        }

        return $processedDataValues;
    }
}
