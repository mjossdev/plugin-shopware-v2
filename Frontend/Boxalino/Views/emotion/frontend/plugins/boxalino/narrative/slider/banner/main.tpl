{block name="frontend_narrative_banner_slider_item"}
	<div class="banner-slider--item image-slider--item"
		 data-coverImage="true"
		 data-containerSelector=".banner-slider--banner"
		 data-width="{$width}"
		 data-height="{$height}">

		{block name="frontend_widgets_banner_slider_banner"}
			<div class="banner-slider--banner">

				{block name="frontend_widgets_banner_slider_banner_picture"}
					{$thumbnails = $bxRender->getLocalizedValue($thumbnails)}
					{if $thumbnails}
						{$baseSource = $thumbnails[0].source}
						{$srcSet = ''}
						{$itemSize = ''}
						{foreach $element.viewports as $viewport}
							{$cols = ($viewport.endCol - $viewport.startCol) + 1}
							{$elementSize = $cols * $cellWidth}
							{$size = "{$elementSize}vw"}

							{if $breakpoints[$viewport.alias]}

								{if $viewport.alias === 'xl' && !$emotionFullscreen}
									{$size = "calc({$elementSize / 100} * {$baseWidth}px)"}
								{/if}

								{$size = "(min-width: {$breakpoints[$viewport.alias]}) {$size}"}
							{/if}

							{$itemSize = "{$size}{if $itemSize}, {$itemSize}{/if}"}
						{/foreach}

						{foreach $thumbnails as $image}
							{$srcSet = "{if $srcSet}{$srcSet}, {/if}{$image.source} {$image.maxWidth}w"}
						{/foreach}
					{else}
						{$baseSource = $bxRender->getLocalizedValue($desktop)}
					{/if}

					<img src="{$baseSource}" class="banner-slider--image"
						 {if $srcSet}sizes="{$itemSize}" srcset="{$srcSet}"{/if}
						 {if $altText}alt="{$bxRender->getLocalizedValue($altText)|escape}" {/if}
					/>
				{/block}
			</div>
		{/block}

		{if $link}
			{block name="frontend_widgets_banner_slider_link"}
				<a class="banner-slider--link" href="{$link}" title="{$bxRender->getLocalizedValue($title)|escape}">
					{$bxRender->getLocalizedValue($altText)}
				</a>
			{/block}
		{/if}
	</div>
{/block}