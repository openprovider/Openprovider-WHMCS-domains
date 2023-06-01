<link rel="stylesheet" href="{$cssModuleUrl}">
<section class="js-dnssec-module">
    <div class="row d-flex align-items-center">

        <h2 class="">
            {if $LANG.dnssec.tablename}
                {$LANG.dnssec.tablename}
            {else}
                Manage DNSSEC Records
            {/if}
        </h2>

        {if ($isDnssecEnabled)}
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{if $LANG.dnssec.deactivatednssecbutton}{$LANG.dnssec.deactivatednssecbutton}{else}Deactivate DNSSEC{/if}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{if $LANG.dnssec.activatednssecbutton}{$LANG.dnssec.activatednssecbutton}{else}Activate DNSSEC{/if}"
                   data-value="1"/>
        {else}
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{if $LANG.dnssec.deactivatednssecbutton}{$LANG.dnssec.deactivatednssecbutton}{else}Deactivate DNSSEC{/if}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{if $LANG.dnssec.activatednssecbutton}{$LANG.dnssec.activatednssecbutton}{else}Activate DNSSEC{/if}"
                   data-value="1"/>
        {/if}

    </div>

    {if ($isDnssecEnabled)}
        <div class="dnssec-alert-on-disabled alert alert-warning hidden">
            {if $LANG.dnssec.alertdnssecnotactivated}
                {$LANG.dnssec.alertdnssecnotactivated}
            {else}
                DNSSEC is not active on this domain.
            {/if}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning">
            {if $LANG.dnssec.alertdnssecactivated}
                {$LANG.dnssec.alertdnssecactivated}
            {else}
                DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.
            {/if}
        </div>
    {else}
        <div class="dnssec-alert-on-disabled alert alert-warning">
            {if $LANG.dnssec.alertdnssecnotactivated}
                {$LANG.dnssec.alertdnssecnotactivated}
            {else}
                DNSSEC is not active on this domain.
            {/if}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning hidden">
            {if $LANG.dnssec.alertdnssecactivated}
                {$LANG.dnssec.alertdnssecactivated}
            {else}
                DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.
            {/if}
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
                    {if $LANG.dnssec.tableheaderflags}
                        {$LANG.dnssec.tableheaderflags}
                    {else}
                        Flags
                    {/if}
                </th>
                <th>
                    {if $LANG.dnssec.tableheaderalgorithms}
                        {$LANG.dnssec.tableheaderalgorithms}
                    {else}
                        Algorithm
                    {/if}
                </th>
                <th>
                    {if $LANG.dnssec.tableheaderpublickeys}
                        {$LANG.dnssec.tableheaderpublickeys}
                    {else}
                        Public key
                    {/if}
                </th>
                <th>
                    {if $LANG.dnssec.tableheaderactions}
                        {$LANG.dnssec.tableheaderactions}
                    {else}
                        Actions
                    {/if}
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
                       value="{if $LANG.dnssec.tablebuttonactiondelete}{$LANG.dnssec.tablebuttonactiondelete}{else}Delete{/if}"/>
            </td>
        <tr>
        {/foreach}
        </tbody>
    </table>

        {if ($isDnssecEnabled)}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom"
                   value="{if $LANG.dnssec.buttonadddnssecrecord}{$LANG.dnssec.buttonadddnssecrecord}{else}Add A New DNSSEC Record{/if}"/>
        {else}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom hidden"
                   value="{if $LANG.dnssec.buttonadddnssecrecord}{$LANG.dnssec.buttonadddnssecrecord}{else}Add A New DNSSEC Record{/if}"/>
        {/if}
</section>
<script>
    // define php variables to js module
    const domainId                  = {$domainId},
          apiUrlUpdateDnssecRecords = '{$apiUrlUpdateDnssecRecords}',
          apiUrlTurnOnOffDnssec     = '{$apiUrlTurnOnOffDnssec}',
          buttonDeleteName          = '{if $LANG.dnssec.tablebuttonactiondelete}{$LANG.dnssec.tablebuttonactiondelete}{else}Delete{/if}',
          buttonSaveName            = '{if $LANG.dnssec.tablebuttonactionsave}{$LANG.dnssec.tablebuttonactionsave}{else}Save{/if}';
</script>

<script src="{$jsModuleUrl}"></script>