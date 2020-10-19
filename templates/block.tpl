{**
 * plugins/blocks/mostRead/block.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * "Most Read" block.
 *}
<div class="pkp_block block_developed_by">
	<div class="content">
		<span class="title">{$blockTitle}</span>
			<ul class="most_read">
			{foreach from=$resultMetrics item=article}
				<li class="most_read_article">
					<div class="most_read_article_title"><a href="{url journal=$article.journalPath page="article" op="view" path=$article.articleId}">{$article.articleTitle}{if !empty($article.articleSubTitle)} {$article.articleSubTitle}{/if}</a></div>
					<div class="most_read_article_journal"><span class="fa fa-eye"></span> {$article.metric}</div>
				</li>
			{/foreach}
			</ul>
	</div>
</div>
