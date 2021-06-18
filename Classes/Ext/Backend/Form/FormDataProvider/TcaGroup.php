<?php

namespace Ppi\TemplaVoilaPlus\Ext\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;



/**
 * Q3i - restore classic file/group field in tca
 * Main part of code bases on 10.4 and should be kept in sync!
 * Modded part is taken from 9.5.20
 * 
 * Resolve databaseRow field content to the real connected rows for type=group
 */
class TcaGroup extends \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup
{
    /**
     * Initialize new row with default values from various sources
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type'])
                || $fieldConfig['config']['type'] !== 'group'
                || empty($fieldConfig['config']['internal_type'])
            ) {
                continue;
            }

            // Sanitize max items, set to 99999 if not defined
            $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = MathUtility::forceIntegerInRange(
                $fieldConfig['config']['maxitems'] ?? 0,
                0,
                99999
            );
            if ($result['processedTca']['columns'][$fieldName]['config']['maxitems'] === 0) {
                $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = 99999;
            }

            $databaseRowFieldContent = '';
            if (!empty($result['databaseRow'][$fieldName])) {
                $databaseRowFieldContent = (string)$result['databaseRow'][$fieldName];
            }

            $items = [];
            $sanitizedClipboardElements = [];
            $internalType = $fieldConfig['config']['internal_type'];


// Q3i mod - restore non-fal file field from 9.5

            if ($internalType === 'file_reference' || $internalType === 'file') {
                // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
                // Set 'allowed' config to "*" if not set
                if (empty($fieldConfig['config']['allowed'])) {
                    $result['processedTca']['columns'][$fieldName]['config']['allowed'] = '*';
                }
                // Force empty uploadfolder for file_reference type
                if ($internalType === 'file_reference') {
                    $result['processedTca']['columns'][$fieldName]['config']['uploadfolder'] = '';
                }

                // Simple list of files
                $fileList = GeneralUtility::trimExplode(',', $databaseRowFieldContent, true);
                $fileFactory = ResourceFactory::getInstance();
                foreach ($fileList as $uidOrPath) {
                    $item = [
                        'uidOrPath' => $uidOrPath,
                        'title' => $uidOrPath,
                    ];
                    try {
                        if (MathUtility::canBeInterpretedAsInteger($uidOrPath)) {
                            $fileObject = $fileFactory->getFileObject($uidOrPath);
                            $item['title'] = $fileObject->getName();
                        }
                    } catch (Exception $exception) {
                        continue;
                    }
                    $items[] = $item;
                }

                // Register elements from clipboard
                $allowed = GeneralUtility::trimExplode(',', $result['processedTca']['columns'][$fieldName]['config']['allowed'], true);
                $clipboard = GeneralUtility::makeInstance(Clipboard::class);
                $clipboard->initializeClipboard();
                $clipboardElements = $clipboard->elFromTable('_FILE');
                if ($allowed[0] !== '*') {
                    // If there are a set of allowed extensions, filter the content
                    foreach ($clipboardElements as $elementValue) {
                        $pathInfo = pathinfo($elementValue);
                        if (in_array(strtolower($pathInfo['extension']), $allowed)) {
                            $sanitizedClipboardElements[] = [
                                'title' => $elementValue,
                                'value' => $elementValue,
                            ];
                        }
                    }
                } else {
                    // If all is allowed, insert all. This does NOT respect any disallowed extensions,
                    // but those will be filtered away by the DataHandler
                    foreach ($clipboardElements as $elementValue) {
                        $sanitizedClipboardElements[] = [
                            'title' => $elementValue,
                            'value' => $elementValue,
                        ];
                    }
                }
            } else

// q3i end
            
            if ($internalType === 'db') {
                if (empty($fieldConfig['config']['allowed'])) {
                    throw new \RuntimeException(
                        'Mandatory TCA config setting "allowed" missing in field "' . $fieldName . '" of table "' . $result['tableName'] . '"',
                        1482250512
                    );
                }

                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    $databaseRowFieldContent,
                    $fieldConfig['config']['allowed'],
                    $fieldConfig['config']['MM'],
                    $result['databaseRow']['uid'],
                    $result['tableName'],
                    $fieldConfig['config']
                );
                $relationHandler->getFromDB();
                $relations = $relationHandler->getResolvedItemArray();
                foreach ($relations as $relation) {
                    $tableName = $relation['table'];
                    $uid = $relation['uid'];
                    $record = BackendUtility::getRecordWSOL($tableName, $uid);
                    $title = BackendUtility::getRecordTitle($tableName, $record, false, false);
                    $items[] = [
                        'table' => $tableName,
                        'uid' => $record['uid'] ?? null,
                        'title' => $title,
                        'row' => $record,
                    ];
                }

                // Register elements from clipboard
                $allowed = GeneralUtility::trimExplode(',', $fieldConfig['config']['allowed'], true);
                $clipboard = GeneralUtility::makeInstance(Clipboard::class);
                $clipboard->initializeClipboard();
                if ($allowed[0] !== '*') {
                    // Only some tables, filter them:
                    foreach ($allowed as $tablename) {
                        foreach ($clipboard->elFromTable($tablename) as $recordUid) {
                            $record = BackendUtility::getRecordWSOL($tablename, $recordUid);
                            $sanitizedClipboardElements[] = [
                                'title' => BackendUtility::getRecordTitle($tablename, $record),
                                'value' => $tablename . '_' . $recordUid,
                            ];
                        }
                    }
                } else {
                    // All tables allowed for relation:
                    $clipboardElements = array_keys($clipboard->elFromTable(''));
                    foreach ($clipboardElements as $elementValue) {
                        [$elementTable, $elementUid] = explode('|', $elementValue);
                        $record = BackendUtility::getRecordWSOL($elementTable, $elementUid);
                        $sanitizedClipboardElements[] = [
                            'title' => BackendUtility::getRecordTitle($elementTable, $record),
                            'value' => $elementTable . '_' . $elementUid,
                        ];
                    }
                }
            } elseif ($internalType === 'folder') {
                // Simple list of folders
                $folderList = GeneralUtility::trimExplode(',', $databaseRowFieldContent, true);
                foreach ($folderList as $folder) {
                    if (empty($folder)) {
                        continue;
                    }
                    try {
                        $folderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($folder);
                        if ($folderObject instanceof Folder) {
                            $items[] = [
                                'folder' => $folder,
                            ];
                        }
                    } catch (Exception $exception) {
                        continue;
                    }
                }
            } else {
                throw new \UnexpectedValueException(
                    'TCA internal_type of field "' . $fieldName . '" in table ' . $result['tableName']
                    . ' must be set to "db" or "folder".',
                    1438780511
                );
            }

            $result['databaseRow'][$fieldName] = $items;
            $result['processedTca']['columns'][$fieldName]['config']['clipboardElements'] = $sanitizedClipboardElements;
        }

        return $result;
    }
}
