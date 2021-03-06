<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 AOE GmbH <dev@aoe.com>
*  All rights reserved
*
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package extracache
 */
class Tx_Extracache_Validation_Validator_CleanerStrategy extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {
	/**
	 * @param	Tx_Extracache_Domain_Model_CleanerStrategy $cleanerStrategy
	 * @return	void
	 */
    protected function isValid($cleanerStrategy) {
		if($this->getCleanerStrategyRepository()->hasStrategy($cleanerStrategy->getKey())) {
			$this->addError('cleanerStrategy with key ' . $cleanerStrategy->getKey() . ' does already exist!', 1289897851);
		}
		$this->childrenModeIsValid( $cleanerStrategy->getChildrenMode() );
		$this->elementModeIsValid( $cleanerStrategy->getElementsMode() );
		$this->actionsAreValid( $cleanerStrategy->getActions() );
	}

	
	/**
	 * @return Tx_Extracache_Domain_Repository_CleanerStrategyRepository
	 */
	protected function getCleanerStrategyRepository() {
		return GeneralUtility::makeInstance('Tx_Extracache_Domain_Repository_CleanerStrategyRepository');
	}

	/**
	 * @param integer $actions
	 */
	private function actionsAreValid($actions) {
		$actionsContainActionStaticUpdate = $actions & Tx_Extracache_Domain_Model_CleanerStrategy::ACTION_StaticUpdate;
		$actionsContainActionStaticClear = $actions & Tx_Extracache_Domain_Model_CleanerStrategy::ACTION_StaticClear;
		$actionsContainActionStaticDirty = $actions & Tx_Extracache_Domain_Model_CleanerStrategy::ACTION_StaticDirty;
		$actionsContainActionTYPO3Clear = $actions & Tx_Extracache_Domain_Model_CleanerStrategy::ACTION_TYPO3Clear;
		if(
			(boolean) $actionsContainActionStaticUpdate === FALSE &&
			(boolean) $actionsContainActionStaticClear === FALSE &&
			(boolean) $actionsContainActionStaticDirty === FALSE &&
			(boolean) $actionsContainActionTYPO3Clear === FALSE
		) {
			$this->addError('actions ' . $actions . ' do not contain any valid action!', 1289897852);
		}
	}
	/**
	 * @param string $childrenMode
	 */
	private function childrenModeIsValid($childrenMode) {
		if(!in_array($childrenMode, Tx_Extracache_Domain_Model_CleanerStrategy::getSupportedChildModes())) {
			$this->addError('childrenMode ' . $childrenMode . ' is not supported!', 1289897853);
		}
	}
	/**
	 * @param string $elementMode
	 */
	private function elementModeIsValid($elementMode) {
		if(!in_array($elementMode, Tx_Extracache_Domain_Model_CleanerStrategy::getSupportedElementModes())) {
			$this->addError('elementMode ' . $elementMode . ' is not supported!', 1289897854);
		}
	}
}