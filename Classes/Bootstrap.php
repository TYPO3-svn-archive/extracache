<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 AOE GmbH <dev@aoe.com>
 * All rights reserved
 *
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The bootstrap of extracache extension, initialising basic configurations
 * @package extracache
 */
final class Bootstrap {
	const ExtensionKey = 'extracache';

	/**
	 * start
	 */
	static public function start() {
        if (false === defined('REST_API_IS_RUNNING') && false === array_key_exists('eID', $_GET)) {
            // only initialize extracache-settings, if NO eID-script or REST-API-call (from restler-Extension) is running
            // if eID-script or REST-API-call (from restler-Extension) is running, than we don't must do that, because it
            // would be needless (but it would cost us a lot of time)
            self::initializeEventHandling();
            self::initializeHooks();
            self::initializeSchedulerTasks();
        }
		self::initializeXClasses();
	}

	/**
	 * initialize event-handler
	 */
	static protected function initializeEventHandling() {
		$dispatcher = GeneralUtility::makeInstance('Tx_Extracache_System_Event_Dispatcher');
		self::addEventHandlerForLogging ( $dispatcher );
		self::addEventHandlerForStaticCache ( $dispatcher );
	}
	/**
	 * Initializes hooks.
	 *
	 * @return void
	 */
	static protected function initializeHooks() {
		// Register hooks for nc_staticfilecache-extension
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['createFile_initializeVariables'][self::ExtensionKey] = 'EXT:' . self::ExtensionKey . '/Classes/Typo3/Hooks/StaticFileCache/CreateFileHook.php:tx_Extracache_Typo3_Hooks_StaticFileCache_CreateFileHook->initialize';
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['createFile_processContent'][self::ExtensionKey] = 'EXT:' . self::ExtensionKey . '/Classes/Typo3/Hooks/StaticFileCache/CreateFileHook.php:tx_Extracache_Typo3_Hooks_StaticFileCache_CreateFileHook->process';
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['processDirtyPages'][self::ExtensionKey] = 'EXT:' . self::ExtensionKey . '/Classes/Typo3/Hooks/StaticFileCache/DirtyPagesHook.php:tx_Extracache_Typo3_Hooks_StaticFileCache_DirtyPagesHook->process';

		// Register Hook that determine, block and re-queue modifications concerning file references (This is required in combination with statically cached files):
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = PATH_tx_extracache . 'Classes/Typo3/Hooks/FileReferenceModification.php:&tx_Extracache_Typo3_Hooks_FileReferenceModification';

		// Register hook to remove cache TypoScript:
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = PATH_tx_extracache . 'Classes/Typo3/TypoScriptCache.php:&tx_Extracache_Typo3_TypoScriptCache->clearCachePostProc';

		// Register hook to delete eventqueue-table:
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = PATH_tx_extracache . 'Classes/Typo3/Hooks/ClearCachePostProc.php:&tx_Extracache_Typo3_Hooks_ClearCachePostProc->clearCachePostProc';

		// Register pre-rendering cache to deliver statically published content:
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = 'EXT:'.self::ExtensionKey.'/Classes/System/StaticCache/Dispatcher.php:&tx_Extracache_System_StaticCache_Dispatcher->dispatch';

		// register hook to disable caching for faulty pages (e.g. if templaVoila could not render page):
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:'.self::ExtensionKey.'/Classes/Typo3/Hooks/AvoidFaultyPages.php:&tx_Extracache_Typo3_Hooks_AvoidFaultyPages->disableCachingOnFaultyPages';

		// Sends HTTP headers for debuging caching situations (if developmentContext is set)
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][self::ExtensionKey] = 'EXT:'.self::ExtensionKey.'/Classes/Typo3/Hooks/SendCacheDebugHeader.php:&tx_Extracache_Typo3_Hooks_SendCacheDebugHeader->sendCacheDebugHeader';

		// execute contentProcessors
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][self::ExtensionKey.'_contentProcessors'] = 'EXT:'.self::ExtensionKey.'/Classes/Typo3/Hooks/ExecuteContentProcessor.php:&tx_Extracache_Typo3_Hooks_ExecuteContentProcessor->executeContentProcessor';

		// Register hook that ignores an existing TYPO3 cache (used to force regeneration):
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'][self::ExtensionKey] = 'EXT:'.self::ExtensionKey.'/Classes/Typo3/Hooks/IgnoreTypo3Cache.php:tx_Extracache_Typo3_Hooks_IgnoreTypo3Cache->ignoreExistingCache';

		// Register hook to write gr_list to cache_pages:
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'][] = 'EXT:'.self::ExtensionKey.'/Classes/Typo3/Hooks/InsertPageIncache.php:&tx_Extracache_Typo3_Hooks_InsertPageIncache';
	}
	/**
	 * Initializes scheduler-tasks.
	 *
	 * @return void
	 */
	static protected function initializeSchedulerTasks() {
		if (TYPO3_MODE == 'BE') {
			// register scheduler-task to clean-up removed files:
			require_once PATH_tx_extracache . 'Classes/Typo3/SchedulerTaskCleanUpRemovedFiles.php';
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Extracache_Typo3_SchedulerTaskCleanUpRemovedFiles'] = array (
				'extension'        => self::ExtensionKey,
				'title'            => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_cleanUpRemovedFiles.name',
				'description'      => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_cleanUpRemovedFiles.description',
			);

			// register scheduler-task to process event-queue:
			require_once PATH_tx_extracache . 'Classes/Typo3/SchedulerTaskProcessEventQueue.php';
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Extracache_Typo3_SchedulerTaskProcessEventQueue'] = array (
				'extension'        => self::ExtensionKey,
				'title'            => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_processEventQueue.name',
				'description'      => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_processEventQueue.description',
			);

			// register scheduler-task to release cache deadlocks:
			require_once PATH_tx_extracache . 'Classes/Typo3/SchedulerTaskReleaseCacheDeadlocks.php';
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Extracache_Typo3_SchedulerTaskReleaseCacheDeadlocks'] = array (
				'extension'        => self::ExtensionKey,
				'title'            => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_releaseCacheDeadlocks.name',
				'description'      => 'LLL:EXT:' . self::ExtensionKey . '/Resources/Private/Language/locallang_db.xml:scheduler_task_releaseCacheDeadlocks.description',
				'additionalFields' => 'Tx_Extracache_Typo3_SchedulerTaskReleaseCacheDeadlocksAdditionalFields',

			);
		}
	}
	/**
	 * Initializes XCLASSES
	 */
	static protected function initializeXClasses() {
		// Define XCLASS for user authentication:
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication'] = array(
            'className' => 'Tx_Extracache_Xclass_FrontendUserAuthentication',
        );

		// Define XCLASS for nc_staticfilecache info module:
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['tx_ncstaticfilecache_infomodule'] = array(
            'className' => 'Tx_Extracache_Xclass_StaticFileCacheInfoModule',
        );
	}
	/**
	 * @param Tx_Extracache_System_Event_Dispatcher $dispatcher
	 */
	static protected function addEventHandlerForLogging(Tx_Extracache_System_Event_Dispatcher $dispatcher) {
		$dispatcher->addLazyLoadingHandler('onCleanUpRemovedFilesError', 'Tx_Extracache_System_LoggingEventHandler', 'logWarning');
		$dispatcher->addLazyLoadingHandler('onProcessCacheEventInfo', 'Tx_Extracache_System_LoggingEventHandler', 'logNotice');
		$dispatcher->addLazyLoadingHandler('onProcessCacheEventError', 'Tx_Extracache_System_LoggingEventHandler', 'logWarning');
		$dispatcher->addLazyLoadingHandler('onProcessEventQueueError', 'Tx_Extracache_System_LoggingEventHandler', 'logWarning');
		$dispatcher->addLazyLoadingHandler('onStaticCacheInfo', 'Tx_Extracache_System_LoggingEventHandler', 'logNotice');
		$dispatcher->addLazyLoadingHandler('onStaticCacheLoaded', 'Tx_Extracache_System_LoggingEventHandler', 'logNotice');
		$dispatcher->addLazyLoadingHandler('onStaticCacheWarning', 'Tx_Extracache_System_LoggingEventHandler', 'logWarning');
		$dispatcher->addLazyLoadingHandler('onStaticCacheFatalError', 'Tx_Extracache_System_LoggingEventHandler', 'logFatalError');
		$dispatcher->addLazyLoadingHandler('onReleaseCacheDeadlocksError', 'Tx_Extracache_System_LoggingEventHandler', 'logFatalError');
		$dispatcher->addLazyLoadingHandler('onReleaseCacheDeadlocksNotice', 'Tx_Extracache_System_LoggingEventHandler', 'logNotice');
	}
	/**
	 * @param Tx_Extracache_System_Event_Dispatcher $dispatcher
	 */
	static protected function addEventHandlerForStaticCache(Tx_Extracache_System_Event_Dispatcher $dispatcher) {
		$dispatcher->addLazyLoadingHandler('onFaultyPages', 'tx_Extracache_Typo3_Hooks_AvoidFaultyPages', 'handleFaultyEvent');
		$dispatcher->addLazyLoadingHandler('onProcessCacheEvent', 'Tx_Extracache_Domain_Service_CacheEventHandler', 'handleEventOnProcessCacheEvent');
		$dispatcher->addLazyLoadingHandler('onStaticCacheRequest', 'Tx_Extracache_System_StaticCache_EventHandler', 'handleEventOnStaticCacheRequest');
	}
}