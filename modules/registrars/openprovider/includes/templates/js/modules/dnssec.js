/**
 * Vars domainId, apiUrlUpdateDnssecRecords, apiUrlTurnOnOffDnssec takes from template
 */

$(document).on('ready', function () {
    const addNewRecordButton = $('input[name=addNewDnsSecRecord]');
    addNewRecordButton.on('click', handleAddNewRecord)

    const deleteRecordButton = $('input[name=deleteDnsSecRecord]');
    deleteRecordButton.on('click', handleDelete)

    const turnOnDnssecButton  = $('input[name=turnOnOffDnssec][data-value=1]');
    const turnOffDnssecButton = $('input[name=turnOnOffDnssec][data-value=0]');
    turnOnDnssecButton.on('click', handleTurnOnDnssec);
    turnOffDnssecButton.on('click', handleTurnOffDnssec);

    const alertOnDnssecEnabled  = $('.dnssec-alert-on-enabled');
    const alertOnDnssecDisabled = $('.dnssec-alert-on-disabled');

    const dnssecRecordsTable = $('table.dnssec-records-table');

    function handleAddNewRecord(e) {
        e.preventDefault();
        addNewRecordButton.addClass('hidden');
        let dnsSecRecordTemplate = `
            <tr>
                <td>
                    <select name="dnsSecRecordFlag" value="">
                        <option value="256">ZSK</option>
                        <option value="257">KSK</option>
                    </select>
                </td>
                <td>
                    <select name="dnsSecRecordAlgorithm" value="">
                        <option value="3">3 - DSA/SHA1</option>
                        <option value="5">5 - RSA/SHA1</option>
                        <option value="6">6 - DSA-NSEC3-SHA1</option>
                        <option value="7">7 - RSASHA1-NSEC3-SHA1</option>
                        <option value="8">8 - RSA/SHA-256</option>
                        <option value="10">10 - RSA/SHA-512</option>
                        <option value="12">12 - GOST R 34.10-2001</option>
                        <option value="13">13 - ECDSA Curve P-256 with SHA-256</option>
                    </select>
                </td>
                <td>
                    <textarea class="ta" placeholder="Your public key..." name="dnsSecRecordPublicKey"></textarea>
                </td>
                <td>
                    <input type="button" name="saveDnsSecRecord" class="btn btn-primary" value="Save"/>
                </td>
            </tr>
        `;

        dnssecRecordsTable.find('tbody').append(dnsSecRecordTemplate);

        let dnsSecRecordFlag = $('select[name=dnsSecRecordFlag]')[0],
            dnsSecRecordAlgorithm = $('select[name=dnsSecRecordAlgorithm]')[0],
            dnsSecRecordPublickKey = $('textarea[name=dnsSecRecordPublicKey]')[0],
            saveButton = $('input[name=saveDnsSecRecord]')[0];

        $(saveButton).on('click', function ( ) {
            let data = {
                flags   : dnsSecRecordFlag.value,
                alg     : dnsSecRecordAlgorithm.value,
                pubKey  : dnsSecRecordPublickKey.value,
                domainId: domainId,
                action  : 'create',
            }
            showHideErrorMessage();

            $.ajax({
                method: 'GET',
                url: apiUrlUpdateDnssecRecords,
                data,
            }).done(function (reply) {
                const data = JSON.parse(reply);
                if (data.success) {
                    addNewRecordButton.removeClass('hidden');
                    renderTable(data.dnssecKeys);
                } else {
                    showHideErrorMessage(data.message);
                }
            })
        })
    }

    function handleDelete(e) {
        e.preventDefault();
        let row = $(this).parents('tr'),
            dnsSecRecordFlag = $(row).find('td').get(0).textContent,
            dnsSecRecordAlgorithm = $(row).find('td').get(1).textContent,
            dnsSecRecordPublickKey = $(row).find('td').get(2).textContent;

        let data = {
            flags   : dnsSecRecordFlag,
            alg     : dnsSecRecordAlgorithm,
            pubKey  : dnsSecRecordPublickKey,
            domainId: domainId,
            action  : 'delete',
        }

        $.ajax({
            method: 'GET',
            url: apiUrlUpdateDnssecRecords,
            data,
        }).done(function (reply) {
            const data = JSON.parse(reply);
            if (data.success) {
                renderTable(data.dnssecKeys);
                if (!data.dnssecKeys.length) {
                    dnssecRecordsTable.addClass('hidden');
                    addNewRecordButton.addClass('hidden');

                    turnOffDnssecButton.addClass('hidden');
                    turnOnDnssecButton.removeClass('hidden');

                    alertOnDnssecEnabled.addClass('hidden');
                    alertOnDnssecDisabled.removeClass('hidden');
                }
            } else {
                showHideErrorMessage(data.message);
            }
        })
    }

    function handleTurnOnDnssec(e) {
        e.preventDefault();

        $('.dnssec-records-table').removeClass('hidden');
        addNewRecordButton.removeClass('hidden');
        $(this).addClass('hidden');
        turnOffDnssecButton.removeClass('hidden');

        alertOnDnssecEnabled.removeClass('hidden');
        alertOnDnssecDisabled.addClass('hidden');
    }

    function handleTurnOffDnssec(e) {
        e.preventDefault();

        $.ajax({
            method: 'GET',
            url: apiUrlTurnOnOffDnssec,
            data: {
                isDnssecEnabled: 0,
                domainId: domainId,
            }
        }).done(function (reply) {
            const data = JSON.parse(reply);
            if (data.success) {
                renderTable([]);
                dnssecRecordsTable.addClass('hidden');
                addNewRecordButton.addClass('hidden');

                turnOffDnssecButton.addClass('hidden');
                turnOnDnssecButton.removeClass('hidden');

                alertOnDnssecEnabled.addClass('hidden');
                alertOnDnssecDisabled.removeClass('hidden');
            } else {
                showHideErrorMessage(data.message);
            }
        });
    }

    function renderTable(dnssecKeys) {
        let tbody = $('.dnssec-records-table').find('tbody');
        tbody.first().html(dnssecKeys.map(rowItem).join(''));
        $('input[name=deleteDnsSecRecord]').on('click', handleDelete);
    }

    function rowItem(props) {
        const {
                  flags,
                  alg,
                  pubKey,
              } = props;

        return `<tr>
            <td>` + flags + `</td>
            <td>` + alg + `</td>
            <td>` + pubKey + `</td>
            <td>
                <input type="button" name="deleteDnsSecRecord" class="btn btn-danger" value="Delete" />
            </td>
        </tr>`;
    }

    function showHideErrorMessage(message = '') {
        const alertErrorMessage = $('.dnssec-alert-error-message');

        if (!message) {
            alertErrorMessage.addClass('hidden');
            return;
        }

        alertErrorMessage.removeClass('hidden').html(message);
    }
})

