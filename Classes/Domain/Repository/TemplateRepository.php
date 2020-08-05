<?php
namespace Ppi\TemplaVoilaPlus\Domain\Repository;

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

use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class TemplateRepository implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * Retrieve a single templateobject by uid or xml-file path
     *
     * @param integer $uid
     *
     * @return \Ppi\TemplaVoilaPlus\Domain\Model\Template
     */
    public function getTemplateByUid($uid)
    {
        return GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Model\Template::class, $uid);
    }

    /**
     * Retrieve template objects which are related to a specific datastructure
     *
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $ds
     * @param integer $storagePid
     *
     * @return array
     */
    public function getTemplatesByDatastructure(\Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $ds, $storagePid = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $queryBuilder
            ->select('uid')
            ->from('tx_templavoilaplus_tmplobj')
            ->where(
                $queryBuilder->expr()->eq('datastructure', $queryBuilder->createNamedParameter($ds->getKey()))
        );
        if ((int)$storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT))
            );
        }
        $toList = $queryBuilder
            ->execute()
            ->fetchAll();

        $toCollection = array();
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, array($this, 'sortTemplates'));

        return $toCollection;
    }

    /**
     * Retrieve template objects with a certain scope within the given storage folder
     *
     * @param integer $storagePid
     * @param integer $scope
     *
     * @return array
     */
    public function getTemplatesByStoragePidAndScope($storagePid, $scope)
    {
        $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
        $toCollection = array();
        foreach ($dsList as $dsObj) {
            $toCollection = array_merge($toCollection, $this->getTemplatesByDatastructure($dsObj, $storagePid));
        }
        usort($toCollection, array($this, 'sortTemplates'));

        return $toCollection;
    }

    /**
     * Retrieve template objects which have a specific template as their parent
     *
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\Template $to
     * @param integer $storagePid
     *
     * @return array
     */
    public function getTemplatesByParentTemplate(\Ppi\TemplaVoilaPlus\Domain\Model\Template $to, $storagePid = 0)
    {
        $toList = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoilaplus_tmplobj.uid',
            'tx_templavoilaplus_tmplobj',
            'tx_templavoilaplus_tmplobj.parent=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($to->getKey(), 'tx_templavoilaplus_tmplobj')
            . ((int)$storagePid > 0 ? ' AND tx_templavoilaplus_tmplobj.pid = ' . (int)$storagePid : ' AND pid!=-1')
            . ' AND NOT deleted'
            . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_tmplobj')
        );
        $toCollection = array();
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, array($this, 'sortTemplates'));

        return $toCollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoilaplus_tmplobj objects
     *
     * @param integer $storagePid
     *
     * @return array
     */
    public function getAll($storagePid = 0)
    {
        $toList = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoilaplus_tmplobj.uid',
            'tx_templavoilaplus_tmplobj',
            '1=1'
            . ((int)$storagePid > 0 ? ' AND tx_templavoilaplus_tmplobj.pid = ' . (int)$storagePid : ' AND tx_templavoilaplus_tmplobj.pid!=-1')
            . ' AND NOT deleted'
            . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_tmplobj')
        );
        $toCollection = array();
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, array($this, 'sortTemplates'));

        return $toCollection;
    }

    /**
     * Sorts templates alphabetically
     *
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\Template $obj1
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\Template $obj2
     *
     * @return integer Result of the comparison (see strcmp())
     * @see usort()
     * @see strcmp()
     */
    public function sortTemplates($obj1, $obj2)
    {
        return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
    }

    /**
     * Find all folders with template objects
     *
     * @return array
     */
    public function getTemplateStoragePids()
    {
    	/** @var QueryBuilder $queryBuilder */
    	$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $queryBuilder
            ->select('pid')
            ->from('tx_templavoilaplus_tmplobj')
            ->where(
                $queryBuilder->expr()->gte('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
        );
        
        $list = [];
        foreach($queryBuilder
            ->execute()
            ->fetchAll()
	             as $row)   {
        	$list[] = $row['pid'];
        }

        return array_unique($list);
    }

    /**
     * @param integer $pid
     *
     * @return integer
     */
    public function getTemplateCountForPid($pid)
    {
    	/** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
    	$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $queryBuilder
            ->select('title')
            ->from('tx_templavoilaplus_tmplobj')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)));

        return $queryBuilder
            ->execute()
            ->rowCount();
    }
}
