{include file='../header.tpl'}

<div class="op-addon bulk-transfer-page">
    <h2 style="margin-bottom: 12px;">Batch List Page</h2>

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
                        onclick="window.location.href='{get_route route='bulkDomainTransfersBatchDetails' batchReference=$batch.reference}'"
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
                            {if $batch.status eq 'processing'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #b7cdfc; border-radius: 999px; color: #2156d9; background: #eef4ff; font-weight: 600;">
                                    Processing
                                </span>
                            {elseif $batch.status eq 'completed_with_errors'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #f7b27a; border-radius: 999px; color: #d96d12; background: #fff7ed; font-weight: 600;">
                                    Completed with errors
                                </span>
                            {elseif $batch.status eq 'completed'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #86d4a8; border-radius: 999px; color: #067647; background: #ecfdf3; font-weight: 600;">
                                    Completed
                                </span>
                            {elseif $batch.status eq 'queued'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #d0d5dd; border-radius: 999px; color: #344054; background: #f9fafb; font-weight: 600;">
                                    Queued
                                </span>
                            {elseif $batch.status eq 'failed'}
                                <span style="display: inline-block; padding: 6px 14px; border: 1px solid #f3b3b3; border-radius: 999px; color: #b42318; background: #fef3f2; font-weight: 600;">
                                    Failed
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
    {if isset($batchPagination) && $batchPagination.totalPages > 1}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; gap:16px; flex-wrap:wrap;">
            <div style="color:#667085; font-size:14px;">
                Showing
                {($batchPagination.currentPage - 1) * $batchPagination.perPage + 1}
                -
                {min($batchPagination.currentPage * $batchPagination.perPage, $batchPagination.totalItems)}
                of {$batchPagination.totalItems}
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                {if $batchPagination.hasPreviousPage}
                    <a href="{get_route route='bulkDomainTransfersBatchList' page=$batchPagination.previousPage}"
                    class="btn btn-default">
                        Previous
                    </a>
                {/if}

                {section name=page start=1 loop=$batchPagination.totalPages+1}
                    {assign var=pageNumber value=$smarty.section.page.index}
                    {if $pageNumber == $batchPagination.currentPage}
                        <span class="btn btn-default" style="background:#101828; color:#fff; border-color:#101828;">
                            {$pageNumber}
                        </span>
                    {else}
                        <a href="{get_route route='bulkDomainTransfersBatchList' page=$pageNumber}"
                        class="btn btn-default">
                            {$pageNumber}
                        </a>
                    {/if}
                {/section}

                {if $batchPagination.hasNextPage}
                    <a href="{get_route route='bulkDomainTransfersBatchList' page=$batchPagination.nextPage}"
                    class="btn btn-default">
                        Next
                    </a>
                {/if}
            </div>
        </div>
    {/if}
</div>