{include file='../header.tpl'}

<div class="op-addon panel panel-default">
    <!-- Default panel contents -->
    <div class="panel-heading"><strong>{$LANG.bulk_import_title}</strong></div>
    <div class="panel-body">
        
        <h1>Openprovider Bulk Import</h1>

        <p>Import domains from Openprovider Reseller Controller Panel to WHMCS</p>

        <!-- Large input field for multiple inputs (new line separated) -->
        <div class="form-group">
            <label for="domainInput">Domain names (one for each line):</label>
            <textarea id="domainInput" name="domainInput" class="form-control" rows="10" placeholder="Enter each domain on a new line"></textarea>
            <span id="domainError" style="color: red; display: none;">Please enter at least one domain.</span>
        </div>

        <!-- Dropdown with static options ("None", "openprovider") -->
        <div class="form-group">
            <label for="providerSelect">Registrar:</label>
            <select id="providerSelect" name="providerSelect" class="form-control">
                <option value="" selected>None</option>
                <option value="openprovider">Openprovider</option>
            </select>
        </div>

        <!-- Dropdown for selecting clients -->
        <div class="form-group">
            <label for="clientSelect">Select Client*:</label>
            <select id="clientSelect" name="clientSelect" class="form-control">
                {foreach from=$client_list item=variable}
                    <option value="{$variable.value}">{$variable.name}</option>
                {/foreach}
            </select>
            <span id="clientError" style="color: red; display: none;">Please select a client.</span>
        </div>

        <!-- Dropdown for selecting payment method -->
        <div class="form-group">
            <label for="paymentMethodSelect">Select Payment Method*:</label>
            <select id="paymentMethodSelect" name="paymentMethodSelect" class="form-control">
                {foreach from=$payment_methods item=variable}
                    <option value="{$variable.value}">{$variable.name}</option>
                {/foreach}
            </select>
            <span id="paymentMethodError" style="color: red; display: none;">Please select a payment method.</span>
        </div>

        <!-- Submit Button -->
        <!-- Submit Button -->
        <div class="form-group">
            <input id="submitButton" class="btn btn-primary" type="button" value="{$LANG.bulk_import_button}">
        </div>
        <p>(if you select "Openprovider" as the registrar, Domain sync will be executed after importing)</p>

        <!-- Success and Error Messages -->
        <div id="successMessage" style="display:none; text-align:left; color: green;"></div>
        <div id="existingDomain" style="display:none; text-align:left; color: orange;"></div>
        <div id="invalidDomain" style="display:none; text-align:left; color: red;"></div>
        <div id="syncFailedDomain" style="display:none; text-align:left; color: red;"></div>
        <div id="errorMessage" style="display:none; text-align:left; color: red;"></div>
        
    </div>
</div>

<!-- Embed PHP variable in JS -->
<script type="text/javascript">
    var apiUrlImportDomains = "{$apiUrlImportDomains}";  // Embed PHP variable for API URL

    $(document).ready(function() {
        // On button click
        $('#submitButton').click(function() {
            // Clear previous errors
            $('#domainError').hide();
            $('#clientError').hide();
            $('#paymentMethodError').hide();
            $('#successMessage').hide();
            $('#errorMessage').hide();

            // Get the values from the fields
            var domainInput = $('#domainInput').val().trim();
            var clientSelect = $('#clientSelect').val();
            var paymentMethodSelect = $('#paymentMethodSelect').val();
            var providerSelect = $('#providerSelect').val();

            var isValid = true;

            // Validate inputs
            if (domainInput === '') {
                $('#domainError').show();
                isValid = false;
            }
            if (clientSelect === '') {
                $('#clientError').show();
                isValid = false;
            }
            if (paymentMethodSelect === '') {
                $('#paymentMethodError').show();
                isValid = false;
            }

            // If the form is valid, proceed with the AJAX call
            if (isValid) {
                // Disable the button and change the text to "Processing..."
                $('#submitButton').attr('disabled', 'disabled').val('Processing...');

                // Perform AJAX call
                $.ajax({
                    url: apiUrlImportDomains,  // Use the PHP variable in the AJAX URL
                    type: 'GET',
                    data: {
                        domainList: domainInput,
                        clientId: clientSelect,
                        paymentMethod: paymentMethodSelect,
                        registrar: providerSelect
                    },
                    success: function(response) {
                        var jsonResponse = JSON.parse(response); 
                        // Check response for success or error
                        if (jsonResponse.success) {
                            // Hide the submit button and show the success message
                            $('#submitButton').hide();
                            $('#successMessage').text(jsonResponse.message).show();
                            $('#existingDomain').text(jsonResponse.existingDomains).show();
                            $('#invalidDomain').text(jsonResponse.invalidDomains).show();
                            $('#syncFailedDomain').text(jsonResponse.syncedFailedDomains).show();
                        } else {
                            // Show the error message and keep the submit button
                            $('#errorMessage').text(jsonResponse.message).show();
                            $('#submitButton').removeAttr('disabled').val('{$LANG.bulk_import_button}');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle server errors and show the error message
                        $('#errorMessage').text("Error: " + (xhr.responseJSON ? xhr.responseJSON.message : "An error occurred")).show();
                        $('#submitButton').removeAttr('disabled').val('{$LANG.bulk_import_button}');
                    }
                });
            }
        });
    });
</script>
