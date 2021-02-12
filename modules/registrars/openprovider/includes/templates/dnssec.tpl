<link rel="stylesheet" href="{$cssModuleUrl}">
<section class="js-dnssec-module">
    <div class="row d-flex align-items-center">

        <h2 class="">{$lang['table_name']}</h2>

        {if ($isDnssecEnabled)}
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec" value="{$lang['deactivate_dnssec_button']}" data-value="0"/>
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec" value="{$lang['activate_dnssec_button']}" data-value="1"/>
        {else}
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec" value="{$lang['deactivate_dnssec_button']}" data-value="0"/>
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec" value="{$lang['activate_dnssec_button']}" data-value="1"/>
        {/if}

    </div>

    {if ($isDnssecEnabled)}
        <div class="dnssec-alert-on-disabled alert alert-warning hidden">
            {$lang['alert_dnssec_not_activated']}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning">
            {$lang['alert_dnssec_activated']}
        </div>
    {else}
        <div class="dnssec-alert-on-disabled alert alert-warning">
            {$lang['alert_dnssec_not_activated']}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning hidden">
            {$lang['alert_dnssec_activated']}
        </div>
    {/if}

    <div class="dnssec-alert-error-message alert alert-danger hidden">
    </div>


    {if ($isDnssecEnabled)}
        <table class="dnssec-records-table table table-bordered">
    {else}
        <table class="dnssec-records-table table table-bordered hidden">
    {/if}
        <thead>
            <tr>
                <th>{$lang['table_header_flags']}</th>
                <th>{$lang['table_header_algorithms']}</th>
                <th>{$lang['table_header_public_keys']}</th>
                <th>{$lang['table_header_actions']}</th>
            </tr>
        </thead>
        <tbody>
        {foreach $dnssecKeys as $dnssecKey}
            </tr>
                <td>{$dnssecKey['flags']}</td>
                <td>{$dnssecKey['alg']}</td>
                <td class="break-word">{$dnssecKey['pubKey']}</td>
                <td>
                    <input type="button" name="deleteDnsSecRecord" class="btn btn-danger" value="{$lang['table_button_action_delete']}" />
                </td>
            <tr>
        {/foreach}
        </tbody>
    </table>

    {if ($isDnssecEnabled)}
        <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom" value="{$lang['button_add_dnssec_record']}" />
    {else}
        <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom hidden" value="{$lang['button_add_dnssec_record']}" />
    {/if}
</section>
<script>
    // define php variables to js module
    const domainId                  = {$domainId},
          apiUrlUpdateDnssecRecords = '{$apiUrlUpdateDnssecRecords}',
          apiUrlTurnOnOffDnssec     = '{$apiUrlTurnOnOffDnssec}',
          buttonDeleteName          = '{$lang['table_button_action_delete']}',
          buttonSaveName            = '{$lang['table_button_action_save']}';
</script>

<script src="{$jsModuleUrl}"></script>