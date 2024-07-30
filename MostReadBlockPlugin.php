<?php

/**
 * @file plugins/blocks/mostRead/MostReadBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MostReadBlockPlugin
 * @ingroup plugins_blocks_mostRead
 *
 * @brief Class for "Most Read" block plugin
 */

namespace APP\plugins\blocks\mostRead;

use APP\core\Application;
use APP\core\Services;
use APP\i18n\AppLocale;
use APP\facades\Repo;
use APP\template\TemplateManager;
use Illuminate\Support\Collection;
use PKP\cache\CacheManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction; 
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\BlockPlugin;
use PKP\submission\PKPSubmission;

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
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', $this->smartyPluginUrl(...));

                $form = new MostReadSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

	/**
	 * @copydoc BlockPlugin::getContents
	 */
	function getContents($templateMgr, $request = null) 
	{
		$context = $request->getContext();
		if (!$context) return '';
		
		$cacheManager = CacheManager::getManager();
		$cache = $cacheManager->getCache($context->getId(), 'mostread' , array($this, 'getMostReadCache'));

		$daysToStale = 1;
		$cachedMetrics = false;

		if (time() - $cache->getCacheTime() > 60 * 60 * 24 * $daysToStale) {
			$cachedMetrics = $cache->getContents();
			$cache->flush();
		}

		$metrics = $cache->getContents();

		if (!$metrics && $cachedMetrics) {
			$metrics = $cachedMetrics;
			$cache->setEntireCache($cachedMetrics);
		} elseif (!$metrics) {
			$cache->flush();
		}

		$locale = AppLocale::getLocale();
		$mostReadBlockTitle = (array) json_decode($this->getSetting($context->getId(), 'mostReadBlockTitle'));
		$blockTitle = $mostReadBlockTitle[$locale] ? $mostReadBlockTitle[$locale] : "";
		$templateMgr->assign('blockTitle', $blockTitle);

		$mostRead = [];
		foreach($metrics as $metric){
			$submission = Repo::submission()->get($metric['submissionId']);
			if(isset($submission) && $submission?->getCurrentPublication()->getData('status') === PKPSubmission::STATUS_PUBLISHED) 
			{
				$mostRead[] = [
					'url' => Application::get()->getRequest()->url($context?->getPath(), 'article', 'view', [$submission->getBestId()]),
					'metric' => $metric['metric'],
					'title' => $submission?->getCurrentPublication()->getLocalizedFullTitle($locale, 'html')
                ];
			}
		}

		$templateMgr->assign('mostRead', $mostRead);
		return parent::getContents($templateMgr, $request);
	}

	/**
	 * Set cache
	 * @param $cache object
	 */
	
	function getMostReadCache($cache): array 
	{
		$mostReadDays = (int) $this->getSetting($cache->context, 'mostReadDays');
		if (empty($mostReadDays)){
			$mostReadDays = 7;
		}
		$dayString = "-" . $mostReadDays . " days";

        $mostRead = Services::get('publicationStats')->getTotals([
            'dateStart' => date('Y-m-d', strtotime($dayString)),
            'contextIds' => [$cache->context],
            'count' => 5,
			'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE],
        ]);

        $results = (new Collection($mostRead))
            ->map(function($result) {
                $submission = Repo::submission()->get($result->submission_id);
                return [
                    'submissionId' => $result->submission_id,
                    'metric' => $result->metric
                ];
            })
            ->filter(function($result) {
                return $result['submissionId'] && $result['metric'];
            })
            ->toArray();

		$cache->setEntireCache($results);
		return $results;
    }
}
?>
