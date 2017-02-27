{block name='frontend_index_content' prepend}
	{if count($sSearchResults.sSuggestions)}
		<h3 class="cat-filter--headline">
			{s namespace='boxalino/intelligence' name='relaxation/didyoumean'}Did you mean...{/s}
		</h3>
		<div class="block-group modal--compare">
			{foreach $sSearchResults.sSuggestions as $suggestion}
				<div class="block compare--group">
					<a href="{url controller='search' sSearch=$suggestion.text}" title="{$suggestion.text|escape}">
						{$suggestion.text|escape} ({$suggestion.count})
					</a>
				</div>
			{/foreach}
		</div>
	{/if}
{/block}