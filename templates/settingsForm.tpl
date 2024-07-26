{**
 * plugins/blocks/mostRead/settingsForm.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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

<form class="pkp_form" id="mostReadSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="blocks" plugin=$pluginName verb="settings" save=true}">
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

	{fbvFormButtons}

</form>
