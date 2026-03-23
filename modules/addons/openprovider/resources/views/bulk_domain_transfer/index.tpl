{include file='../header.tpl'}

<div class="op-addon panel panel-default">
    <div class="panel-heading">
        <strong>Bulk Domain Transfers</strong>
    </div>

    <div class="panel-body">
        {if isset($notification) && is_array($notification)}
            <div class="alert alert-{$notification.type}" role="alert">
                {$notification.message}
            </div>
        {/if}

        <form method="post" action="{get_route route='bulkDomainTransfers'}" enctype="multipart/form-data">
            <div class="form-group">
                <label for="domains">Domains (one per line)</label>
                <textarea
                    name="domains"
                    id="domains"
                    class="form-control"
                    rows="10"
                    placeholder="example.com&#10;example.net&#10;example.org"
                >{$domains|default:''}</textarea>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="domains_csv">Upload CSV</label>
                <input
                    type="file"
                    name="domains_csv"
                    id="domains_csv"
                    class="form-control"
                    accept=".csv"
                >
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <button type="button" id="load-csv-btn" class="btn btn-default">
                    Load CSV
                </button>

                <button type="submit" class="btn btn-primary">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('load-csv-btn').addEventListener('click', function () {
    const fileInput = document.getElementById('domains_csv');
    const textarea = document.getElementById('domains');

    if (!fileInput.files.length) {
        alert('Please select a CSV file first.');
        return;
    }

    const file = fileInput.files[0];
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

            if (index === 0 && trimmed.toLowerCase() === 'domain') {
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