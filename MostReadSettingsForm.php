<?php

/**
 * @file plugins/blocks/mostRead/MostReadSettingsForm.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MostReadSettingsForm
 *
 * @ingroup plugins_blocks_mostRead
 *
 * @brief Form for journal managers to modify Most Read plugin settings
 */

namespace APP\plugins\blocks\mostRead;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;

class MostReadSettingsForm extends Form
{
    /** @var int */
    public $_contextId;

    /** @var MostReadBlockPlugin */
    public $_plugin;

    /**
     * Constructor
     *
     * @param MostReadBlockPlugin $plugin
     * @param int $contextId
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidator($this, 'mostReadDays', 'required', 'plugins.blocks.mostRead.settings.mostReadDaysRequired'));
        $this->addCheck(new FormValidatorCustom($this, 'mostReadCount', 'optional', 'plugins.blocks.mostRead.settings.mostReadCountInvalid', fn ($value) => ctype_digit((string) $value) && (int) $value >= 1));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $mostReadBlockTitle = (array) json_decode($this->_plugin->getSetting($this->_contextId, 'mostReadBlockTitle') ?? '');
        $this->_data = [
            'mostReadDays' => $this->_plugin->getSetting($this->_contextId, 'mostReadDays'),
            'mostReadCount' => $this->_plugin->getSetting($this->_contextId, 'mostReadCount'),
            'mostReadBlockTitle' => $mostReadBlockTitle,
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['mostReadDays', 'mostReadCount', 'mostReadBlockTitle']);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $mostReadBlockTitle = json_encode($this->getData('mostReadBlockTitle'));
        $this->_plugin->updateSetting($this->_contextId, 'mostReadDays', $this->getData('mostReadDays'), 'string');
        $this->_plugin->updateSetting($this->_contextId, 'mostReadCount', $this->getData('mostReadCount'), 'string');
        $this->_plugin->updateSetting($this->_contextId, 'mostReadBlockTitle', $mostReadBlockTitle, 'string');

        // Empty the current cache so new settings take effect immediately
        $this->_plugin->clearCache($this->_contextId);

        return parent::execute(...$functionArgs);
    }
}
