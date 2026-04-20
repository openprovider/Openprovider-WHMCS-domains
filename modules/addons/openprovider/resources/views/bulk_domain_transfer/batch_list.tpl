{include file='../header.tpl'}

<div class="op-addon bulk-transfer-page">
    <h2 style="margin-bottom: 6px;">Batch List Page</h2>

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <input
                type="text"
                class="form-control"
                placeholder="Search by bulk reference"
                style="width: 260px;"
                disabled
            />
            <select class="form-control" style="width: 140px;" disabled>
                <option>All statuses</option>
            </select>
        </div>

        <button type="button" class="btn btn-default" disabled>
            Newest first
        </button>
    </div>

    <div class="table-responsive">
        <table class="table" style="background: #fff;">
            <thead>
                <tr style="color: #667085; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 14px 16px;">Bulk Reference</th>
                    <th style="padding: 14px 16px;">Submitted At</th>
                    <th style="padding: 14px 16px;">Status</th>
                    <th style="padding: 14px 16px;">Domains</th>
                    <th style="padding: 14px 16px;">Success</th>
                    <th style="padding: 14px 16px;">Failed</th>
                    <th style="padding: 14px 16px;">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                {foreach $batches as $batch}
                    <tr
                        onclick="window.location.href='addonmodules.php?module=openprovider&action=bulkDomainTransfersBatchDetails&batchReference={$batch.reference|escape:'url'}'"
                        style="cursor: pointer;"
                    >
                        <td style="padding: 18px 16px; vertical-align: middle;">
                            <strong style="display: block; font-size: 16px; color: #101828;">
                                {$batch.reference|escape:'html'}
                            </strong>
                            <span style="font-size: 13px; color: #667085;">
                                Click row to open batch details
                            </span>
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$batch.submittedAt|escape:'html'}
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {if $batch.status eq 'Processing'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #b7cdfc; border-radius: 999px; color: #2156d9; background: #eef4ff; font-weight: 600;">
                                    {$batch.status|escape:'html'}
                                </span>
                            {elseif $batch.status eq 'Completed with errors'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #f7b27a; border-radius: 999px; color: #d96d12; background: #fff7ed; font-weight: 600;">
                                    {$batch.status|escape:'html'}
                                </span>
                            {elseif $batch.status eq 'Completed'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #86d4a8; border-radius: 999px; color: #067647; background: #ecfdf3; font-weight: 600;">
                                    {$batch.status|escape:'html'}
                                </span>
                            {elseif $batch.status eq 'Queued'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #d0d5dd; border-radius: 999px; color: #344054; background: #f9fafb; font-weight: 600;">
                                    {$batch.status|escape:'html'}
                                </span>
                            {else}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #d0d5dd; border-radius: 999px; color: #344054; background: #f9fafb; font-weight: 600;">
                                    {$batch.status|escape:'html'}
                                </span>
                            {/if}
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$batch.processed|escape:'html'} / {$batch.total|escape:'html'} processed
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$batch.success|escape:'html'}
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$batch.failed|escape:'html'}
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$batch.lastUpdated|escape:'html'}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>