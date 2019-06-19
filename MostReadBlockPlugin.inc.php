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
		return __('plugins.block.mostRead.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.mostRead.description');
	}

	/**
	 * @see BlockPlugin::getContents
	 */
	function getContents($templateMgr, $request = null) {
		$context = $request->getContext();
		if (!$context) return '';

		$metricsDao = DAORegistry::getDAO('MetricsDAO');
		$cacheManager =& CacheManager::getManager();
		$cache  =& $cacheManager->getCache('mostread', $context->getId(), array($this, '_cacheMiss'));
		$daysToStale = 1;
		$cachedMetrics = false;

		if (time() - $cache->getCacheTime() > 60 * 60 * 24 * $daysToStale) {
			$cachedMetrics = $cache->getContents();
			$cache->flush();
		}
		$resultMetrics = $cache->getContents();

		if (!$resultMetrics && $cachedMetrics) {
			$resultMetrics = $cachedMetrics;
			$cache->setEntireCache($cachedMetrics);
		} elseif (!$resultMetrics) {
			$cache->flush();
		}

		$templateMgr->assign('resultMetrics', $resultMetrics);

		return parent::getContents($templateMgr, $request);
	}

	function _cacheMiss($cache) {
			$metricsDao = DAORegistry::getDAO('MetricsDAO');
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$request = Application::getRequest();
			$context = $request->getContext();

			$currentDate = date('Ymd');
			$weekAgo = date('Ymd', strtotime("-1 week"));

			$result = $metricsDao->retrieve(
				"SELECT submission_id, SUM(metric) AS metric FROM metrics WHERE (day BETWEEN $weekAgo AND $currentDate) AND (assoc_type='515' AND submission_id IS NOT NULL) AND (context_id='?') GROUP BY submission_id ORDER BY metric DESC LIMIT 5", (int) $context->getId()
			);

			while (!$result->EOF) {
				$resultRow = $result->GetRowAssoc(false);
				$article = $publishedArticleDao->getById($resultRow['submission_id']);	
				$journal = $journalDao->getById($article->getJournalId());
				$articles[$resultRow['submission_id']]['journalPath'] = $journal->getPath();
				$articles[$resultRow['submission_id']]['articleId'] = $article->getBestArticleId();
				$articles[$resultRow['submission_id']]['articleTitle'] = $article->getLocalizedTitle();
				$articles[$resultRow['submission_id']]['articleSubTitle'] = $article->getLocalizedSubtitle();
				$articles[$resultRow['submission_id']]['metric'] = $resultRow['metric'];
				$result->MoveNext();
			}
			$result->Close();			
			$cache->setEntireCache($articles);
			return $result;
	}

	function imagem($request) {
		$plugin = $this->getPlugin();
		$context = $this->getContext();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $plugin->getName());
		$templateMgr->assign('pluginBaseUrl', $request->getBaseUrl() . '/' . $plugin->getPluginPath());

		$displayimg = "/" . $this->getPluginPath() . "imagens/icon.png";

		$templateMgr->assign('displayimg', $displayimg);

		return parent::imagem($templateMgr, $request);
	
	}
}

?>