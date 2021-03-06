<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 AOE media GmbH <dev@aoemedia.de>
 *  All rights reserved
 *
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Abstract base class for extracache Database-Tests
 *
 * @package extracache_tests
 */
abstract class Tx_Extracache_Tests_AbstractDatabaseTestcase extends tx_phpunit_database_testcase {

	/**
	 * Initializes common extensions.
	 *
	 * @return void
	 */
	protected function initializeCommonExtensions() {
		if (ExtensionManagementUtility::isLoaded('aoe_dbsequenzer')) {
			$this->importExtensions(array('aoe_dbsequenzer'));
		}
	}
}