{**
 * plugins/blocks/mostRead/settingsForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Most read plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#mostReadSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="mostReadSettingsForm" method="post" action="{url op="manage" category="blocks" plugin=$pluginName verb="save"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="mostReadFormNotification"}

	{fbvFormArea id="mostReadDisplayOptions" title="plugins.blocks.mostRead.settings.title"}

		{fbvFormSection for="mostReadDays"}
			{fbvElement type="text" label="plugins.blocks.mostRead.settings.days" id="mostReadDays" value=$mostReadDays}
		{/fbvFormSection}

		{fbvFormSection for="mostReadBlockTitle"}
			{fbvElement type="text" label="plugins.blocks.mostRead.settings.blockTitle" id="mostReadBlockTitle" value=$mostReadBlockTitle multilingual=true}
		{/fbvFormSection}		

	{/fbvFormArea}

	{fbvFormButtons id="WGLSettingsFormSubmit" submitText="common.save" hideCancel=true}

</form>
