{include file='../header.tpl'}

<div class="op-addon panel panel-default">
    <div class="panel-heading">
        <strong>Transfer your domain names to Openprovider</strong>
    </div>

    <div class="panel-body">

        <form method="post" action="{get_route route='bulkDomainTransfers'}" enctype="multipart/form-data">

            <!-- Import CSV -->
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:10px;">
                <div></div>

                <div>
                    <input type="file" id="domains_csv" accept=".csv" style="display:none;">
                    <button type="button" id="import-csv-btn" class="btn btn-default">
                        Import CSV
                    </button>
                </div>
            </div>

            <!-- File name display -->
            <div id="file-name" style="margin-bottom:10px; font-size: 12px; color: #666;"></div>

            <!-- Textarea -->
            <div class="form-group">
                <textarea
                    name="domains"
                    id="domains"
                    class="form-control"
                    rows="10"
                    placeholder="example.com authcode1&#10;example.net authcode2"
                >{$domains|default:''}</textarea>
            </div>

            <!-- Info text -->
            <div style="margin-top:10px; color:#666;">
                List one domain per line.
            </div>

            <!-- Submit -->
            <div style="margin-top:15px; text-align:right;">
                <button type="submit" class="btn btn-primary">
                    Continue
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

importBtn.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', function () {
    if (!fileInput.files.length) return;

    const file = fileInput.files[0];

    // Show file name
    fileNameDiv.textContent = "Selected file: " + file.name;

    const reader = new FileReader();

    reader.onload = function (e) {
        const content = e.target.result;
        const lines = content.split(/\r?\n/);
        const domains = [];

        lines.forEach(function (line, index) {
            const trimmed = line.trim();
            if (!trimmed) return;

            // Skip header
            if (index === 0 && trimmed.toLowerCase().includes('domain')) return;

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