<?php

// .DE - delete additonal fields for DE domains
unset($additionaldomainfields[".de"]);

// .ES - delete additional fields for ES domains
unset($additionaldomainfields[".es"]);

// .ID.AU - delete additional fields for ID.AU domains
unset($additionaldomainfields['.id.au']);

// .CA
$additionaldomainfields[".ca"][] = array
(
    "Name"      =>  "Legal Type",
    "LangVar"   =>  "legalType",
    "Type"      =>  "dropdown",
    "Options"   =>  "ABO|Aboriginal Peoples indigenous to Canada,ASS|Canadian Unincorporated Association,CCO|Corporation (Canada or Canadian province or territory),CCT|Canadian citizen,EDU|Canadian Educational Institution,GOV|Government or government entity in Canada,HOP|Canadian Hospital,INB|Indian Band recognized by the Indian Act of Canada,LAM|Canadian Library. Archive or Museum,LGR|Legal Rep. of a Canadian Citizen or Permanent Resident,MAJ|Her Majesty the Queen,OMK|Official mark registered in Canada,PLT|Canadian Political Party,PRT|Partnership Registered in Canada,RES|Permanent Resident of Canada,TDM|Trade-mark registered in Canada (by a non-Canadian owner),TRD|Canadian Trade Union,TRS|Trust established in Canada",
    "Default"   =>  "ABO",
);


// .ID.AU, .NET.AU, .COM.AU, .ORG.AU
$additionaldomainfields[".id.au"][] = array(
    "Name" => "Eligibility Type Relationship",
    "LangVar" => "eligibilityTypeRelationship",
    "Type" => "dropdown",
    "Options" => "1,2",
    "Default" => "1",
);
$additionaldomainfields[".id.au"][] = array(
    "Name" => "Eligibility Type",
    "LangVar" => "eligibilityType",
    "Type" => "dropdown",
    "Options" => "Company,RegisteredBusiness,SoleTrader,Partnership,TrademarkOwner,PendingTMOwner,CitizenResident,IncorporatedAssociation,Club,NonProfitOrganisation,Charity,TradeUnion,IndustryBody,CommercialStatutoryBody,PoliticalParty,Other,Non­profitOrganisation,Charity,Citizen/Resident",
    "Default" => "Company",
);


// .NET.AU, .COM.AU, .ORG.AU
$additionaldomainfields[".net.au"] = $additionaldomainfields[".id.au"];
$additionaldomainfields[".net.au"][] = array(
    "Name" => "ID Number",
    "LangVar" => "idNumber",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => true,
);
$additionaldomainfields[".net.au"][] = array(
    "Name" => "ID Type",
    "LangVar" => "idType",
    "Type" => "dropdown",
    "Options" => "ABN,ACN,OTHER",
    "Default" => "ABN",
);
$additionaldomainfields[".com.au"] = $additionaldomainfields[".net.au"];
$additionaldomainfields[".org.au"] = $additionaldomainfields[".net.au"];


// .BIO
$additionaldomainfields[".bio"][] = array
(
    "Name"      =>  "Bio Acceptance", 
    "LangVar"   =>  "bioAcceptance", 
    "Type"      =>  "tickbox",
    "Required"  =>  true,
);


// .ARCHI
$additionaldomainfields[".archi"][] = array
(
    "Name"      =>  "Archi Acceptance", 
    "LangVar"   =>  "archiAcceptance", 
    "Type"      =>  "tickbox",
    "Required"  =>  true,
);


// .COM, .NET
$additionaldomainfields[".com"][] = array
(
    "Name"      =>  "IDN Script",
    "LangVar"   =>  "idnScript",
    "Type"      =>  "dropdown",
    "Options"   =>  "AFR,ALB,ARA,ARG,ARM,ASM,AST,AVE,AWA,AZE,BAN,BAL,BAS,BAK,BAQ,BEL,BEN,BHO,BOS,BUL,BUR,CAR,CAT,CHE,CHI,CHV,COP,COS,SCR,CZE,DAN,DIV,DOI,DUT,ENG,EST,FAO,FIJ,FIN,FRE,FRY,GLA,GEO,GER,GON,GRE,GUJ,HEB,HIN,HUN,ICE,INC,IND,INH,GLE,ITA,JPN,JAV,KAS,KAZ,KHM,KIR,KOR,KUR,LAO,LAV,LIT,LTZ,MAC,MAL,MAY,MLT,MAO,MOL,MON,NEP,NOR,ORI,OSS,PAN,PER,POL,POR,PUS,RAJ,RUM,RUS,SMO,SAN,SRD,SCC,SCR,SND,SIN,SLO,SLV,SOM,SPA,SWA,SWE,SYR,TGK,TAM,TEL,THA,TIB,TUR,UKR,URD,UZB,VIE,WEL,YID",
    "Default"   =>  "AFR",
);
$additionaldomainfields[".net"] = $additionaldomainfields[".com"];


// .ORG
$additionaldomainfields[".org"][] = array
(
    "Name"      =>  "IDN Script",
    "LangVar"   =>  "idnScript",
    "Type"      =>  "dropdown",
    "Options"   =>  "ZH­TW,ZH­CN,DA,DE,HU,IS,KO,LV,LT,PL,ES,SV,BS,BG,BE,MK,RU,SR,UK",
    "Default"   =>  "ZH­TW",
);


// .CAT
$additionaldomainfields[".cat"][] = array
(
    "Name"      =>  "intendedUse",
    "LangVar"   =>  "intendedUse",
    "Type"      =>  "text",
    "Required"  =>  true,
);
