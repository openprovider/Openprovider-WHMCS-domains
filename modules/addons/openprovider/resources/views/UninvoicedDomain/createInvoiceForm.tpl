{include file='../header.tpl'}

<div class="panel panel-default">
	<!-- Default panel contents -->
	<div class="panel-heading"><strong>Create invoice for possible uninvoiced {$domain->domain}</strong></div>
	
	<div class="panel-body">

		{if isset($error)}
			<div class="alert alert-danger" role="alert">{$error}</div>
		{/if}

		{if isset($invoice)}
			<div class="alert alert-success" role="alert">The invoice has been created.</div>
		{/if}

		<div class="row">
			<div class="col-xs-6 col-md-3 op-col">
				<div class="op-found-invoices"> 
					<span class="number">{$domain->getFoundInvoices()}</span> Found invoices
				</div>
			</div>
			<div class="col-xs-6 col-md-3 op-col">
				<div class="op-expected-invoices"> 
					<span class="number">{$domain->getExpectedInvoices()}</span> Expected invoices
				</div>
			</div>
			<div class="col-xs-6 col-md-3 op-col">
				<div class="op-difference-invoices"> 
					<span class="number">{$domain->getInvoiceDifference()}</span> Years missing
				</div>
			</div>
			<div class="col-xs-6 col-md-3 op-col">
				<div class="op-recurring-amount"> 
					<span class="number">{$domain->recurringamount}</span> Recurring amount
				</div>
			</div>
		</div>

		<hr>
		
		<form method="post" action="{{get_current_url action=1}}&action=createInvoicePost">
			<div class="form-group">
				<label for="description">Invoice description</label>
				<textarea name="description" class="form-control">Correction for uninvoiced renewal of {$domain->domain}</textarea>
			</div>
			<div class="form-group">
				<label for="price">Amount</label>
				<input type="text" name="amount" class="form-control" value="{round($domain->recurringamount * $domain->getInvoiceDifference(), 2)}">
			</div>
			<button type="submit" class="btn btn-default">Submit</button>
		</form>
	</div>

	
</div>
