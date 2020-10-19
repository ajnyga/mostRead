<?php

/**
 * @file plugins/blocks/mostRead/MostReadBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MostReadBlockPlugin
 * @ingroup plugins_blocks_mostRead
 *
 * @brief Class for "Most Read" block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class MostReadBlockPlugin extends BlockPlugin {

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.blocks.mostRead.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.blocks.mostRead.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array_merge($actionArgs, array('verb' => 'settings'))),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$this->import('MostReadSettingsForm');
		$context = Application::getRequest()->getContext();
		$contextId = ($context && isset($context) && $context->getId()) ? $context->getId() : CONTEXT_SITE;
		switch($request->getUserVar('verb')) {
			case 'settings':
				$settingsForm = new MostReadSettingsForm($this, $contextId);
				$settingsForm->initData();
				return new JSONMessage(true, $settingsForm->fetch($request));
			case 'save':
				$settingsForm = new MostReadSettingsForm($this, $contextId);
				$settingsForm->readInputData();
				if ($settingsForm->validate()) {
					$settingsForm->execute();
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification(
						$request->getUser()->getId(),
						NOTIFICATION_TYPE_SUCCESS,
						array('contents' => __('plugins.blocks.mostRead.settings.saved'))
					);
					return new JSONMessage(true);
				}
				return new JSONMessage(true, $settingsForm->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc BlockPlugin::getContents
	 */
	function getContents($templateMgr, $request = null) {
		$context = $request->getContext();
		if (!$context) return '';

		$metricsDao = DAORegistry::getDAO('MetricsDAO');
		
		$cacheManager = CacheManager::getManager();
		$cache = $cacheManager->getCache($context->getId(), 'mostread' , array($this, '_cacheMiss'));

		$daysToStale = 1;

		if (time() - $cache->getCacheTime() > 60 * 60 * 24 * $daysToStale) {
			$cache->flush();
		}
		$resultMetrics = $cache->getContents();

		$templateMgr->assign('resultMetrics', $resultMetrics);

		$mostReadBlockTitle = unserialize($this->getSetting($context->getId(), 'mostReadBlockTitle'));
		$locale = AppLocale::getLocale();
		$blockTitle = $mostReadBlockTitle[$locale] ? $mostReadBlockTitle[$locale] : __('plugins.blocks.mostRead.settings.blockTitle');
		$templateMgr->assign('blockTitle', $blockTitle);

		return parent::getContents($templateMgr, $request);
	}

	/**
	 * Set cache
	 * @param $cache object
	 */
	
	function _cacheMiss($cache) {
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		
		$journalDao = DAORegistry::getDAO('JournalDAO');
	
		$mostReadDays = (int) $this->getSetting($cache->context, 'mostReadDays');
		if (empty($mostReadDays)){
			$mostReadDays = 120;
		}
		$dayString = "-" . $mostReadDays . " days";
		$daysAgo = date('Ymd', strtotime($dayString));
		$currentDate = date('Ymd');

		$filter = array(
		        STATISTICS_DIMENSION_CONTEXT_ID => $cache->context,
		        STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_SUBMISSION_FILE,
		);
		$filter[STATISTICS_DIMENSION_DAY]['from'] = $daysAgo;
		$filter[STATISTICS_DIMENSION_DAY]['to'] = $currentDate;
		$orderBy = array(STATISTICS_METRIC => STATISTICS_ORDER_DESC);
		$column = array(
		        STATISTICS_DIMENSION_SUBMISSION_ID,
		);
		import('lib.pkp.classes.db.DBResultRange');
		$dbResultRange = new DBResultRange(5);
		
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$result = $metricsDao->getMetrics(OJS_METRIC_TYPE_COUNTER, $column, $filter, $orderBy, $dbResultRange);
		
		foreach ($result as $resultRecord) {
				$submissionId = $resultRecord[STATISTICS_DIMENSION_SUBMISSION_ID];
				$article = $submissionDao->getById($submissionId);
				
		    $journal = $journalDao->getById($article->getJournalId());
		    $articles[$submissionId]['journalPath'] = $journal->getPath();
		    $articles[$submissionId]['articleId'] = $article->getBestArticleId();
		    $articles[$submissionId]['articleTitle'] = $article->getLocalizedTitle();
		    $articles[$submissionId]['metric'] = $resultRecord[STATISTICS_METRIC];
		}
		$cache->setEntireCache($articles);
		return $result;
    }
}
?>
