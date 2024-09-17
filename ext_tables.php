<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][\Tvp\TemplaVoilaPlus\Hooks\WizardItems::class]
        = \Tvp\TemplaVoilaPlus\Hooks\WizardItems::class;

    $GLOBALS['TBE_STYLES']['skins']['templavoilaplus']['stylesheetDirectories'][]
        = 'EXT:templavoilaplus/Resources/Public/StyleSheet/Skin';

    $navigationComponentId = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';
    if (version_compare(TYPO3_version, '9.0.0', '<')) {
        $navigationComponentId = 'typo3-pagetree';
    }

    $classPrefixForRegisterModule = '';
    $classPostfixForRegisterModule = '';
    $moduleName = 'Tvp.TemplaVoilaPlus';
    if (version_compare(TYPO3_version, '10.0.0', '>=')) {
        $classPrefixForRegisterModule = Tvp\TemplaVoilaPlus\Controller::class . '\\';
        $classPostfixForRegisterModule = 'Controller';
        $moduleName = 'TemplaVoilaPlus';
    }

    // Adding backend modules:
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $moduleName,
        'web',
        'Layout',
        'top',
        [
            $classPrefixForRegisterModule . 'Backend\PageLayout' . $classPostfixForRegisterModule => 'show',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
            'navigationComponentId' => $navigationComponentId,
//             'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $moduleName,
        'tools',
        'ControlCenter',
        'bottom',
        [
            $classPrefixForRegisterModule . 'Backend\ControlCenter' . $classPostfixForRegisterModule => 'show,debug',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\DataStructures' . $classPostfixForRegisterModule => 'list,info,delete',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Mappings' . $classPostfixForRegisterModule => 'list',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Templates' . $classPostfixForRegisterModule => 'list,info',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update' . $classPostfixForRegisterModule => 'info',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update\TemplaVoilaPlus8' . $classPostfixForRegisterModule => 'stepStart,step1,step2,step3,step3NewExtension,step3ExistingExtension,step4,step5,stepFinal',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update\ServerMigration' . $classPostfixForRegisterModule => 'stepStart',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update\DataStructureV8' . $classPostfixForRegisterModule => 'stepStart,stepFinal',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update\DataStructureV10' . $classPostfixForRegisterModule => 'stepStart,stepFinal',
            $classPrefixForRegisterModule . 'Backend\ControlCenter\Update\DataStructureV11' . $classPostfixForRegisterModule => 'stepStart,stepFinal',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false
        ]
    );
}

// complex condition to make sure the icons are available during frontend editing...
if (
    TYPO3_MODE === 'BE' ||
    (
        TYPO3_MODE === 'FE'
        && isset($GLOBALS['BE_USER'])
        && method_exists($GLOBALS['BE_USER'], 'isFrontendEditingActive')
        && $GLOBALS['BE_USER']->isFrontendEditingActive()
    )
) {
    $iconsBitmap = [
        'paste' =>  'EXT:templavoilaplus/Resources/Public/Icon/clip_pasteafter.gif',
        'pasteSubRef' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_pastesubref.gif',
        'makelocalcopy' => 'EXT:templavoilaplus/Resources/Public/Icon/makelocalcopy.gif',
        'clip_ref' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref.gif',
        'clip_ref-release' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref_h.gif',
        'htmlvalidate' => 'EXT:templavoilaplus/Resources/Public/Icon/html_go.png',
        'type-fce' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png',
    ];
    $iconsSvg = [
        'template-default' => 'EXT:templavoilaplus/Resources/Public/Icons/TemplateDefault.svg',
        'datastructure-default' => 'EXT:templavoilaplus/Resources/Public/Icons/DataStructureDefault.svg',
        'folder' => 'EXT:templavoilaplus/Resources/Public/Icons/Folder.svg',
        'menu-item' => 'EXT:templavoilaplus/Resources/Public/Icons/MenuItem.svg',
    ];
    $iconsFontAwesome = [
        'unlink' => 'unlink',
    ];


    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($iconsBitmap as $identifier => $file) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => $file]
        );
    }

    foreach ($iconsSvg as $identifier => $file) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => $file]
        );
    }

    foreach ($iconsFontAwesome as $identifier => $name) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
            ['name' => $name]
        );
    }
}
