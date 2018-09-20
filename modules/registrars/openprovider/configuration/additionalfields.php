<?php
/**
 * Configuration fields
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

// .US
$additionaldomainfields[".us"][] = array(
    "Name" => "Nexus Category",
    "LangVar" => "ustldnexuscat",
    "Type" => "dropdown",
    "Options" => "C11,C12,C21,C31,C32",
    "Default" => "C11",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "nexusCategory"
);

$additionaldomainfields[".us"][] = array(
    "Name" => "Application Purpose",
    "LangVar" => "ustldapppurpose",
    "Type" => "dropdown",
    "Options" => "P1 - Business use for profit,P2 - Non-profit business (club;  association; religious organization),P3 - Personal,P5 - Government purposes",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "applicantPurpose",
    "op_explode" => ' -'
);

// .TRAVEL
$additionaldomainfields[".travel"][] = array(
    "Name" => "UIN (Unique Identification Number)",
    "LangVar" => "travelUIN",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "uin",
);

// .XXX
$additionaldomainfields[".xxx"][] = array(
    "Name" => "Sponsored Communicaty",
    "LangVar" => "xxxCommunityId",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "communityId",
);

$additionaldomainfields[".xxx"][] = array(
    "Name" => "Defensive",
    "LangVar" => "xxxDefensive",
    "Type" => "tickbox",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "defensive",
);

// .pro
$additionaldomainfields[".pro"][] = array(
    "Name" => "Profession",
    "LangVar" => "proProfession",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "profession",
);

$additionaldomainfields[".pro"][] = array(
    "Name" => "Authority Name",
    "LangVar" => "proAuthorityName",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "authorityName",
);

$additionaldomainfields[".pro"][] = array(
    "Name" => "Authority URL",
    "LangVar" => "proAuthorityUrl",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "authorityUrl",
);

$additionaldomainfields[".pro"][] = array(
    "Name" => "License Number",
    "LangVar" => "proLicenseNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "licenseNumber",
);

// .JOBS
$additionaldomainfields[".jobs"][] = array(
    "Name" => "Website",
    "LangVar" => "jobsWebsite",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "website",
);

$additionaldomainfields[".jobs"][] = array(
    "Name" => "Industry Class",
    "LangVar" => "jobsIndustryClass",
    "Type" => "dropdown",
    "Options" => "None,2,3,21,5,4,12,6,7,13,19,10,11,15,16,17,18,20,9,26,22,14,23,8,24,25",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "industryClass",
    "op_skip" => "None"
);

$additionaldomainfields[".jobs"][] = array(
    "Name" => "Admin Type",
    "LangVar" => "jobsAdminType",
    "Type" => "tickbox",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "adminType"
);

$additionaldomainfields[".jobs"][] = array(
    "Name" => "Contact Title",
    "LangVar" => "jobsContactTitle",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "contactTitle"
);
    
$additionaldomainfields[".jobs"][] = array(
    "Name" => "HR Member",
    "LangVar" => "jobsHRMember",
    "Type" => "tickbox",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "hrMember"
);

// .AERO
$additionaldomainfields[".aero"][] = array(
    "Name" => ".aero ID",
    "LangVar" => "aeroEnsAuthId",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "ensAuthId"
);

$additionaldomainfields[".aero"][] = array(
    "Name" => ".aero ENS key",
    "LangVar" => "aeroEnsKey",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "ensKey"
);

// .ru & .рф (xn--p1ai)
// $additionaldomainfields[".ru"][] = array(
//     "Name" => "Company Name Cyrillic",
//     "LangVar" => "ruCompanyNameCyrillic",
//     "Type" => "text",
//     "op_location" => "customerExtensionAdditionalData",
//     "op_name" => "сompanyNameCyrillic"
// );

// $additionaldomainfields[".ru"][] = array(
//     "Name" => "Company Name Latin",
//     "LangVar" => "ruCompanyNameLatin",
//     "Type" => "text",
//     "op_location" => "customerExtensionAdditionalData",
//     "op_name" => "сompanyNameLatin"
// );

$additionaldomainfields[".ru"][] = array(
    "Name" => "Mobile Phone Number",
    "LangVar" => "ruMobilePhoneNumber",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "mobilePhoneNumber"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Legal Address Cyrillic",
    "LangVar" => "ruLegalAddressCyrillic",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "legalAddressCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Postal Address Cyrillic",
    "LangVar" => "ruPostalAddressCyrillic",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "postalAddressCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Tax Payer Number",
    "LangVar" => "ruTaxPayerNumber",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "taxPayerNumber"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "First Name Cyrillic",
    "LangVar" => "ruFirstNameCyrillic",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "firstNameCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Middle Name Cyrillic",
    "LangVar" => "ruMiddleNameCyrillic",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "middleNameCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Last Name Cyrillic",
    "LangVar" => "ruLastNameCyrillic",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "lastNameCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "First Name Latin",
    "LangVar" => "ruFirstNameLatin",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "firstNameLatin"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Middle Name Latin",
    "LangVar" => "ruMiddleNameLatin",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "middleNameLatin"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Last Name Latin",
    "LangVar" => "ruLastNameLatin",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "lastNameLatin"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Series",
    "LangVar" => "ruPassportSeries",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportSeries"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Number",
    "LangVar" => "ruPassportNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportNumber"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Issuer",
    "LangVar" => "ruPassportIssuer",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportIssuer"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Issue Date",
    "LangVar" => "ruPassportIssueDate",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportIssueDate"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Birth Date",
    "LangVar" => "ruBirthDate",
    "Type" => "text",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "birthDate"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Mobile Phonenumber",
    "LangVar" => "ruMobilePhoneNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "mobilePhoneNumber"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Legal Address Cyrillic",
    "LangVar" => "ruLegalAddressCyrillic",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "legalAddressCyrillic"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Tax Payer Number",
    "LangVar" => "ruTaxPayerNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "taxPayerNumber"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Company Name Latin",
    "LangVar" => "ruCompanyNameLatin",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "companyNameLatin"
);

$additionaldomainfields[".ru"][] = array(
    "Name" => "Mobile Phonenumber",
    "LangVar" => "ruMobilePhoneNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "mobilePhoneNumber"
);


$additionaldomainfields['.xn--p1ai'] = $additionaldomainfields['.ru'];

// .RO
$additionaldomainfields[".ro"][] = array(
    "Name" => "Company Registration Number",
    "LangVar" => "roCompanyRegistrationNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "companyRegistrationNumber"
);

$additionaldomainfields[".ro"][] = array(
    "Name" => "Social Security Number",
    "LangVar" => "roSocialSecurityNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "socialSecurityNumber"
);

// .NO
$additionaldomainfields[".no"] = $additionaldomainfields[".ro"];

// .ES
$additionaldomainfields[".es"][] = array(
    "Name" => "Company Registration Number",
    "LangVar" => "esCompanyRegistrationNumber",
    "Type" => "text",
    "Size" => "30",
    "op_location" => "customerExtensionAdditionalData",
    "op_name"  => "companyRegistrationNumber"
);

$additionaldomainfields[".es"][] = array(
    "Name" => "Social Security Number",
    "LangVar" => "esSocialSecurityNumber",
    "Type" => "text",
    "Size" => "30",
    "op_location" => "customerExtensionAdditionalData",
    "op_name"   => "socialSecurityNumber"
);

$additionaldomainfields[".es"][] = array(
    "Name" => "Passport Number",
    "LangVar" => "esPassportNumber",
    "Type" => "text",
    "Size" => "30",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportNumber"
);

// .SG

$additionaldomainfields[".sg"][] = array(
    "Name" => "Company Registration Number",
    "LangVar" => "companyRegistrationNumber",
    "Type" => "text",
    "Size" => "30",
    "op_location" => "customerExtensionAdditionalData",
    "op_name"  => "companyRegistrationNumber"
);

// .COM.SG

$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];

// .HU

$additionaldomainfields[".hu"][] = array(
    "Name" => "VAT",
    "LangVar" => "huVat",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "vat"
);

$additionaldomainfields[".hu"][] = array(
    "Name" => "Passportnumber",
    "LangVar" => "huPassportNumber",
    "Type" => "text",
    "op_location" => "customerExtensionAdditionalData",
    "op_name" => "passportNumber"
);

// .ID.AU

$additionaldomainfields[".id.au"][] = array(
    "Name" => "Eligibility Type Relationship",
    "LangVar" => "idauEligibilityTypeRelationship",
    "Type" => "dropdown",
    "Options" => "1 - 2LD Domain name is an exact match; an acronym or abbreviation of the company or trading name; organisation or association name; or trademark,2 - 2LD Domain Name is closely and substantially connected to the organisation or activities undertaken by the organisation.",
    "Required" => true,
    "op_explode" => " -",
    "op_location" => "domainAdditionalData",
    "op_name" => "eligibilityTypeRelationship"
);

$additionaldomainfields[".id.au"][] = array(
    "Name" => "Eligibility Type",
    "LangVar" => "idauEligibilityType",
    "Type" => "dropdown",
    "Options" => "Company,RegisteredBusiness,SoleTrader,Partnership,TrademarkOwner,PendingTMOwner,CitizenResident,IncorporatedAssociation,Club,NonProfitOrganisation,Charity,TradeUnion,IndustryBody,CommercialStatutoryBody,PoliticalParty,Other,Non-profit,Organisation,Charity,Citizen/Resident",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "eligibilityType"
);

// .COM.AU
$additionaldomainfields[".com.au"] = $additionaldomainfields[".id.au"];

// .NET.AU
$additionaldomainfields[".et.au"] = $additionaldomainfields[".id.au"];

// .ORG.AU
$additionaldomainfields[".org.au"] = $additionaldomainfields[".id.au"];

// .CA
$additionaldomainfields[".ca"][] = array(
    "Name" => "Legal Type",
    "LangVar" => "caLegalType",
    "Type" => "dropdown",
    "Options" => "ABO - Aboriginal Peoples indigenous to Canada,ASS - Canadian Unincorporated Association,CCO - Corporation (Canada or Canadian province or territory,CCT - Canadian citizen,EDU - Canadian Educational Institution,GOV - Government or government entity in Canada,HOP - Canadian Hospital,INB - Indian Band recognized by the Indian Act of Canada,LAM - Canadian Library; Archive or Museum,LGR - Legal Rep. of a Canadian Citizen or Permanent Resident,MAJ - Her Majesty the Queen,OMK - Official mark registered in Canada,PLT - Canadian Political Party,PRT - Partnership Registered in Canada,RES- Permanent Resident of Canada,TDM - Trade-mark registered in Canada (by a non-Canadian owner),TRD - Canadian Trade Union,TRS - Trust established in Canada",
    "Required" => true,
    "op_explode" => " -",
    "op_location" => "domainAdditionalData",
    "op_name" => "legalType"
);

// .COM
$additionaldomainfields[".com"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "comIdnScript",
    "Type" => "dropdown",
    "Options" => "AFR - African,ALB - Albanian,ARA - Arabic,ARG - Aragonese,ARM - Armenian,ASM - Assamese,AST - Asturian,AVE - Avestan,AWA - Awadhi,AZE - Azerbaijani,BAN - Balinese,BAL - Baluchi,BAS - Basa,BAK - Bashkir,BAQ - Basque,BEL - Belarusian,BEN - Bengali,BHO - Bhojpuri,BOS - Bosnian,BUL - Bulgarian,BUR - Burmese,CAR - Carib,CAT - Catalan,CHE - Chechen,CHI - Chinese,CHV - Chuvash,COP - Coptic,COS - Corsican,SCR - Croatian,CZE - Czech,DAN - Danish,DIV - Divehi,DOI - Dogri,DUT - Dutch,ENG - English,EST - Estonian,FAO - Faroese,FIJ - Fijian,FIN - Finnish,FRE - French,FRI - Frisian,GLA - Gaelic,GEO - Georgian,GER - German,GON - Gondi,GRE - Greek,GUJ - Gujarati,HEB - Hebrew,HIN - Hindi,HUN - Hungarian,ICE - Icelandic,INC - Indic,IND - Indonesian,INH - Ingush,GLE - Irish,ITA - Italian,JPN - Japanese,JAV - Javanese,KAS - Kashmiri,KAZ - Kazakh,KHM - Khmer,KIR - Kirghiz,KOR - Korean,KUR - Kurdish,LAO - Lao,LAV - Latvian,LIT - Lithuanian,LTZ - Luxembourgisch,MAC - Macedonian,MAL - Malayalam,MAY - Malay,MLT - Maltese,MAO - Maori,MOL - Moldavian,MON - Mongolian,NEP - Nepali,NOR - Norwegian,ORI - Oriya,OSS - Ossetian,PAN - Panjabi,PER - Persian,POL - Polish,POR - Portuguese,PUS - Pushto,RAJ - Rajasthani,RUM - Romanian,RUS - Russian,SMO - Samoan,SAN - Sanskrit,SRD - Sardinian,SCC - Serbian,SND - Sindhi,SIN - Sinhalese,SLO - Slovak,SLV - Slovenian,SOM - Somali,SPA - Spanish,SWA - Swahili,SWE - Swedish,SYR - Syriac,TGK - Tajik,TAM - Tamil,TEL - Telugu,THA - Thai,TIB - Tibetan,TUR - Turkish,UKR - Ukrainian,URD - Urdu,UZB - Uzbek,VIE - Vietnamese,WEL - Welsh,YID - Yiddish",
    "op_explode" => " -",
    "op_location" => "domainAdditionalData",
    "op_name" => "idnScript"
);

// .NET
$additionaldomainfields[".net"] = $additionaldomainfields[".com"];

// .ORG
$additionaldomainfields[".org"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "orgIdnScript",
    "Type" => "dropdown",
    "Options" => "None,ZH-TW - Chinese (Traditional),ZH-CN Chinese (Simplified),DA - Danish,DE - German,HU - Hungarian,IS - Icelandic,KO - Korean (Hangul),LV - Latvian,LT - Lithuanian,PL - Polish,ES - Spanish,SV - Swedish,BS - Bosnian,BG - Bulgarian,BE - Belarusian,MK - Macedonian,RU - Russian,SR - Serbian,UK - Ukrainian",
    "op_explode" => " -",
    "op_location" => "domainAdditionalData",
    "op_name" => "idnScript",
    "op_skip" => "None"
);

// .VOTO
$additionaldomainfields[".voto"][] = array(
    "Name" => "Accept Voto Registry Policy",
    "LangVar" => "votoAcceptance",
    "Type" => "tickbox",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "votoAcceptance"
);

// .VOTE
$additionaldomainfields[".vote"][] = array(
    "Name" => "Accept Vote Registry Policy",
    "LangVar" => "voteAcceptance",
    "Type" => "tickbox",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "voteAcceptance"
);

// .SCOT
$additionaldomainfields[".scot"][] = array(
    "Name" => "Intended Use",
    "LangVar" => "scotIntendedUse",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "intendedUse"
);

$additionaldomainfields[".scot"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "scotIdnScript",
    "Type" => "dropdown",
    "Options" => "None,Latn - Latin",
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript",
    "op_skip" => "None"
);

$additionaldomainfields[".scot"][] = array(
    "Name" => "Domain Name Variants (other variants separated by ,",
    "LangVar" => "scotDomainNameVariants",
    "Type" => "text",
    "op_location" => "domainAdditionalData",
    "op_name" => "domainNameVariants"
);

// .EUS
$additionaldomainfields[".eus"] = $additionaldomainfields[".scot"];

// .GAL
$additionaldomainfields[".gal"] = $additionaldomainfields[".scot"];

// .CAT
$additionaldomainfields[".cat"] = $additionaldomainfields[".scot"];

// .BARCELONA
$additionaldomainfields[".barcelona"] = $additionaldomainfields[".scot"];

// .PL
$additionaldomainfields[".pl"][] = array(
    "Name" => "Intended Use",
    "LangVar" => "plIntendedUse",
    "Type" => "text",
    "op_location" => "domainAdditionalData",
    "op_name" => "intendedUse"
);

// .شبكة (xn--ngbc5azd)
$additionaldomainfields[".xn--ngbc5azd"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "ar - Arabic",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript"
);

// .موقع (xn--4gbrim)
$additionaldomainfields[".xn--4gbrim"] = $additionaldomainfields[".xn--ngbc5azd"];

// .cайт (xn--80aswg)
$additionaldomainfields[".xn--80aswg"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "ar - Arabic",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript"
);

// .онлайн (xn--80asehdb)
$additionaldomainfields[".xn--80asehdb"] = $additionaldomainfields[".xn--80aswg"];

// .DEMOCRAT
$additionaldomainfields[".democrat"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "None,fr,es",
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript",
    "op_skip" => "None"
);

// .DANCE
$additionaldomainfields[".dance"] = $additionaldomainfields[".democrat"];

// .NINJA
$additionaldomainfields[".ninja"] = $additionaldomainfields[".democrat"];

// .SOCIAL
$additionaldomainfields[".social"] = $additionaldomainfields[".democrat"];

// .FUTBOL
$additionaldomainfields[".futbol"] = $additionaldomainfields[".democrat"];

// .REVIEWS
$additionaldomainfields[".reviews"] = $additionaldomainfields[".democrat"];

// .PUB
$additionaldomainfields[".pub"] = $additionaldomainfields[".democrat"];

// .MODA
$additionaldomainfields[".moda"] = $additionaldomainfields[".democrat"];

// .CONSULTING
$additionaldomainfields[".consulting"] = $additionaldomainfields[".democrat"];

// .ROCKS
$additionaldomainfields[".rocks"] = $additionaldomainfields[".democrat"];

// .ACTOR
$additionaldomainfields[".actor"] = $additionaldomainfields[".democrat"];

// .REPUBLICAN
$additionaldomainfields[".republican"] = $additionaldomainfields[".democrat"];

// .ATTORNEY
$additionaldomainfields[".attorney"] = $additionaldomainfields[".democrat"];

// .LAWYER
$additionaldomainfields[".lawyer"] = $additionaldomainfields[".democrat"];

// .AIRFORCE
$additionaldomainfields[".airforce"] = $additionaldomainfields[".democrat"];

// .VET
$additionaldomainfields[".vet"] = $additionaldomainfields[".democrat"];

// .ARMY
$additionaldomainfields[".army"] = $additionaldomainfields[".democrat"];

// .NAVY
$additionaldomainfields[".navy"] = $additionaldomainfields[".democrat"];

// .MORTGAGE
$additionaldomainfields[".mortgage"] = $additionaldomainfields[".democrat"];

// .MARKET
$additionaldomainfields[".market"] = $additionaldomainfields[".democrat"];

// .ENGINEER
$additionaldomainfields[".engineer"] = $additionaldomainfields[".democrat"];

// .SOFTWARE
$additionaldomainfields[".software"] = $additionaldomainfields[".democrat"];

// .AUCTION
$additionaldomainfields[".auction"] = $additionaldomainfields[".democrat"];

// .DENTIST
$additionaldomainfields[".dentist"] = $additionaldomainfields[".democrat"];

// .REHAB
$additionaldomainfields[".rehab"] = $additionaldomainfields[".democrat"];

// .GIVES
$additionaldomainfields[".gives"] = $additionaldomainfields[".democrat"];

// .DEGREE
$additionaldomainfields[".degree"] = $additionaldomainfields[".democrat"];

// .FORSALE
$additionaldomainfields[".forsale"] = $additionaldomainfields[".democrat"];

// .RIP
$additionaldomainfields[".rip"] = $additionaldomainfields[".democrat"];

// .BAND
$additionaldomainfields[".band"] = $additionaldomainfields[".democrat"];

// .IMMOBILIEN
$additionaldomainfields[".immobilien"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "None,fr - French,es - Spanish,de - German",
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript",
    "op_skip" => "None"
);

// .KAUFEN
$additionaldomainfields[".kaufen"] = $additionaldomainfields[".immobilien"];

// .HAUS
$additionaldomainfields[".haus"] = $additionaldomainfields[".immobilien"];

// .NRW
$additionaldomainfields[".nrw"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "None,de - German",
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript",
    "op_skip" => "None"
);

// .OPR (xn--c1avg)
$additionaldomainfields[".opr"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "de - German",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript"
);

$additionaldomainfields[".xn--c1avg"] = $additionaldomainfields[".opr"];

// .机构 (xn--nqv7f)
$additionaldomainfields[".xn--nqv7f"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "ZH-TW - Chinese (Traditional),ZH-CN - Chinese (Simplified)",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript"
);

// .机构 (xn--nqv7f)
$additionaldomainfields[".xn--i1b6b1a6a2e"][] = array(
    "Name" => "Internationalized domain name Script (only for IDN domains)",
    "LangVar" => "idnScript",
    "Type" => "dropdown",
    "Options" => "hin-deva",
    "Required" => true,
    "op_location" => "customerExtensionAdditionalData",
    "op_explode" => " -",
    "op_name" => "idnScript"
);

// .SWISS
$additionaldomainfields[".swiss"][] = array(
    "Name" => "Intended Use",
    "LangVar" => "swissIntendedUse",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "intendedUse"
);

// .RADIO
$additionaldomainfields[".radio"][] = array(
    "Name" => "Intended Use",
    "LangVar" => "intendedUse",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "intendedUse"
);

// .LAW
$additionaldomainfields[".law"][] = array(
    "Name" => "Law Accreditation Id",
    "LangVar" => "lawAccreditationId",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "accreditationId"
);

$additionaldomainfields[".law"][] = array(
    "Name" => "Accreditation Body",
    "LangVar" => "lawAccreditationBody",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "accreditationBody"
);

$additionaldomainfields[".law"][] = array(
    "Name" => "Accreditation Year",
    "LangVar" => "lawAccreditationYear",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "accreditationYear"
);

$additionaldomainfields[".law"][] = array(
    "Name" => "jurisdiction CC",
    "LangVar" => "lawJurisdictionCC",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "jurisdictionCC"
);

$additionaldomainfields[".law"][] = array(
    "Name" => "jurisdiction SP",
    "LangVar" => "lawJurisdictionSP",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "jurisdictionSP"
);

// .FI

$additionaldomainfields[".fi"][] = array(
    "Name" => "Organisation Type",
    "LangVar" => "fiOrgType",
    "Type" => "text",
    "Required" => true,
    "op_location" => "domainAdditionalData",
    "op_name" => "orgType"
);

 
   
   
   
  