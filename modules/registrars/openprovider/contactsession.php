<?php require_once('../../../init.php');

if($_POST['set'] == 'contactsession')
{
  $owner = $_POST['owner'];
  $tech = $_POST['tech'];
  $admin = $_POST['admin'];
  $billing = $_POST['billing'];

  $data = array(
    'owner' => $owner,
    'admin' => $admin,
    'tech' => $tech,
    'billing' => $billing,
  );

  $_SESSION['contactsession'] = json_encode($data);
}
else
{
  header("Location: /");
  exit;
}
