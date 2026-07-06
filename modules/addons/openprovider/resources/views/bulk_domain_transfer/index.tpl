{include file='../header.tpl'}

<div class="op-addon panel panel-default">
    <div class="panel-heading">
        <strong>Transfer your domain names to Openprovider</strong>
    </div>

    <div class="panel-body">

        <form method="post" action="{get_route route='bulkDomainTransfers'}">
            {generate_csrf}
            <div style="
                background: #fff8e1;
                border: 1px solid #ffe082;
                border-left: 4px solid #f9a825;
                border-radius: 4px;
                padding: 14px 18px;
                margin-bottom: 18px;
                font-size: 13px;
                color: #5d4037;
                line-height: 1.6;
            ">
                <div style="font-weight: 600; margin-bottom: 8px; font-size: 13.5px;">
                    &#9432;&nbsp; Before you submit — please note the following:
                </div>
                <ul style="margin: 0; padding-left: 20px;">
                    <li style="margin-bottom: 6px;">
                        <strong>Auto-renew:</strong> All transferred domains will have auto-renew turned <strong>ON</strong> after the transfer.
                        Only submit domains that you are comfortable with being automatically renewed.
                    </li>
                    <li>
                        <strong>ID Protection:</strong> ID protection will be <strong>OFF</strong> for all transferred domains after the transfer.
                    </li>
                </ul>
                <div style="margin-top: 10px; font-size: 12px; color: #8d6e63;">
                    To change auto-renew or ID protection for specific domains after the transfer, use the WHMCS admin area for domains or the Openprovider Reseller Control Panel.
                    Per-domain and per-batch control during bulk transfer will be available in a future release.
                </div>
            </div>

            {if isset($bulkReference) && $bulkReference}
                <div class="alert alert-success text-center" role="alert"
                    style="margin: 0 auto 15px; max-width: 600px;">
                    <strong>Bulk transfer submitted successfully. Reference:</strong> {$bulkReference|escape:'html'}
                </div>
            {/if}

            {if isset($submissionError) && $submissionError}
                <div class="alert alert-danger" role="alert" style="margin-bottom: 15px;">
                    <strong>Bulk transfer submission failed.</strong>
                    <div style="margin-top: 5px;">{$submissionError|escape:'html'}</div>
                </div>
            {/if}

            {if isset($validationErrors) && $validationErrors}
                <div class="alert alert-danger" role="alert" style="margin-bottom: 15px;">
                    <ul style="margin-bottom: 0; padding-left: 20px;">
                        {foreach from=$validationErrors item=validationError}
                            <li>{$validationError|escape:'html'}</li>
                        {/foreach}
                    </ul>
                </div>
            {/if}

            <!-- Import CSV -->
            <div style="display:flex; justify-content: flex-end; align-items:center; gap:10px; margin-bottom:10px;">

                <a href="{$sampleCsvUrl}" download style="font-size:11px; color:#666;">
                    Sample CSV
                </a>

                <input type="file" id="domains_csv" accept=".csv" style="display:none;">
                
                <button type="button" id="import-csv-btn" class="btn btn-default">
                    Import CSV
                </button>

            </div>

            <div id="file-name" style="margin-bottom:10px; font-size:12px; color:#666;"></div>

            <div class="form-group">
                <textarea
                    name="domains"
                    id="domains"
                    class="form-control"
                    rows="10"
                    placeholder="example.com&#10;example.net"
                >{if !isset($bulkReference) || !$bulkReference}{$domains|default:''|escape:'html'}{/if}</textarea>
            </div>

            <div style="margin-top:10px; color:#666;">
                List one domain per line.
            </div>

            <div style="margin-top:15px; text-align:right;">
                <button type="submit" class="btn btn-primary">
                    Submit
                </button>
            </div>

        </form>
    </div>
</div>

<script>
const importBtn = document.getElementById('import-csv-btn');
const fileInput = document.getElementById('domains_csv');
const textarea = document.getElementById('domains');
const fileNameDiv = document.getElementById('file-name');

importBtn.addEventListener('click', function () {
    fileInput.click();
});

fileInput.addEventListener('change', function () {
    if (!fileInput.files.length) {
        return;
    }

    const file = fileInput.files[0];
    fileNameDiv.textContent = "Selected file: " + file.name;

    const reader = new FileReader();

    reader.onload = function (e) {
        const content = e.target.result;
        const lines = content.split(/\r?\n/);
        const domains = [];

        lines.forEach(function (line, index) {
            const trimmed = line.trim();

            if (!trimmed) {
                return;
            }

            if (index === 0 && trimmed.toLowerCase().includes('domain')) {
                return;
            }

            const firstColumn = trimmed.split(',')[0].trim();

            if (firstColumn) {
                domains.push(firstColumn);
            }
        });

        textarea.value = domains.join('\n');
    };

    reader.readAsText(file);
});

</script>
