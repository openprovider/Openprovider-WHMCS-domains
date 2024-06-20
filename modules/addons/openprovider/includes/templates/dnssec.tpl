<link rel="stylesheet" href="{$cssModuleUrl}">
<section class="js-dnssec-module">
    <div class="row d-flex align-items-center">

        <h2 class="">
            {if $_lang.dnssec.tablename}
                {$_lang.dnssec.tablename}
            {else}
                Manage DNSSEC Records
            {/if}
        </h2>

        {if ($isDnssecEnabled)}
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{if $_lang.dnssec.deactivatednssecbutton}{$_lang.dnssec.deactivatednssecbutton}{else}Deactivate DNSSEC{/if}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{if $_lang.dnssec.activatednssecbutton}{$_lang.dnssec.activatednssecbutton}{else}Activate DNSSEC{/if}"
                   data-value="1"/>
        {else}
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec"
                   value="{if $_lang.dnssec.deactivatednssecbutton}{$_lang.dnssec.deactivatednssecbutton}{else}Deactivate DNSSEC{/if}"
                   data-value="0"/>
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec"
                   value="{if $_lang.dnssec.activatednssecbutton}{$_lang.dnssec.activatednssecbutton}{else}Activate DNSSEC{/if}"
                   data-value="1"/>
        {/if}

    </div>

    {if ($isDnssecEnabled)}
        <div class="dnssec-alert-on-disabled alert alert-warning hidden">
            {if $_lang.dnssec.alertdnssecnotactivated}
                {$_lang.dnssec.alertdnssecnotactivated}
            {else}
                DNSSEC is not active on this domain.
            {/if}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning">
            {if $_lang.dnssec.alertdnssecactivated}
                {$_lang.dnssec.alertdnssecactivated}
            {else}
                DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.            
            {/if}
        </div>
    {else}        
        <div class="dnssec-alert-on-disabled alert alert-warning">
            {if $_lang.dnssec.alertdnssecnotactivated}
                {$_lang.dnssec.alertdnssecnotactivated}
            {else}
                DNSSEC is not active on this domain.
            {/if}
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning hidden">
            {if $_lang.dnssec.alertdnssecactivated}
                {$_lang.dnssec.alertdnssecactivated}
            {else}
                DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.                
            {/if}
        </div>
        <div class="dnssec-alert-on-enabled-new alert alert-warning hidden">
            DNSSEC has not been activated yet. Please add a new DNSSEC record and save to activate DNSSEC.
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
                    {if $_lang.dnssec.tableheaderflags}
                        {$_lang.dnssec.tableheaderflags}
                    {else}
                        Flags
                    {/if}
                </th>
                <th>
                    {if $_lang.dnssec.tableheaderalgorithms}
                        {$_lang.dnssec.tableheaderalgorithms}
                    {else}
                        Algorithm
                    {/if}
                </th>
                <th>
                    {if $_lang.dnssec.tableheaderpublickeys}
                        {$_lang.dnssec.tableheaderpublickeys}
                    {else}
                        Public key
                    {/if}
                </th>
                <th>
                    {if $_lang.dnssec.tableheaderactions}
                        {$_lang.dnssec.tableheaderactions}
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
                       value="{if $_lang.dnssec.tablebuttonactiondelete}{$_lang.dnssec.tablebuttonactiondelete}{else}Delete{/if}"/>
            </td>
        <tr>
        {/foreach}
        </tbody>
    </table>

        {if ($isDnssecEnabled)}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom"
                   value="{if $_lang.dnssec.buttonadddnssecrecord}{$_lang.dnssec.buttonadddnssecrecord}{else}Add A New DNSSEC Record{/if}"/>
        {else}
            <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom hidden"
                   value="{if $_lang.dnssec.buttonadddnssecrecord}{$_lang.dnssec.buttonadddnssecrecord}{else}Add A New DNSSEC Record{/if}"/>
        {/if}
</section>
<script>
    // define php variables to js module
    const domainId                  = {$domainId},
          apiUrlUpdateDnssecRecords = '{$apiUrlUpdateDnssecRecords}',
          apiUrlTurnOnOffDnssec     = '{$apiUrlTurnOnOffDnssec}',
          buttonDeleteName          = '{if $_lang.dnssec.tablebuttonactiondelete}{$_lang.dnssec.tablebuttonactiondelete}{else}Delete{/if}',
          buttonSaveName            = '{if $_lang.dnssec.tablebuttonactionsave}{$_lang.dnssec.tablebuttonactionsave}{else}Save{/if}';
</script>

<script src="{$jsModuleUrl}"></script>
