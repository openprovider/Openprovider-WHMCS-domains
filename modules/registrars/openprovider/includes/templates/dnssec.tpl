<link rel="stylesheet" href="{$cssModuleUrl}">
<section class="js-dnssec-module">
    <div class="row d-flex align-items-center">

        <h2 class="">Manage DNSSEC Records</h2>

        {if ($isDnssecEnabled)}
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec" value="Deactivate DNSSEC" data-value="0"/>
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec" value="Activate DNSSEC" data-value="1"/>
        {else}
            <input type="button" class="ml-auto btn-primary hidden" name="turnOnOffDnssec" value="Deactivate DNSSEC" data-value="0"/>
            <input type="button" class="ml-auto btn-primary" name="turnOnOffDnssec" value="Activate DNSSEC" data-value="1"/>
        {/if}

    </div>

    {if ($isDnssecEnabled)}
        <div class="dnssec-alert-on-disabled alert alert-warning hidden">
            DNSSEC is not active on this domain.
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning">
            DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.
        </div>
    {else}
        <div class="dnssec-alert-on-disabled alert alert-warning">
            DNSSEC is not active on this domain.
        </div>
        <div class="dnssec-alert-on-enabled alert alert-warning hidden">
            DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.
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
                <th>Flags</th>
                <th>Algorithm</th>
                <th>Public key</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        {foreach $dnssecKeys as $dnssecKey}
            </tr>
                <td>{$dnssecKey['flags']}</td>
                <td>{$dnssecKey['alg']}</td>
                <td>{$dnssecKey['pubKey']}</td>
                <td>
                    <input type="button" name="deleteDnsSecRecord" class="btn btn-danger" value="Delete" />
                </td>
            <tr>
        {/foreach}
        </tbody>
    </table>

    {if ($isDnssecEnabled)}
        <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom" value="Add A New DNSSEC Record" />
    {else}
        <input type="button" name="addNewDnsSecRecord" class="btn btn-success margin-bottom hidden" value="Add A New DNSSEC Record" />
    {/if}
</section>
<script>
    // define php variables to js module
    const domainId                  = {$domainId},
          apiUrlUpdateDnssecRecords = '{$apiUrlUpdateDnssecRecords}',
          apiUrlTurnOnOffDnssec     = '{$apiUrlTurnOnOffDnssec}';
</script>

<script src="{$jsModuleUrl}"></script>