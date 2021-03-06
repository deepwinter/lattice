<div class="ui-FileElement <?=$class;?> <?=($value['filename'])?'':'empty'?>" data-field="<?=$name;?>" data-extensions="<?=$extensions;?>" data-maxlength="<?=$maxlength;?>">

	<label><?=isset($label)?$label:'File';?></label>

	<div class="fineuploader-lattice" id="fineuploader-lattice-<?=$name;?>"></div>

	<div class="wrapper">
		
		<p class="<?=str_replace(',',' ',$extensions);?> fileName"><?if ($value['filename']):?><?=$value['filename'];?><?else:?>No file uploaded yet&hellip;<?endif;?></p>
		
		<div class="status hidden">
			<img src="<?=url::site('modules/lattice/resources/images/bar.gif', null, false);?>" class="progress" />
			<span class="message hidden"></span>
		</div>

		<div class="controls clearFix">
			<?if ($value):?>
				<a title="download <?=$value['filename'];?>"  class="command downloadLink" href="<?=url::site("file/download/{$value['id']}");?>">&darr;</a>
				<a title="clear this file" class="command clearImageLink" href="#">&times;</a>
			<?else:?>
				<a title="download" class="command downloadLink hidden" target="_blank" href="#">&darr;</a>
				<a title="clear this file" class="command clearImageLink hidden" href="#">&times;</a>
			<?endif;?>
		</div>

	</div>

</div>
