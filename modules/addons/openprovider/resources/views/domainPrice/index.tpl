{include file='../header.tpl'}

<h2></h2>

<div class="op-addon panel panel-default">
	<!-- Default panel contents -->
	<div class="panel-heading"><strong>Import TLDs</strong></div>
	<div class="panel-body">
		<p></p>

		{if $status == 'moduleDeactivated'}
		<div class="alert alert-danger" role="alert">
			<strong>WARNING</strong> Activate the OpenProvider registrar module with the correct settings. Otherwise, you can not import all the TLDs.
		</div>
        {else}

        <a href="{get_route route='synchroniseTLD'}">Synchronise</a>
		{/if}
	</div>

</div>