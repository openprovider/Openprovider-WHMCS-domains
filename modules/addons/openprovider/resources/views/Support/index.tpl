{include file='../header.tpl'}

<h2></h2>

<div class="op-addon panel panel-default">
	<!-- Default panel contents -->
	<div class="panel-heading"><strong>{$LANG.supportDownloadSupportFileHeading}</strong></div>
	<div class="panel-body">
		{$LANG.supportDownloadSupportFileBody}

		{if $zipAvailable == 'no'}
			<div class="alert alert-danger" role="alert">{$LANG.supportZipExtensionWarning}</div>
			<input class="btn btn-primary" type="button" value="{$LANG.supportDownloadSupportButton}" disabled>
		{else}
			<script>
				fetch("{get_route route='supportDownload'}").then(response => {
					if (response.status !== 500) {
						document.getElementById('downloadLink').style.display = 'inline';
						document.getElementById('alert').style.display = 'none';

					} else {
						document.getElementById('downloadLink').style.display = 'none';
						document.getElementById('alert').style.display = 'block';
					}
				});
			</script>
			<div id='alert' class="alert alert-danger" role="alert" style="display: none;">{$LANG.moduleLogWarning}</div>
			<a id='downloadLink' class="btn btn-primary" href="{get_route route='supportDownload'}" role="button" target="_blank" style="display: none;" >{$LANG.supportDownloadSupportButton}</a>
		{/if}
	</div>
</div>
