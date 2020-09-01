<?php

namespace  Ppi\TemplaVoilaPlus\Task;

use Ppi\TemplaVoilaPlus\Controller\Update\FalUpdateController;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class FalMigrateTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {
	

	public function execute() {
		
		$controller = GeneralUtility::makeInstance(FalUpdateController::class);
		$controller->execute();
		return true;
	}
	
	
	
	/**
	 * This method is used to add a message to the internal queue
	 *
	 * @param    string    the message itself
	 * @param    integer    message level (-1 = success (default), 0 = info, 1 = notice, 2 = warning, 3 = error)
	 * @return    void
	 */
	protected function addMessage($message, $severity = FlashMessage::OK) {
		$message = GeneralUtility::makeInstance(
			'\TYPO3\CMS\Core\Messaging\FlashMessage',
			$message,
			'',
			$severity
		);

		GeneralUtility::makeInstance(FlashMessageQueue::class)->addMessage($message);
	}
}