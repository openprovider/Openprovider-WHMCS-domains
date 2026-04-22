{include file='../header.tpl'}

<div class="op-addon bulk-transfer-page">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 8px; flex-wrap: wrap;">
        <div>
            <h2 style="margin-bottom: 6px;">Batch Detail Page</h2>
        </div>

        <div>
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
        </div>
    </div>

    <div style="margin-bottom: 26px; font-size: 16px; color: #475467;">
        <strong>Bulk reference:</strong> {$batch.reference|escape:'html'}
        <span style="display: inline-block; width: 18px;"></span>
        <strong>Submitted at:</strong> {$batch.submittedAt|escape:'html'}
        <span style="display: inline-block; width: 18px;"></span>
        <strong>Last updated:</strong> {$batch.lastUpdated|escape:'html'}
    </div>

    <div class="row" style="margin-bottom: 26px;">
        <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
            <div style="border: 1px solid #d0d5dd; border-radius: 14px; padding: 18px; background: #fff;">
                <div style="font-size: 15px; color: #667085; margin-bottom: 8px;">Total domains</div>
                <div style="font-size: 40px; font-weight: 700; color: #101828;">{$batch.totalDomains|escape:'html'}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
            <div style="border: 1px solid #d0d5dd; border-radius: 14px; padding: 18px; background: #fff;">
                <div style="font-size: 15px; color: #667085; margin-bottom: 8px;">Processed</div>
                <div style="font-size: 40px; font-weight: 700; color: #101828;">{$batch.processed|escape:'html'}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
            <div style="border: 1px solid #d0d5dd; border-radius: 14px; padding: 18px; background: #fff;">
                <div style="font-size: 15px; color: #667085; margin-bottom: 8px;">Successful</div>
                <div style="font-size: 40px; font-weight: 700; color: #101828;">{$batch.successful|escape:'html'}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
            <div style="border: 1px solid #d0d5dd; border-radius: 14px; padding: 18px; background: #fff;">
                <div style="font-size: 15px; color: #667085; margin-bottom: 8px;">Failed</div>
                <div style="font-size: 40px; font-weight: 700; color: #101828;">{$batch.failed|escape:'html'}</div>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #667085;">
            <span>Batch progress</span>
            <span>{$batch.progressPercentage|escape:'html'}% processed</span>
        </div>

        <div style="width: 100%; height: 12px; background: #e5e7eb; border-radius: 999px; overflow: hidden;">
            <div style="width: {$batch.progressPercentage|escape:'html'}%; height: 12px; background: #14213d;"></div>
        </div>
    </div>

    {if $batch.failed > 0}
        <div style="margin-bottom: 22px; padding: 16px 18px; border: 1px solid #f7b27a; border-radius: 14px; background: #fff7ed; color: #c2410c;">
            Some domains need attention. Review failed items below to understand the reason and next step.
        </div>
    {/if}

    <div class="table-responsive">
        <table class="table" style="background: #fff;">
            <thead>
                <tr style="color: #667085; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 14px 16px;">Domain</th>
                    <th style="padding: 14px 16px;">Status</th>
                    <th style="padding: 14px 16px;">Message</th>
                    <th style="padding: 14px 16px;">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                {foreach $domains as $domain}
                    <tr>
                        <td style="padding: 18px 16px; vertical-align: middle;">
                            <strong style="font-size: 16px; color: #101828;">
                                {$domain.domain|escape:'html'}
                            </strong>
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                        {if $domain.status eq 'success'}
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #86d4a8; border-radius: 999px; color: #067647; background: #ecfdf3; font-weight: 600;">
                                Completed
                            </span>

                        {elseif $domain.status eq 'failed'}
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #f3b3b3; border-radius: 999px; color: #b42318; background: #fef3f2; font-weight: 600;">
                                Failed
                            </span>

                        {elseif $domain.status eq 'validation_failed'}
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #f7b27a; border-radius: 999px; color: #d96d12; background: #fff7ed; font-weight: 600;">
                                Validation failed
                            </span>

                        {elseif $domain.status eq 'queued' || $domain.status eq 'ready_for_transfer'}
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #d0d5dd; border-radius: 999px; color: #344054; background: #f9fafb; font-weight: 600;">
                                {if $domain.status eq 'ready_for_transfer'}
                                    Ready for transfer
                                {else}
                                    Queued
                                {/if}
                            </span>

                        {elseif
                            $domain.status eq 'validating' ||
                            $domain.status eq 'unlocking' ||
                            $domain.status eq 'getting_epp' ||
                            $domain.status eq 'creating_handle' ||
                            $domain.status eq 'transferring' ||
                            $domain.status eq 'transfer_requested' ||
                            $domain.status eq 'checking_transfer_status'
                        }
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #b7cdfc; border-radius: 999px; color: #2156d9; background: #eef4ff; font-weight: 600;">
                                {if $domain.status eq 'validating'}
                                    Validating
                                {elseif $domain.status eq 'unlocking'}
                                    Unlocking
                                {elseif $domain.status eq 'getting_epp'}
                                    Getting EPP
                                {elseif $domain.status eq 'creating_handle'}
                                    Creating handle
                                {elseif $domain.status eq 'transferring'}
                                    Transferring
                                {elseif $domain.status eq 'transfer_requested'}
                                    Transfer requested
                                {elseif $domain.status eq 'checking_transfer_status'}
                                    Checking transfer status
                                {/if}
                            </span>

                        {else}
                            <span style="display: inline-block; padding: 6px 14px; border: 1px solid #d0d5dd; border-radius: 999px; color: #344054; background: #f9fafb; font-weight: 600;">
                                {$domain.status|escape:'html'}
                            </span>
                        {/if}
                    </td>

                        <td style="padding: 18px 16px; vertical-align: middle; color: #344054;">
                            {$domain.message|escape:'html'}
                        </td>

                        <td style="padding: 18px 16px; vertical-align: middle;">
                            {$domain.lastUpdated|escape:'html'}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {if isset($domainPagination) && $domainPagination.totalPages > 1}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; gap:16px; flex-wrap:wrap;">
            <div style="color:#667085; font-size:14px;">
                Showing
                {($domainPagination.currentPage - 1) * $domainPagination.perPage + 1}
                -
                {min($domainPagination.currentPage * $domainPagination.perPage, $domainPagination.totalItems)}
                of {$domainPagination.totalItems}
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                {if $domainPagination.hasPreviousPage}
                    <a href="{get_route route='bulkDomainTransfersBatchDetails' batchReference=$batch.reference domainPage=$domainPagination.previousPage}"
                    class="btn btn-default">
                        Previous
                    </a>
                {/if}

                {section name=page start=1 loop=$domainPagination.totalPages+1}
                    {assign var=pageNumber value=$smarty.section.page.index}
                    {if $pageNumber == $domainPagination.currentPage}
                        <span class="btn btn-default" style="background:#101828; color:#fff; border-color:#101828;">
                            {$pageNumber}
                        </span>
                    {else}
                        <a href="{get_route route='bulkDomainTransfersBatchDetails' batchReference=$batch.reference domainPage=$pageNumber}"
                        class="btn btn-default">
                            {$pageNumber}
                        </a>
                    {/if}
                {/section}

                {if $domainPagination.hasNextPage}
                    <a href="{get_route route='bulkDomainTransfersBatchDetails' batchReference=$batch.reference domainPage=$domainPagination.nextPage}"
                    class="btn btn-default">
                        Next
                    </a>
                {/if}
            </div>
        </div>
    {/if}
</div>