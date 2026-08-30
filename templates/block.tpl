{**
 * plugins/blocks/mostRead/templates/block.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * "Most Read" block.
 *}
{if !empty($mostRead)}
<div class="pkp_block block_most_read">
	<div class="content">
		{if !empty($blockTitle)}<span class="title">{$blockTitle|escape}</span>{/if}
		<ul class="most_read">
			{foreach from=$mostRead item=submission}
				<li class="most_read_article">
					<div class="most_read_article_title"><a href="{$submission.url|escape}">{$submission.title}</a></div>
					<div class="most_read_article_journal"><span class="fa fa-eye"></span> {$submission.metric|escape}</div>
				</li>
			{/foreach}
		</ul>
	</div>
</div>
{/if}
