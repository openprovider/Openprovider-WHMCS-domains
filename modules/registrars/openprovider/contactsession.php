<?php require_once('../../../init.php');

use WHMCS\Database\Capsule;

if (($_POST['set'] ?? '') !== 'contactsession') {
    header("Location: /");
    exit;
}

// Only a logged-in client may store a contact selection.
$loggedInClientId = $_SESSION['uid'] ?? null;
if (!$loggedInClientId) {
    http_response_code(403);
    exit;
}

// Keep only "c<digits>" values pointing at a contact owned by the logged-in client; drop the rest.
$sanitizeContact = function ($value) use ($loggedInClientId) {
    if (!is_string($value) || $value === '' || $value[0] !== 'c') {
        return '';
    }

    $contactId = substr($value, 1);
    if (!ctype_digit($contactId)) {
        return '';
    }

    $owned = Capsule::table('tblcontacts')
        ->where('id', '=', $contactId)
        ->where('userid', '=', $loggedInClientId)
        ->exists();

    return $owned ? 'c' . $contactId : '';
};

$data = array(
    'owner'   => $sanitizeContact($_POST['owner'] ?? ''),
    'admin'   => $sanitizeContact($_POST['admin'] ?? ''),
    'tech'    => $sanitizeContact($_POST['tech'] ?? ''),
    'billing' => $sanitizeContact($_POST['billing'] ?? ''),
);

$_SESSION['contactsession'] = json_encode($data);
