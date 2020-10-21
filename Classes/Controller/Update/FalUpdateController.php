<?php

namespace  Ppi\TemplaVoilaPlus\Controller\Update;

use Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure;
use Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * FAL migrator for TV FCE tt_contents with type=group, internal_type=file
 * Reads all DS xml, finds such fields, then iterates all tt_contents from such DS, 
 * finds file attached in flexform and makes it a FAL relation.
 * 
 * IMPORTANT
 * 
 * It does not modify your datastructure xml!
 * You need to do it by yourself, but do it AFTER this migration! Migrator needs old structure, to read old configs.
 * Remember, when migrating final database on production, checkout pre-modified versions of ds xmls, run migrator
 * and replace them with latest master when finished.
 *
 * EXAMPLE:
 * see on the bottom of this file 
 * 
 * Class FalUpdateController
 * @package Ppi\TemplaVoilaPlus\Controller\Update
 */
class FalUpdateController   {
	
	protected $verbose_level = 2;
	
	const LOG_TYPE_INFO = 0;
	const LOG_TYPE_WARNING = 1;
	const LOG_TYPE_ERROR = 2;
	const DS = '/';

	protected $_startTime = null;
	protected $_logfilePath = null;


	/**
	 * Main run method
	 */
	public function execute(): void {
		
		if ($_GET['stats']) {
			$this->displayStats();
			exit;
		}
		
		$this->start();
		
		// get fce datastructures
		$datastructureRepository = GeneralUtility::makeInstance(DataStructureRepository::class);
		$dsList = $datastructureRepository->getDatastructuresByScope(AbstractDataStructure::SCOPE_FCE);
		
		foreach ($dsList as $ds) {
			$dsXml = $ds->getDataprotXML();
            $dsStructure = GeneralUtility::xml2array($dsXml);
            
            $migrateFields = [];

            // iterate ds fields - find internal_type = file items
			foreach ($dsStructure['ROOT']['el'] as $fieldName => $el) {
				if ($el['TCEforms']['config']['type'] == 'group'  &&  $el['TCEforms']['config']['internal_type'] == 'file') {
					// collect these fields
					$migrateFields[$fieldName] = $el; 
				}

				// 2nd level, if it's nested / repetitive fce
				else if ($el['type'] == 'array')  {
					// SC (section)
					foreach ($el['el'] as $fieldNameSC => $elSC) {
						// CO (repetitive object itself)
						foreach ($elSC['el'] as $fieldNameCO => $elCO) {
							if ($elCO['TCEforms']['config']['type'] == 'group'  &&  $elCO['TCEforms']['config']['internal_type'] == 'file') {
								// collect these fields
								$migrateFields[$fieldName][$fieldNameSC][$fieldNameCO] = $elCO; 
							}
						}
					}
				}
			}
			
			// START

			$this->log('DATASTRUCTURE: ' . $ds->getKey(), 0);


			if (count($migrateFields)) {
				$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
				$resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
				
				// find all ttcontents with that DS
				$query = $connectionPool->getQueryBuilderForTable('tt_content');
				$query->getRestrictions()->removeAll();
				$query->select('*')
					->from('tt_content')
					->where($query->expr()->eq('tx_templavoilaplus_ds', $query->createNamedParameter('FILE:' . $ds->getKey())))
					->andWhere($query->expr()->eq('tx_templavoilaplus_fal_migrated', '0'))
					->andWhere($query->expr()->eq('deleted', '0'))
					// ->setMaxResults(1)
					;
				
				$contentsOfCurrentDS = $query->execute()->fetchAll();
				
				$this->log('- Attempt to migrate. Nr of unprocessed FCEs of that type: ' . count($contentsOfCurrentDS) . '.  Fields to process: ' . implode(', ', array_keys($migrateFields)), 1);
				
//	$_DEBUG[$ds->getKey()] = $contentsOfCurrentDS;    continue;   // for testing - collect all ttcontents that really needs migration (has bad fields in its ds) but don't do anything
			}
			else    {
				$this->log('- No file upload fields found, nothing to migrate. Marking all contents of this type as processed (migrated = 2). Going to next DS.', 1);
				// mark all contents of this tv type as processed
				$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
				$queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
	            $queryBuilder->update('tt_content')
	                ->where($queryBuilder->expr()->eq('tx_templavoilaplus_ds', $queryBuilder->createNamedParameter('FILE:' . $ds->getKey())))
	                ->set('tx_templavoilaplus_fal_migrated', 2)
	                ->execute();
				
				// omit ds, if nothing to migrate
				continue;
			}





			// tt_content iterate
			foreach ($contentsOfCurrentDS as $ttcontent)   {
				$this->log("-- TT_CONTENT uid: {$ttcontent['uid']}, pid: {$ttcontent['pid']}", 1);
				$migrationResults = [];

				// Data from FlexForm field:
	            $data = GeneralUtility::xml2array($ttcontent['tx_templavoilaplus_flex']);
				// if ($ttcontent['uid'] == 835)   $a = 1;

				if (!is_array($data) || !count($data)) {
					if (!$ttcontent['tx_templavoilaplus_flex']) {
						$this->log("--- Flexform data is empty. Marking as processed (migrated = 3). Going to next content.", 1);
			            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
			            $queryBuilder->update('tt_content')
				            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($ttcontent['uid'], \PDO::PARAM_INT)))
				            ->set('tx_templavoilaplus_fal_migrated', 3, \PDO::PARAM_INT)
				            ->execute();
					}
					else    {
						$this->log("--- WARNING: invalid flexform xml, possible parse error. Leaving unprocessed (migrated = 0). Investigate this case. [\$data is of type: " . gettype($data) . "] Going to next content.", 0);
					}

		            continue;
	            }


	            // iterate all sheets & all languages
				// TODO: WARNING - Not tested in a project with languages and with versioning! If used in such, must be tested whether the fal relations are set properly for each  
	            foreach ($data['data'] as $sheetKey => $sheet)    {
	            	foreach ($sheet as $langKey => $lang)   {
	            		
	            		// and here find items from our $migrateFields
			            foreach ($migrateFields as $fieldId => $fieldTvConf) {
			            	//if (is_string ($lang[$fieldId]['vDEF']))
			            	//if (is_array ($lang[$fieldId]['el']))
			                $value = $lang[$fieldId]['vDEF'];
			                $this->log("--- PROCESS field: {$fieldId}, value: {$value}", 1);

		                    $fileCount = 0;
			                // if commalist of more files
			                foreach (GeneralUtility::trimExplode(',', $value, true) as $oldFilename)  {

				                // this value we expect be the filename from uploads. cleanup trailing slash, make sure there's only one, so remove if exist
					            $filePath = preg_replace('/\/$/', '', $fieldTvConf['TCEforms']['config']['uploadfolder']) . '/' . $oldFilename;
					            
					            // ---------------
					            // MAKE FAL OBJECT
				                $fileFalObject = $resourceFactory->retrieveFileOrFolderObject($filePath);
				                
				                if (!is_object($fileFalObject) || !method_exists($fileFalObject, 'getUid')) {
				                	$this->log('---- ! ERROR: can\'t create FAL object from path: ' . $filePath, 0);
				                	//throw new \Exception('$filePath problem: ' . $filePath);
					                continue;
				                }

				                // ---------------
				                // INSERT RELATION
					            // relation should be sys_file_reference:
					            // uid_local = sys_file.uid,  uid_foreign = tt_content.uid,  tablenames = 'tt_content',  table_local = 'sys_file',  fieldname = [tv field name]

					            if ($fileFalObject->getUid() > 0) {
					                $fields = [
					                    'fieldname' => $fieldId,
					                    'table_local' => 'sys_file',
					                    'pid' => $ttcontent['pid'],
					                    'uid_foreign' => $ttcontent['uid'],
					                    'uid_local' => $fileFalObject->getUid(),
					                    'tablenames' => 'tt_content',
					                    'crdate' => time(),
					                    'tstamp' => time(),
					                    'sorting' => $fileCount + 256,
					                    'sorting_foreign' => $fileCount,
					                ];

					                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
					                $migrationResults[] = $queryBuilder->insert('sys_file_reference')->values($fields)->execute();
					                ++$fileCount;
					                
					                $this->log('---- Relation created. Reference uid = ' . $queryBuilder->getConnection()->lastInsertId(), 1);
					            }
					            else    {
					            	$this->log('---- ! ERROR: can\'t retrieve FAL uid from object! Path: ' . $filePath, 0);
					            }
			                }
			                
			                // update flex data (is this really needed to be updated? i'd rather leave original value for occasional control, if it doesn't affect the fal integrity)
			                $data['data'][$sheetKey][$langKey][$fieldId]['vDEF'] = $fileCount;
					        
			            } // end each migrateFields
		            }
	            }

	            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
	            $queryBuilder->update('tt_content')->where(
	                $queryBuilder->expr()->eq(
	                    'uid',
	                    $queryBuilder->createNamedParameter($ttcontent['uid'], \PDO::PARAM_INT)
	                )
	            );

	            // mark the FCE as migrated
		        if (count($migrationResults)) {
                    // rewrite flex if needed (I don't really want to touch it if no need to)
		            //->set('tx_templavoilaplus_flex', GeneralUtility::array2xml($data))
		            $queryBuilder->set('tx_templavoilaplus_fal_migrated', 1)
		                ->execute();
		            
		            $this->log('--- MIGRATED.', 1);
		        }
		        else    {
		        	//$queryBuilder->set('tx_templavoilaplus_fal_migrated', 3)
		            //    ->execute();
			        // leave them unmigrated - in some cases the fields to process might be not found automatically... needs more work

		        	$this->log("--- ! WARNING: There were fields to migrate in this record, but no fal relations inserted. Possible reason might be no file attached to any of these fields. uid={$ttcontent['uid']}", 1);
		        }
			} // end each tt_content
		}
		
		
		// SELECT * FROM `tt_content` where CType = 'templavoilaplus_pi1'
		
		
		$this->finish();
		
		print '<br>finished';
	}


	/**
	 * Display migration statistics
	 */
	public function displayStats()  {
		
		$stats = $this->getStats();
		
		$output = "<h3>TV FCE contents FAL migrated:</h3>
 			<p>- Successfully processed [migrated=1]: <b>{$stats['count_migrated'][1]['items_of_migration_type']}</b></p>
 			<p>- Omitted, not needed to modify (no file fields in ds) [migrated=2]: <b>{$stats['count_migrated'][2]['items_of_migration_type']}</b></p>
 			<p>- Tried to migrate, but no valid flexform data found or no file attached [migrated=3]: <b>{$stats['count_migrated'][3]['items_of_migration_type']}</b></p>
 			<p>- Grouped by DS type (only processed / migrated=1):</p>";
		
		foreach ($stats['migratedByFceType'] as $type)   {
			// $name = preg_replace('/FILE:|typo3conf\/|fileadmin\/|templates\//', '', $type['tx_templavoilaplus_ds']);
			$name = $type['tx_templavoilaplus_ds'];
			$output .= "<p>$name: <b>{$type['items_of_type']}</b></p>";
		}
		
		$output .= "<h3>TV FCE contents still needs to migrate:</h3>
			 <p>- Overall: <b>{$stats['count_notMigrated']}</b></p>
			 <p>- Grouped by DS type:</p>";
		foreach ($stats['notMigratedByFceType'] as $type)   {
			// $name = preg_replace('/FILE:|typo3conf\/|fileadmin\/|templates\//', '', $type['tx_templavoilaplus_ds']);
			$name = $type['tx_templavoilaplus_ds'];
			$output .= "<p>$name: <b>{$type['items_of_type']}</b></p>";
		}
		
		if (count($stats['notMigratedByFceType']))  {
			$output .= "<p>(If you still see unmigrated FCEs here, check if such DS does exist)</p>";
		}
		
		// todo: save output to a file in temp instead
		print $output; 
	}

	/**
	 * Generate migration summary
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getStats()  {
		
		$stats = [];
		
		// count migrated
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default')->getWrappedConnection();
		$preparedStatement = $connection->prepare( 'SELECT `tx_templavoilaplus_fal_migrated`, `tx_templavoilaplus_ds`, COUNT(uid) AS `items_of_migration_type` 
			FROM `tt_content`
			WHERE (`CType` = \'templavoilaplus_pi1\') AND (`tx_templavoilaplus_fal_migrated` > 0) AND NOT deleted 
			GROUP BY `tx_templavoilaplus_fal_migrated`');
		$preparedStatement->execute();
		$res = $preparedStatement->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($res as $migrationSet) {
			$stats['count_migrated'][$migrationSet['tx_templavoilaplus_fal_migrated']] = $migrationSet;
		}
		
		
		/*$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
		$stats['count_migrated'] = $queryBuilder
			->select('*')
			->from('tt_content')
			->where($queryBuilder->expr()->eq('tx_templavoilaplus_fal_migrated', '1'))
			->andWhere($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('templavoilaplus_pi1', \PDO::PARAM_STR)))
			->execute()
			->rowCount();*/

		// count not migrated
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
		$stats['count_notMigrated'] = $queryBuilder
			->select('*')
			->from('tt_content')
			->where($queryBuilder->expr()->eq('tx_templavoilaplus_fal_migrated', '0'))
			->andWhere($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('templavoilaplus_pi1', \PDO::PARAM_STR)))
			->execute()
			->rowCount();


		// group fce types - migrated
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default')->getWrappedConnection();
		$preparedStatement = $connection->prepare( 'SELECT `tx_templavoilaplus_ds`, COUNT(uid) AS `items_of_type` 
			FROM `tt_content`
			WHERE (`CType` = \'templavoilaplus_pi1\') AND (`tx_templavoilaplus_fal_migrated` = 1) AND NOT deleted 
			GROUP BY `tx_templavoilaplus_ds`');
		$preparedStatement->execute();
		$stats['migratedByFceType'] = $preparedStatement->fetchAll(\PDO::FETCH_ASSOC);


		// group fce types - not migrated
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default')->getWrappedConnection();
		$preparedStatement = $connection->prepare( 'SELECT `tx_templavoilaplus_ds`, COUNT(uid) AS `items_of_type` 
			FROM `tt_content` 
			WHERE (`CType` = \'templavoilaplus_pi1\') AND (`tx_templavoilaplus_fal_migrated` = 0) AND NOT deleted 
			GROUP BY `tx_templavoilaplus_ds`');
		$preparedStatement->execute();
		$stats['notMigratedByFceType'] = $preparedStatement->fetchAll(\PDO::FETCH_ASSOC);

		
		
		return $stats;
	}
	
	
	
	/**
	 * Start logging
	 */
	protected function start() {
		$logDir = \TYPO3\CMS\Core\Core\Environment::getPublicPath()."/typo3temp/var/log";
		if (!is_dir($logDir)) {
			mkdir($logDir,0775);
		}

		$this->_startTime = time();

		$this->_logfilePath = $logDir."/templavoila_migrate_".date('Y.m.d_H.i').".log";
		$this->log('---------', 0);
		$this->log('* START: ' . get_class($this), 0);
	}


	/**
	 * Report reaching end
	 */
	protected function finish() {
		$this->log('* STOP: ' . get_class($this), 0);
		$this->log('---------', 0);
	}


	/**
	 * Add a line to execution report
	 *
	 * @param mixed $str
	 * @param int $verboseLevel
	 * @param int $type
	 */
	protected function log($str, $verboseLevel = 2, $type = self::LOG_TYPE_INFO) {

		if ($verboseLevel > $this->verbose_level) {
			// not intended to be logged due to verbose level setting
			return;
		}

		if ($GLOBALS['templavoila_migrateTask_reportArray'] == null) {
			$GLOBALS['templavoila_migrateTask_reportArray'] = [];
		}

		$str = ($type == self::LOG_TYPE_WARNING?'WARNING:':'').$str;
		$str = ($type == self::LOG_TYPE_ERROR?'ERROR:':'').$str;


		if ($type == self::LOG_TYPE_ERROR) {
			$GLOBALS['BE_USER']->writelog(
				-1,
				0,
				$status = 1 /* error*/,
				$code = 0,
				'['.get_class($this).']: '.date('Y.m.d H:i:s').' : ' . $str,
				array()
			);
		}

		if ($type == self::LOG_TYPE_WARNING) {
			$GLOBALS['BE_USER']->writelog(
				-1,
				0,
				$status = 0 /* message*/,
				$code = 0,
				'['.get_class($this).']: '.date('Y.m.d H:i:s').' : ' . $str,
				array()
			);
		}

		$fp = fopen($this->_logfilePath,"a+");
		fputs($fp,date('Y.m.d H:i:s')." {$str}\n");
		fclose($fp);

		$GLOBALS['templavoila_migrateTask_reportArray'][] = date('Y.m.d H:i:s') . " {$str}";
	}


}


/*
    * How to update Datastructure xml (after migration process):
	
		- OLD:
	
			<field_image type="array">
	            <TCEforms type="array">
					<config type="array">
						<type>group</type>
						<internal_type>file</internal_type>
						<allowed>gif,png,jpg,jpeg</allowed>
						<max_size>1000</max_size>
						<uploadfolder>uploads/tx_templavoilaplus</uploadfolder>
						<show_thumbs>1</show_thumbs>
						<size>1</size>
						<maxitems>1</maxitems>
						<minitems>0</minitems>
					</config>
	
		- NEW:
	
			<field_image type="array">
	            <TCEforms type="array">
					<config type="array">
						<type>inline</type>
			            <foreign_table>sys_file_reference</foreign_table>
			            <foreign_field>uid_foreign</foreign_field>
			            <foreign_sortby>sorting_foreign</foreign_sortby>
			            <foreign_table_field>tablenames</foreign_table_field>
			            <foreign_match_fields>
			                <fieldname>field_image</fieldname>	                <!-- IMPORTANT - THIS TV FIELD ID! -->
			            </foreign_match_fields>
						<foreign_label>uid_local</foreign_label>
						<foreign_selector>uid_local</foreign_selector>
						<overrideChildTca>
							<columns>
								<uid_local>
									<config>
										<appearance>
											<elementBrowserType>file</elementBrowserType>
											<elementBrowserAllowed></elementBrowserAllowed>
										</appearance>
									</config>
								</uid_local>
							</columns>
						</overrideChildTca>
						<filter>
							<userFunc>TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren</userFunc>
							<parameters>
								<allowedFileExtensions>gif,png,jpg,jpeg</allowedFileExtensions>	        <!-- FROM OLD 'allowed' -->
								<disallowedFileExtensions></disallowedFileExtensions>
							</parameters>
						</filter>
						<appearance>
							<useSortable>1</useSortable>
							<headerThumbnail>
								<field>uid_local</field>
								<width>45</width>
								<height>45c</height>
							</headerThumbnail>
							<enabledControls>
								<info>1</info>
								<new>0</new>
								<dragdrop>1</dragdrop>
								<sort>0</sort>
								<hide>1</hide>
								<delete>1</delete>
							</enabledControls>
						</appearance>
					</config>
	



	* How to run this migrator:
        
		You can run it through Scheduler task, but it might be good idea (especially for tests & debug) to run it standalone:

			tvFal = PAGE
			tvFal.typeNum = 1112
			tvFal.10 = USER
			tvFal.10.userFunc = Ppi\TemplaVoilaPlus\Controller\Update\FalUpdateController->execute
			
		and then call https://example.com/?type=1112

		You can analyse the log in typo3temp/var/log/templavoila_migrate_[time].log
		
		Display the stats (summary of contents migrated / needed to process):
		https://example.com/?type=1112&stats=1

*/