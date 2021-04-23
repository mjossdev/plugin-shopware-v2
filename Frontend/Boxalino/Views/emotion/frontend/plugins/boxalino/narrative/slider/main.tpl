{block name="frontend_narrative_banner_slider"}
	<div class="emotion--banner-slider image-slider"
		 data-image-slider="true"
		 data-thumbnails="false"
		 data-lightbox="false"
		 data-loopSlides="true"
		 data-animationSpeed="{$banner_slider_scrollspeed}"
		 data-arrowControls="{if $banner_slider_arrows}true{else}false{/if}"
		 data-autoSlideInterval="{$banner_slider_rotatespeed}"
		 data-autoSlide="{if $banner_slider_rotation}true{else}false{/if}"
		 data-imageSelector=".image-slider--item">

		{block name="frontend_narrative_banner_slider_container"}
			<div class="banner-slider--container image-slider--container bx-narrative" data-bx-variant-uuid="{$narrative_bx_request_uuid}" data-bx-narrative-name="products-list" data-bx-narrative-group-by="{$narrative_bx_request_groupby}">

				{block name="frontend_narrative_banner_slider_slide"}
					<div class="banner-slider--slide image-slider--slide">
						{foreach $bxSubRenderings as $subRendering}
							{$additionalParameter=[]}
							{$additionalParameter['list_index'] = $i++}
							{$bxRender->renderElement($subRendering.visualElement, $additionalParameter)}
						{/foreach}
					</div>
				{/block}

				{block name="frontend_widgets_banner_slider_navigation"}
					{if $banner_slider_numbers}
						<div class="image-slider--dots">
							{foreach $bxSubRenderings as $link}
								<div class="dot--link">{$link@iteration}</div>
							{/foreach}
						</div>
					{/if}
				{/block}
			</div>
		{/block}
	</div>
{/block}