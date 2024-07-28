{**
 * plugins/blocks/mostRead/block.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * "Most Read" block.
 *}
<div class="pkp_block block_developed_by">
	<div class="content">
		{if isset($blockTitle) }<span class="title">{$blockTitle}</span>{/if}
			<ul class="most_read">
			{foreach from=$mostRead item=submission}
				<li class="most_read_article">
					<div class="most_read_article_title"><a href="{$submission.url}">{$submission.title}</a></div>
					<div class="most_read_article_journal"><span class="fa fa-eye"></span> {$submission.metric}</div>
				</li>
			{/foreach}
			</ul>
	</div>
</div>
