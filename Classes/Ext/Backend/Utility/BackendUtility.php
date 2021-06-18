<?php
namespace Ppi\TemplaVoilaPlus\Ext\Backend\Utility;


use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * This whole code is a rip from 9.5 core - it's only purpose is to provide thumbnail generation for
 * custom-restored non-fal file fields
 * 
 * It's used for displaying thumbnails on FCEs - don't use it in Controller/Preview/TextpicController!
 * 
 */
class BackendUtility extends \TYPO3\CMS\Backend\Utility\BackendUtility
{

    /**
     * Returns a linked image-tag for thumbnail(s)/fileicons/truetype-font-previews from a database row with a list of image files in a field
     * All $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] extension are made to thumbnails + ttf file (renders font-example)
     * Thumbsnails are linked to the show_item.php script which will display further details.
     *
     * @param array $row Row is the database row from the table, $table.
     * @param string $table Table name for $row (present in TCA)
     * @param string $field Field is pointing to the list of image files
     * @param string $backPath Back path prefix for image tag src="" field
     * @param string $thumbScript UNUSED since FAL
     * @param string $uploaddir Optional: $uploaddir is the directory relative to Environment::getPublicPath() where the image files from the $field value is found (Is by default set to the entry in $GLOBALS['TCA'] for that field! so you don't have to!)
     * @param int $abs UNUSED
     * @param string $tparams Optional: $tparams is additional attributes for the image tags
     * @param int|string $size Optional: $size is [w]x[h] of the thumbnail. 64 is default.
     * @param bool $linkInfoPopup Whether to wrap with a link opening the info popup
     * @return string Thumbnail image tag.
     */
    public static function thumbCode(
        $row,
        $table,
        $field,
        $backPath = '',
        $thumbScript = '',
        $uploaddir = null,
        $abs = 0,
        $tparams = '',
        $size = '',
        $linkInfoPopup = true
    ) {
        // Check and parse the size parameter
        $size = trim($size);
        $sizeParts = [64, 64];
        if ($size) {
            $sizeParts = explode('x', $size . 'x' . $size);
        }
        $thumbData = '';
        $fileReferences = static::resolveFileReferences($table, $field, $row);
        // FAL references
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    	
        if ($fileReferences !== null) {
        	
            // this part is basically not needed here for now, so I removed it.
        	die ('STOP! 928234981234 - you should\'t see this! ' . __CLASS__);
        	// if the above happened:
	        // Warning, you started to use fal relations in templavoila!
	        // in this case, if you convert all finally to fal, this class and it call can be removed.
	        // if not, this part of code should be filled with code from current src
	        
	        // w: 10.4 src
	        
	        foreach ($fileReferences as $fileReferenceObject) {
                // Do not show previews of hidden references
                if ($fileReferenceObject->getProperty('hidden')) {
                    continue;
                }
                $fileObject = $fileReferenceObject->getOriginalFile();

                if ($fileObject->isMissing()) {
                    $thumbData .= '<span class="label label-danger">'
                        . htmlspecialchars(
                            static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing')
                        )
                        . '</span>&nbsp;' . htmlspecialchars($fileObject->getName()) . '<br />';
                    continue;
                }

                // Preview web image or media elements
                if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']
                    && GeneralUtility::inList(
                        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                        $fileReferenceObject->getExtension()
                    )
                ) {
                    $cropVariantCollection = CropVariantCollection::create((string)$fileReferenceObject->getProperty('crop'));
                    $cropArea = $cropVariantCollection->getCropArea();
                    $imageUrl = self::getThumbnailUrl($fileObject->getUid(), [
                        'width' => $sizeParts[0],
                        'height' => $sizeParts[1] . 'c',
                        'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($fileReferenceObject),
                        '_context' => $cropArea->isEmpty() ? ProcessedFile::CONTEXT_IMAGEPREVIEW : ProcessedFile::CONTEXT_IMAGECROPSCALEMASK
                    ]);
                    $attributes = [
                        'src' => $imageUrl,
                        'width' => (int)$sizeParts[0],
                        'height' => (int)$sizeParts[1],
                        'alt' => $fileReferenceObject->getName(),
                    ];
                    $imgTag = '<img ' . GeneralUtility::implodeAttributes($attributes, true) . $tparams . '/>';
                } else {
                    // Icon
                    $imgTag = '<span title="' . htmlspecialchars($fileObject->getName()) . '">'
                        . $iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render()
                        . '</span>';
                }
                if ($linkInfoPopup) {
                    // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
                    $attributes = GeneralUtility::implodeAttributes([
                        'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
                        'data-dispatch-args-list' => '_FILE,' . (int)$fileObject->getUid(),
                    ], true);
                    $thumbData .= '<a href="#" ' . $attributes . '>' . $imgTag . '</a> ';
                } else {
                    $thumbData .= $imgTag;
                }
            }
	         
        } else {
            // Find uploaddir automatically
            if ($uploaddir === null) {
                $uploaddir = $GLOBALS['TCA'][$table]['columns'][$field]['config']['uploadfolder'];
            }
            $uploaddir = rtrim($uploaddir, '/');
            // Traverse files:
            $thumbs = GeneralUtility::trimExplode(',', $row[$field], true);
            $thumbData = '';
            foreach ($thumbs as $theFile) {
                if ($theFile) {
                	
                	
	                // wolo mod - fix:
		            // I noticed recently, that group/file type fields in FCEs after update/new are stored as fal uids instead of filename,
		            // even though of the type is not fal. It causes problem with BE thumbnails, but for some reason it still works in FE (with both ways)
		            // These uids are not resolved here above as relations, so I'm adding detecting and handling them here.
	                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theFile))   {
						$fileRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\FileRepository::class);
						$fileName = $fileRepository->findByIdentifier($theFile)->getPublicUrl();
		            }
	                else    {
                        $fileName = trim($uploaddir . '/' . $theFile, '/');
	                }
	                
                    //$fileName = trim($uploaddir . '/' . $theFile, '/');
	                // /wolo mod
	                
                    try {
                        /** @var File $fileObject */
                        $fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileName);
                        // Skip the resource if it's not of type AbstractFile. One case where this can happen if the
                        // storage has been externally modified and the field value now points to a folder
                        // instead of a file.
                        if (!$fileObject instanceof AbstractFile) {
                            continue;
                        }
                        if ($fileObject->isMissing()) {
                            $thumbData .= '<span class="label label-danger">'
                                . htmlspecialchars(static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                                . '</span>&nbsp;' . htmlspecialchars($fileObject->getName()) . '<br />';
                            continue;
                        }
                    } catch (ResourceDoesNotExistException $exception) {
                        $thumbData .= '<span class="label label-danger">'
                            . htmlspecialchars(static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                            . '</span>&nbsp;' . htmlspecialchars($fileName) . '<br />';
                        continue;
                    }

                    $fileExtension = $fileObject->getExtension();
                    if ($fileExtension === 'ttf'
                        || GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)
                    ) {
                        $imageUrl = $fileObject->process(
                            ProcessedFile::CONTEXT_IMAGEPREVIEW,
                            [
                                'width' => $sizeParts[0],
                                'height' => $sizeParts[1]
                            ]
                        )->getPublicUrl(true);

                        $image = '<img src="' . htmlspecialchars($imageUrl) . '" hspace="2" border="0" title="' . htmlspecialchars($fileObject->getName()) . '"' . $tparams . ' alt="" />';
                        if ($linkInfoPopup) {
                            $onClick = 'top.TYPO3.InfoWindow.showItem(\'_FILE\', ' . GeneralUtility::quoteJSvalue($fileName) . ',\'\');return false;';
                            $thumbData .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $image . '</a> ';
                        } else {
                            $thumbData .= $image;
                        }
                    } else {
                        // Gets the icon
                        $fileIcon = '<span title="' . htmlspecialchars($fileObject->getName()) . '">'
                            . $iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render()
                            . '</span>';
                        if ($linkInfoPopup) {
                            $onClick = 'top.TYPO3.InfoWindow.showItem(\'_FILE\', ' . GeneralUtility::quoteJSvalue($fileName) . ',\'\'); return false;';
                            $thumbData .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $fileIcon . '</a> ';
                        } else {
                            $thumbData .= $fileIcon;
                        }
                    }
                }
            }
        }
        return $thumbData;
    }
}
