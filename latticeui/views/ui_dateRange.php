<div class="ui-DateRange" data-field='reportDateRange'>
	<?if(isset($label)):?>
		<label><?=$label;?></label>
	<?endif;?>
	<input type="text" value="<?=$startDate;?>-<?=$endDate;?>" />
	<img src="<?=url::base();?>lattice/lattice/resources/images/spinner.gif" width="12" height="12" alt="saving date range..." class="hidden spinner" />
</div>
