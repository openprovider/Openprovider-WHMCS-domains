<?php
/*
--------------------------------------------------------------------
              ///  WHMCS DOMAIN ADDITIONAL FIELDS  \\\
--------------------------------------------------------------------

This is where you can define the TLD specific fields required to
register certain TLDs. Supported variables are:

  Name - key name used to reference field in modules (required)
  DisplayName - name displayed in client & admin interfaces
  LangVar - the language file variable to use if set
  Type - field type: text, dropdown, radio, tickbox (required)
  Size - the length of the text field
  Default - the default value the field should take
  Required - force entry - true/false

--------------------------------------------------------------------
*/

// .US

$additionaldomainfields[".us"][] = array("Name" => "Nexus Category", "LangVar" => "ustldnexuscat", "Type" => "dropdown", "Options" => "C11,C12,C21,C31,C32", "Default" => "C11",);
$additionaldomainfields[".us"][] = array("Name" => "Nexus Country", "LangVar" => "ustldnexuscountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".us"][] = array("Name" => "Application Purpose", "LangVar" => "ustldapppurpose", "Type" => "dropdown", "Options" => "Business use for profit,Non-profit business,Club,Association,Religious Organization,Personal Use,Educational purposes,Government purposes", "Default" => "Business use for profit",);

// .UK

$additionaldomainfields[".co.uk"][] = array("Name" => "Legal Type", "LangVar" => "uktldlegaltype", "Type" => "dropdown", "Options" => "Individual,UK Limited Company,UK Public Limited Company,UK Partnership,UK Limited Liability Partnership,Sole Trader,UK Registered Charity,UK Entity (other),Foreign Organization,Other foreign organizations,UK Industrial/Provident Registered Company,UK School,UK Government Body,UK Corporation by Royal Charter,UK Statutory Body,Non-UK Individual", "Default" => "Individual",);
$additionaldomainfields[".co.uk"][] = array("Name" => "Company ID Number", "LangVar" => "uktldcompanyid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => false,);
$additionaldomainfields[".co.uk"][] = array("Name" => "Registrant Name", "LangVar" => "uktldregname", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);
$additionaldomainfields[".net.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".org.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".me.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".plc.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".ltd.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".co.uk"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "uktldwhoisoptout", "Type" => "tickbox",);
$additionaldomainfields[".uk"] = $additionaldomainfields[".co.uk"];

// .CA

$additionaldomainfields[".ca"][] = array("Name" => "Legal Type", "LangVar" => "catldlegaltype", "Type" => "dropdown", "Options" => "Corporation,Canadian Citizen,Permanent Resident of Canada,Government,Canadian Educational Institution,Canadian Unincorporated Association,Canadian Hospital,Partnership Registered in Canada,Trade-mark registered in Canada,Canadian Trade Union,Canadian Political Party,Canadian Library Archive or Museum,Trust established in Canada,Aboriginal Peoples,Legal Representative of a Canadian Citizen,Official mark registered in Canada", "Default" => "Corporation", "Description" => "Legal type of registrant contact",);
$additionaldomainfields[".ca"][] = array("Name" => "CIRA Agreement", "LangVar" => "catldciraagreement", "Type" => "tickbox", "Description" => "Tick to confirm you agree to the CIRA Registration Agreement shown below<br /><blockquote>You have read, understood and agree to the terms and conditions of the Registrant Agreement, and that CIRA may, from time to time and at its discretion, amend any or all of the terms and conditions of the Registrant Agreement, as CIRA deems appropriate, by posting a notice of the changes on the CIRA website and by sending a notice of any material changes to Registrant. You meet all the requirements of the Registrant Agreement to be a Registrant, to apply for the registration of a Domain Name Registration, and to hold and maintain a Domain Name Registration, including without limitation CIRA's Canadian Presence Requirements for Registrants, at: www.cira.ca/assets/Documents/Legal/Registrants/CPR.pdf. CIRA will collect, use and disclose your personal information, as set out in CIRA's Privacy Policy, at: www.cira.ca/assets/Documents/Legal/Registrants/privacy.pdf</blockquote>",);
$additionaldomainfields[".ca"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "catldwhoisoptout", "Type" => "tickbox", "Description" => "Tick to hide your contact information in CIRA WHOIS (only available to individuals)",);

// .ES 

$additionaldomainfields[".es"]   = array();
$additionaldomainfields[".es"][] = array("Name" => "ID Form Type (registrant)", "LangVar" => "estldidformtype", "Type" => "dropdown", "Options" => "0|Other Identification,1|NIF – Tax ID Number,3|NIE – Resident Alien ID Number", "Default" => "0",);
$additionaldomainfields[".es"][] = array("Name" => "ID Form Number (registrant)", "LangVar" => "estldidformnum", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);
$additionaldomainfields[".es"][] = array("Name" => "Type of Business Entity (registrant)", "LangVar" => "", "Type" => "dropdown", "Options" => "1|-------------,39|Economic Interest Group,47|Association,59|Sport Association,68|Guild Association,124|Savings Bank,150|Co-ownership,152|Residents’ Association,164|Congregation or Religious Institution,181|Consulate,197|Public Corporation,203|Embassy,229|Local Entity,269|Sport Federation,286|Foundation,365|Mutual Benefit Society,434|Regional Administration Entity,436|National Administration Entity,439|Political Party,476|Labor Union,510|Agricultural Processing Society,524|Incorporated Company,525|Sport Incorporated Company,554|Non-profit Company,560|General Partnership,562|General and Limited Partnership,566|Cooperative,608|Limited Labor Company,612|Private Limited Company,713|Branch Office in Spai,717|Temporary Business Association,744|Labor Incorporated Company,745|Regional Public Entity,746|National Public Entity,747|Local Public Entity,877|Other,878|Denomination of Origin Regulatory Board,879|Nature Area Management Entity l", "Default" => "1", "Required" => false);
$additionaldomainfields[".es"][] = array("Name" => "ID Form Type (contacts)", "LangVar" => "estldidformtype", "Type" => "dropdown", "Options" => "0|Other Identification,1|NIF – Tax ID Number,3|NIE – Resident Alien ID Number", "Default" => "0",);
$additionaldomainfields[".es"][] = array("Name" => "ID Form Number (contacts)", "LangVar" => "estldidformnum", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);

// .COM.ES

$additionaldomainfields[".com.es"] = $additionaldomainfields[".es"];

// .NOM.ES

$additionaldomainfields[".nom.es"] = $additionaldomainfields[".es"];

// .ORG.ES

$additionaldomainfields[".org.es"] = $additionaldomainfields[".es"];

// .GOB.ES
$additionaldomainfields[".gob.es"] = $additionaldomainfields[".es"];

// .EDU.ES
$additionaldomainfields[".edu.es"] = $additionaldomainfields[".es"];


// .SG

$additionaldomainfields[".sg"][] = array("Name" => "RCB Singapore ID", "DisplayName" => "RCB/Singapore ID", "LangVar" => "sgtldrcbid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);
$additionaldomainfields[".sg"][] = array("Name" => "Registrant Type", "LangVar" => "sgtldregtype", "Type" => "dropdown", "Options" => "Individual,Organisation", "Default" => "Individual",);
$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".edu.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".net.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".org.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".per.sg"] = $additionaldomainfields[".sg"];

// .TEL

$additionaldomainfields[".tel"][] = array("Name" => "Legal Type", "LangVar" => "teltldlegaltype", "Type" => "dropdown", "Options" => "Natural Person,Legal Person", "Default" => "Natural Person",);
$additionaldomainfields[".tel"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "teltldwhoisoptout", "Type" => "tickbox",);

// .IT

$additionaldomainfields[".it"][] = array("Name" => "Legal Type", "LangVar" => "ittldlegaltype", "Type" => "dropdown", "Options" => "Italian and foreign natural persons,Companies/one man companies,Freelance workers/professionals,non-profit organizations,public organizations,other subjects,non natural foreigners", "Default" => "Italian and foreign natural persons", "Description" => "Legal type of registrant",);
$additionaldomainfields[".it"][] = array("Name" => "Tax ID", "LangVar" => "ittldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".it"][] = array("Name" => "Publish Personal Data", "LangVar" => "ittlddata", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 3 of .IT registrar contract", "LangVar" => "ittldsec3", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 5 of .IT registrar contract", "LangVar" => "ittldsec5", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 6 of .IT registrar contract", "LangVar" => "ittldsec6", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 7 of .IT registrar contract", "LangVar" => "ittldsec7", "Type" => "tickbox",);

// .DE

$additionaldomainfields[".de"][] = array("Name" => "Tax ID", "LangVar" => "detldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".de"][] = array("Name" => "Address Confirmation", "LangVar" => "detldaddressconfirm", "Type" => "tickbox", "Description" => "Please tick to confirm you have a valid German address",);

// .AU

$additionaldomainfields[".com.au"][] = array("Name" => "Registrant Name", "LangVar" => "autldregname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID", "LangVar" => "autldregid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID Type", "LangVar" => "autldregidtype", "Type" => "dropdown", "Options" => "ABN,ACN,Business Registration Number", "Default" => "ABN",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Name", "LangVar" => "autldeligname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID", "LangVar" => "autldeligid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID Type", "LangVar" => "autldeligidtype", "Type" => "dropdown", "Options" => ",Australian Company Number (ACN),ACT Business Number,NSW Business Number,NT Business Number,QLD Business Number,SA Business Number,TAS Business Number,VIC Business Number,WA Business Number,Trademark (TM),Other - Used to record an Incorporated Association number,Australian Business Number (ABN)", "Default" => "",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Type", "LangVar" => "autldeligtype", "Type" => "dropdown", "Options" => "Charity,Citizen/Resident,Club,Commercial Statutory Body,Company,Incorporated Association,Industry Body,Non-profit Organisation,Other,Partnership,Pending TM Owner  ,Political Party,Registered Business,Religious/Church Group,Sole Trader,Trade Union,Trademark Owner,Child Care Centre,Government School,Higher Education Institution,National Body,Non-Government School,Pre-school,Research Organisation,Training Organisation", "Default" => "Company",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Reason", "LangVar" => "autldeligreason", "Type" => "radio", "Options" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.,Close and substantial connection between the domain name and the operations of your Entity.", "Default" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.",);

$additionaldomainfields[".net.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".org.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".asn.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".id.au"] = $additionaldomainfields[".com.au"];

// .ASIA

$additionaldomainfields[".asia"][] = array("Name" => "Legal Type", "LangVar" => "asialegaltype", "Type" => "dropdown", "Options" => "naturalPerson,corporation,cooperative,partnership,government,politicalParty,society,institution", "Default" => "naturalPerson",);
$additionaldomainfields[".asia"][] = array("Name" => "Identity Form", "LangVar" => "asiaidentityform", "Type" => "dropdown", "Options" => "passport,certificate,legislation,societyRegistry,politicalPartyRegistry", "Default" => "passport",);
$additionaldomainfields[".asia"][] = array("Name" => "Identity Number", "LangVar" => "asiaidentitynumber", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);

// .PRO

$additionaldomainfields[".pro"][] = array("Name" => "Profession", "LangVar" => "proprofession", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "Indicated professional association recognized by government body",);

// .COOP

$additionaldomainfields[".coop"][] = array("Name" => "Contact Name", "LangVar" => "coopcontactname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "A sponsor is required to register .coop domains. Please enter the information here",);
$additionaldomainfields[".coop"][] = array("Name" => "Contact Company", "LangVar" => "cooopcontactcompany", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Contact Email", "LangVar" => "coopcontactemail", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Address 1", "LangVar" => "coopaddress1", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Address 2", "LangVar" => "coopaddress2", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "City", "LangVar" => "coopcity", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "State", "LangVar" => "coopstate", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "ZIP Code", "LangVar" => "coopzip", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Country", "LangVar" => "coopcountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "2 Letter Country Code",);
$additionaldomainfields[".coop"][] = array("Name" => "Phone CC", "LangVar" => "coopphonecc", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "Phone Country Code eg 1 for US & Canada, 44 for UK",);
$additionaldomainfields[".coop"][] = array("Name" => "Phone", "LangVar" => "coopphone", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);

// .CN
$additionaldomainfields[".cn"][] = array("Name" => "cnhosting", "DisplayName" => "Hosted in China?", "LangVar" => "cnhosting", "Type" => "tickbox");
$additionaldomainfields[".cn"][] = array("Name" => "cnhregisterclause", "DisplayName" => "Agree to the .CN <a href=\"http://www1.cnnic.cn/PublicS/fwzxxgzcfg/201208/t20120830_35735.htm\" target=\"_blank\">Register Agreement</a>", "LangVar" => "ittldsec3", "Type" => "tickbox", "Required" => true, );

// .FR
$additionaldomainfields[".fr"][] = array("Name" => "Legal Type", "LangVar" => "fr_legaltype", "Type" => "dropdown", "Options" => "Individual,Company", "Default" => "Individual",);
$additionaldomainfields[".fr"][] = array("Name" => "Info", "LangVar" => "fr_info", "Type" => "display", "Default" =>
    "{$_LANG['enomfrregistration']['Heading']}
        <ul>
            <li><strong>{$_LANG['enomfrregistration']['French Individuals']['Name']}</strong>: {$_LANG['enomfrregistration']['French Individuals']['Requirements']}</li>
            <li><strong>{$_LANG['enomfrregistration']['EU Non-French Individuals']['Name']}</strong>: {$_LANG['enomfrregistration']['EU Non-French Individuals']['Requirements']}</li>
            <li><strong>{$_LANG['enomfrregistration']['French Companies']['Name']}</strong>: {$_LANG['enomfrregistration']['French Companies']['Requirements']}</li>
            <li><strong>{$_LANG['enomfrregistration']['EU Non-French Companies']['Name']}</strong>: {$_LANG['enomfrregistration']['EU Non-French Companies']['Requirements']}</li>
        </ul>
        <em>{$_LANG['enomfrregistration']['Non-EU Warning']}</em>",);
$additionaldomainfields[".fr"][] = array("Name" => "Birthdate", 'LangVar' => 'fr_indbirthdate', "Type" => "text","Size" => "16","Default" => "1900-01-01","Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "Birthplace City", 'LangVar' => 'fr_indbirthcity', "Type" => "text","Size" => "50","Default" => "","Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "Birthplace Postcode", 'LangVar' => 'fr_indbirthpostcode', "Type" => "text","Size" => "6","Default" => "","Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "SIRET Number", 'LangVar' => 'fr_cosiret', "Type" => "text","Size" => "50","Default" => "","Required" => false);
$additionaldomainfields[".fr"][] = array("Name" => "DUNS Number", 'LangVar' => 'fr_coduns', "Type" => "text","Size" => "50","Default" => "","Required" => false);


/********************************************* OpenProvider Domain Additional Fields ********************************************/
if(file_exists(ROOTDIR.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'registrars'.DIRECTORY_SEPARATOR.'openprovider'.DIRECTORY_SEPARATOR.'additionaldomainfields.php'))
{
//    global $additionaldomainfields;
    require_once ROOTDIR.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'registrars'.DIRECTORY_SEPARATOR.'openprovider'.DIRECTORY_SEPARATOR.'additionaldomainfields.php';
}
/************************************************************************************************************************************/