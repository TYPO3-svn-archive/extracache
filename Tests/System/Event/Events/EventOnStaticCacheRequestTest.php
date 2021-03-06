<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 AOE media GmbH <dev@aoemedia.de>
 * All rights reserved
 *
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * test case for Tx_Extracache_System_Event_Events_EventOnStaticCacheRequest
 * @package extracache_tests
 * @subpackage System_Event_Events
 */
class Tx_Extracache_System_Event_Events_EventOnStaticCacheRequestTest extends Tx_Extracache_Tests_AbstractTestcase {
	/**
	 * @var Tx_Extracache_System_Event_Events_EventOnStaticCacheRequest
	 */
	private $event;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		$this->event = GeneralUtility::makeInstance('Tx_Extracache_System_Event_Events_EventOnStaticCacheRequest');
	}
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		unset ( $this->event );
	}

	/**
	 * Test get-methods
	 * @test
	 */
	public function getMethods() {
		$request = GeneralUtility::makeInstance('Tx_Extracache_System_StaticCache_Request');
		$frontendUser = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		$reasonForCancelation = 'test';
		$this->assertTrue( $this->event->setFrontendUser( $frontendUser ) === $this->event );
		$this->assertTrue( $this->event->setRequest( $request ) === $this->event );
		$this->assertTrue( $this->event->setReasonForCancelation( $reasonForCancelation ) === $this->event );
		$this->assertTrue( $this->event->getFrontendUser() === $frontendUser );
		$this->assertTrue( $this->event->getRequest() === $request );
		$this->assertTrue( $this->event->getReasonForCancelation() === $reasonForCancelation );
	}
}
