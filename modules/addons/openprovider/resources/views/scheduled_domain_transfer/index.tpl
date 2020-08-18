{include file='../header.tpl'}

<div class="op-addon panel panel-default">
    <!-- Default panel contents -->
    <div class="panel-heading"><strong>{$LANG.scheduled_domain_transfers_title}</strong></div>
    <div class="panel-body">
        {if isset($notification) && is_array($notification)}
            <div class="alert alert-{$notification.type}" role="alert">
                {$LANG[{$notification.message}]}
            </div>
        {/if}


        {if count($scheduled_domain_transfers) == 0 }
            {$LANG.no_scheduled_domain_transfers}
        {else}
            <div class="pull-right" style="margin-bottom:10px;">

                {if isset($smarty.session.op_sch_domain_hide_act_domains) && $smarty.session.op_sch_domain_hide_act_domains == 'yes'}
                    <a class="btn btn-primary" href="{get_route route='toggleFilterScheduledDomainTransfers'}" role="button" >{$LANG.scheduled_domain_transfers_show_all_domains}</a>
                {else}
                    <a class="btn btn-primary" href="{get_route route='toggleFilterScheduledDomainTransfers'}" role="button" >{$LANG.scheduled_domain_transfers_show_scheduled_only_domains}</a>
                {/if}

                <a class="btn btn-primary" href="{get_route route='cleanScheduledDomainTransfers'}" role="button" >{$LANG.clean_completed_scheduled_domain_transfer}</a>
            </div>
            <div>
                <table class="datatable" width="100%" style="margin-bottom:0;">
                    <tbody>
                    <tr>
                        <th>Keyword</th>
                        <th>Status</th>
                        <th>Domain details</th>
                        <th>Created at</th>
                        <th>Updated at</th>
                    </tr>
                    {foreach from=$scheduled_domain_transfers item=domain}
                        <tr>
                            <td>{$domain.domain}</td>
                            <td>{$domain.status}</td>
                            <td>{if isset($domain.tbldomain.id)}<a href="clientsdomains.php?id={$domain.tbldomain.id}">Domain details</a>{/if}</td>
                            <td>{$domain.created_at}</td>
                            <td>{$domain.updated_at}</td>
                        </tr>
                    {/foreach}

                    </tbody>
                </table>
                <br>
                {$pagination}
            </div>
        {/if}
    </div>
</div>