<?php

/**
 * @file plugins/blocks/mostRead/MostReadSettingsForm.inc.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MostReadSettingsForm
 * @ingroup plugins_generic_mostRead
 *
 * @brief Form for journal managers to modify Most Read plugin settings
 */

 namespace APP\plugins\blocks\mostRead;

 use APP\template\TemplateManager;
 use PKP\cache\CacheManager;
 use PKP\form\Form;

class MostReadSettingsForm extends Form {

    /** @var int */
    public $_contextId;

    /** @var object */
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

		$this->addCheck(new \PKP\form\validation\FormValidator($this, 'mostReadDays', 'required', 'plugins.blocks.mostRead.settings.mostReadDaysRequired'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
		$mostReadBlockTitle = (array) json_decode($this->_plugin->getSetting($this->_contextId, 'mostReadBlockTitle'));
        $this->_data = [
            'mostReadDays' => $this->_plugin->getSetting($this->_contextId, 'mostReadDays'),
			'mostReadBlockTitle' => $mostReadBlockTitle,
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
		$this->readUserVars(array('mostReadDays', 'mostReadBlockTitle'));
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
		$this->_plugin->updateSetting($this->_contextId, 'mostReadBlockTitle', $mostReadBlockTitle, 'string');
        
		# empty current cache
		$cacheManager = CacheManager::getManager();
		$cache = $cacheManager->getCache('mostread', $this->_contextId, array($this->_plugin, 'getMostReadCache'));
		$cache->flush();		
		parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\blocks\mostRead\MostReadSettingsForm', '\MostReadSettingsForm');
}