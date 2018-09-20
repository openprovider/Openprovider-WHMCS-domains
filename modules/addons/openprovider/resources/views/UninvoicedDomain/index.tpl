{include file='../header.tpl'}

<h2></h2>

<div class="op-addon panel panel-default">
	<!-- Default panel contents -->
	<div class="panel-heading"><strong>Search possible uninvoiced domains</strong></div>
	<div class="panel-body">
		<p>This page shows domains with a different start date and amount of invoices that should be provided over the years (assuming that every domain is renewed each year). Invoices with free occurances of the domain are ignored.</p>

		{if $notice}
		<div class="alert alert-{$noticeType}" role="alert">
			{$notice}
		</div>
		{/if}
	</div>

	

	<!-- Table -->
	<table class="table table-bordered">
		<thead>
				<tr>
						<th>#ID</th>
						<th>Domain</th>
						<th>Registrar</th>
						<th>Status</th>
						<th>Expected invoices</th>
						<th>Found invoices*</th>
						<th>Exact</th>
						<th>Years missing</th>
					</tr>
		</thead>
		{foreach $domains as $domain}
		<tr>
				<td>{$domain->id}</td>
				<td><a href="clientsdomains.php?userid={$domain->userid}&domainid={$domain->id}" alt="Domain page" target="_blank">{$domain->domain}</a></td>
				<td>{$domain->registrar}</td>
				<td>{$domain->status}</td>
				<td>{$domain->ceilExpectedInvoices}</td>
				<td>{$domain->actualInvoices}</td>
				<td>{$domain->invoiceDifference}</td>
				<td>
					<div class="dropdown">
						<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
							Actions
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
							<li><a href="clientsinvoices.php?userid={$domain->userid}&domainid={$domain->id}" alt="View invoices" target="_blank"><span class="glyphicon glyphicon-list-alt"></span> Invoices</a></li>
							<li><a href="{get_route route='createInvoice' id=$domain->id}" class="op-uninvoiced-domain-invoice" alt="Create invoice"><span class="glyphicon glyphicon-usd"></span> Create invoice</a></li>
							<li><a href="supporttickets.php?action=open&userid={$domain->userid}&subject={$domain->domain}" class="op-uninvoiced-domain-invoice" alt="Create Ticket" target="_blank"><span class="fa fa-comment"></span> Create Ticket</a></li>
							<li><a href="{get_route route='ignoreDomain' id=$domain->id}" class="op-uninvoiced-domain-reset-count" alt="Reset invoice count"><span class="glyphicon glyphicon-refresh"></span> Sync Invoice count</a></li>
						</ul>
					</div>
					
					
					
				</td>
		</tr>
		{/foreach}
				
	</table>
</div>
<p><i>* Only automatically created invoices with a fee</i></p>

{include file='../pagination.tpl'}