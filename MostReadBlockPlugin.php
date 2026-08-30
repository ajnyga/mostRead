<?php

/**
 * @file plugins/blocks/mostRead/MostReadBlockPlugin.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MostReadBlockPlugin
 *
 * @ingroup plugins_blocks_mostRead
 *
 * @brief Class for "Most Read" block plugin
 */

namespace APP\plugins\blocks\mostRead;

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PKP\core\JSONMessage;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\BlockPlugin;
use PKP\submission\PKPSubmission;

class MostReadBlockPlugin extends BlockPlugin
{
    /** Cache lifetime in seconds (1 day). */
    private const CACHE_TTL = 60 * 60 * 24;

    /**
     * Install default settings on journal creation.
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin.
     */
    public function getDisplayName()
    {
        return __('plugins.blocks.mostRead.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.blocks.mostRead.description');
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, array_merge($actionArgs, ['verb' => 'settings'])),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
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
     * @copydoc BlockPlugin::getContents()
     */
    public function getContents($templateMgr, $request = null)
    {
        $context = $request?->getContext();
        if (!$context) {
            return '';
        }

        $contextId = $context->getId();

        $metrics = Cache::remember(
            $this->getCacheKey($contextId),
            self::CACHE_TTL,
            fn () => $this->loadMostRead($contextId)
        );

        $locale = Locale::getLocale();

        $mostReadBlockTitle = (array) json_decode($this->getSetting($contextId, 'mostReadBlockTitle') ?? '');
        $blockTitle = $mostReadBlockTitle[$locale] ?? '';
        $templateMgr->assign('blockTitle', $blockTitle);

        $mostRead = [];
        foreach ($metrics as $metric) {
            $submission = Repo::submission()->get($metric['submissionId']);
            if (!$submission) {
                continue;
            }
            $publication = $submission->getCurrentPublication();
            if (!$publication || (int) $publication->getData('status') !== PKPSubmission::STATUS_PUBLISHED) {
                continue;
            }
            $mostRead[] = [
                'url' => $request->url($context->getPath(), 'article', 'view', [$submission->getBestId()]),
                'metric' => $metric['metric'],
                'title' => $publication->getLocalizedFullTitle($locale, 'html'),
            ];
        }

        $templateMgr->assign('mostRead', $mostRead);
        return parent::getContents($templateMgr, $request);
    }

    /**
     * Build the cache key for a given context.
     */
    public function getCacheKey(int $contextId): string
    {
        return "plugins.blocks.mostRead.{$contextId}";
    }

    /**
     * Clear the cached metrics for a given context.
     */
    public function clearCache(int $contextId): void
    {
        Cache::forget($this->getCacheKey($contextId));
    }

    /**
     * Query the most read submissions for a context.
     *
     * @return array<int, array{submissionId: int, metric: int}>
     */
    public function loadMostRead(int $contextId): array
    {
        $mostReadDays = (int) $this->getSetting($contextId, 'mostReadDays');
        if (empty($mostReadDays)) {
            $mostReadDays = 7;
        }
        $dayString = '-' . $mostReadDays . ' days';

        $mostReadCount = (int) $this->getSetting($contextId, 'mostReadCount');
        if ($mostReadCount < 1) {
            $mostReadCount = 5;
        }

        $mostRead = app()->get('publicationStats')->getTotals([
            'dateStart' => date('Y-m-d', strtotime($dayString)),
            'contextIds' => [$contextId],
            'count' => $mostReadCount,
            'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE],
        ]);

        return (new Collection($mostRead))
            ->map(fn ($result) => [
                'submissionId' => $result->submission_id,
                'metric' => $result->metric,
            ])
            ->filter(fn ($result) => $result['submissionId'] && $result['metric'])
            ->values()
            ->toArray();
    }
}
