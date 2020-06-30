$(document).ready(function () {
    
    var name = 'transfersecret';
    
    // set '0000' in 'EPP Code' field, and hide it
    var eppField = $('[name="' + name + '"]');
    eppField.attr('value', '0000');
    var eppFieldRow = eppField.parent().parent();
    eppFieldRow.hide();
});