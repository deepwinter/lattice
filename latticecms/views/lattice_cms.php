<div id="cms" class="module classPath-lattice_modules_CMS rootObjectId-<? echo Graph::getRootNode(Kohana::config('cms.graphRootNode'))->id; ?> navigation-navigation userLevel-<?=$userlevel;?>">
	<?=$navigation;?>

<?if(Kohana::config('latticecms.localization')):?>
	<div class="localizationControls">
		<div class="localizationControls container_12">
			<ul class="clearFix">
			<?foreach($languages as $language):?>
				<li><a data-lang="<?=$language->code;?>" href="#"><?=$language->fullname;?></a><li>
			<?endforeach;?>
			</ul>
		</div>
	</div>
<?endif;?>
	<div class="pagesPane clearFix">
		<div id="pageContainer" class="clearFix"></div>
	</div>
</div>
