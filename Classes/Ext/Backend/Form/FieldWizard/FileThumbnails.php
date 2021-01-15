<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Ext\Backend\Form\FieldWizard;


use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Q3i: Whole this class is extracted from 9.5 (is removed in 10)
 * It's needed for generating thumbnail in custom-restored non-fal file relation type (for templavoila needs)
 * 
 * 
 * Render thumbnails of selected files,
 * typically used with type=group and internal_type=file and file_reference.
 */
class FileThumbnails extends AbstractNode
{
    /**
     * Render thumbnails of selected files
     *
     * @return array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $selectedItems = $parameterArray['itemFormElValue'];

        if (!isset($config['internal_type'])
            || ($config['internal_type'] !== 'file' && $config['internal_type'] !== 'file_reference')
        ) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
            // Thumbnails only make sense on file and file_reference
            return $result;
        }

        $fileFactory = ResourceFactory::getInstance();
        $thumbnailsHtml = [];
        foreach ($selectedItems as $selectedItem) {
            $uidOrPath = $selectedItem['uidOrPath'];
            if (MathUtility::canBeInterpretedAsInteger($uidOrPath)) {
                $fileObject = $fileFactory->getFileObject($uidOrPath);
                if (!$fileObject->isMissing()) {
                    $extension = $fileObject->getExtension();
                    if (GeneralUtility::inList(
                        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                        $extension
                    )
                    ) {
                        $thumbnailsHtml[] = '<li>';
                        $thumbnailsHtml[] =     '<span class="thumbnail">';
                        // w mod
                        $imgConf['file'] = $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [])->getUid();
						$imgConf['file.']['treatIdAsReference'] = 1;
						$thumbnailsHtml[] =         '<img src="'. $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [])-> getPublicUrl(true) . '" alt="[FAL TV]">';
						// /w mod
                        $thumbnailsHtml[] =     '</span>';
                        $thumbnailsHtml[] = '</li>';
                    }
                }
            } else {
                $rowCopy = [];
                $rowCopy[$fieldName] = $uidOrPath;
                try {
// q3i mod
                    $icon = \Ppi\TemplaVoilaPlus\Ext\Backend\Utility\BackendUtility::thumbCode(
// q3i end
                        $rowCopy,
                        $table,
                        $fieldName,
                        '',
                        '',
                        $config['uploadfolder'],
                        0,
                        ' align="middle"'
                    );
                    $thumbnailsHtml[] =
                        '<li>'
                        . '<span class="thumbnail">'
                        . $icon
                        . '</span>'
                        . '</li>';
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $message,
                        '',
                        FlashMessage::ERROR,
                        true
                    );
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $defaultFlashMessageQueue->enqueue($flashMessage);
                    $this->logger->warning($message, ['table' => $table, 'row' => $row]);
                }
            }
        }

        $html = [];
        if (!empty($thumbnailsHtml)) {
            $html[] = '<ul class="list-inline">';
            $html[] =   implode(LF, $thumbnailsHtml);
            $html[] = '</ul>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
