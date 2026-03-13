<link rel="stylesheet" href="{$cssModuleUrl}">
<section class="js-dnssec-module">
    <div class="row d-flex align-items-center">

        <h2 class="">
            {$ADDONLANG.openprovider.dnssec.tablename}
        </h2>

        {if ($isDnssecEnabled)}
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{$ADDONLANG.openprovider.dnssec.deactivatednssecbutton}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{$ADDONLANG.openprovider.dnssec.activatednssecbutton}"
                   data-value="1"/>
            <div class="dnssec-spinner ml-auto hidden"></div>
        {else}
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{$ADDONLANG.openprovider.dnssec.deactivatednssecbutton}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{$ADDONLANG.openprovider.dnssec.activatednssecbutton}"
                   data-value="1"/>
            <div class="dnssec-spinner ml-auto hidden"></div>
        {/if}

    </div>

    {if ($isDnssecEnabled)}
        <div class="dnssec-alert-on-disabled alert alert-warning hidden">
            {$ADDONLANG.openprovider.dnssec.alertdnssecnotactivated}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning">
            {$ADDONLANG.openprovider.dnssec.alertdnssecactivated}
        </div>
    {else}        
        <div class="dnssec-alert-on-disabled alert alert-warning">
            {$ADDONLANG.openprovider.dnssec.alertdnssecnotactivated}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning hidden">
            {$ADDONLANG.openprovider.dnssec.alertdnssecactivated}
        </div>
        <div class="dnssec-alert-on-enabled-new alert alert-warning hidden">
            {$ADDONLANG.openprovider.dnssec.alertdnsssecnotactivatedyet}
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
                <th>
                    {$ADDONLANG.openprovider.dnssec.tableheaderflags}
                </th>
                <th>
                    {$ADDONLANG.openprovider.dnssec.tableheaderalgorithms}
                </th>
                <th>
                    {$ADDONLANG.openprovider.dnssec.tableheaderpublickeys}
                </th>
                <th>
                    {$ADDONLANG.openprovider.dnssec.tableheaderactions}
                </th>
            </tr>
        </thead>
        <tbody>
        {foreach $dnssecKeys as $dnssecKey}
        <tr>
            <td>{$dnssecKey['flags']}</td>
            <td>{$dnssecKey['alg']}</td>
            <td class="break-word">{$dnssecKey['pubKey']}</td>
            <td>
                <input type="button" name="deleteDnsSecRecord" class="btn btn-danger"
                       value="{if $ADDONLANG.openprovider.dnssec.tablebuttonactiondelete}{$ADDONLANG.openprovider.dnssec.tablebuttonactiondelete}{else}Delete{/if}"/>
            </td>
        <tr>
        {/foreach}
        </tbody>
    </table>

        {if ($isDnssecEnabled)}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom"
                   value="{$ADDONLANG.openprovider.dnssec.buttonadddnssecrecord}"/>
        {else}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom hidden"
                   value="{$ADDONLANG.openprovider.dnssec.buttonadddnssecrecord}"/>
        {/if}
</section>
<script>
    // define php variables to js module
    const domainId                  = {$domainId},
          apiUrlUpdateDnssecRecords = '{$apiUrlUpdateDnssecRecords}',
          apiUrlTurnOnOffDnssec     = '{$apiUrlTurnOnOffDnssec}',
          buttonDeleteName          = '{$ADDONLANG.openprovider.dnssec.tablebuttonactiondelete}',
          buttonSaveName            = '{$ADDONLANG.openprovider.dnssec.tablebuttonactionsave}';
</script>

<script src="{$jsModuleUrl}"></script>
