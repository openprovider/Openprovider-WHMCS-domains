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
			<a class="btn btn-primary" href="{get_route route='supportDownload'}" role="button" target="_blank">{$LANG.supportDownloadSupportButton}</a>
		{/if}
	</div>
</div>
