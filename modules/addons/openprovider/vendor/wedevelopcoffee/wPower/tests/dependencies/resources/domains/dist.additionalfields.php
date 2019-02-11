<?php
/*
 **********************************************************************
 *         Additional Domain Fields (aka Extended Attributes)         *
 **********************************************************************
 *                                                                    *
 * This file contains the default additional domain field definitions *
 * for WHMCS.                                                         *
 *                                                                    *
 * We do not recommend editing this file directly. To customise the   *
 * fields, you should create an overrides file.                       *
 *                                                                    *
 * For more information please refer to the online documentation at   *
 *   http://docs.whmcs.com/Additional_Domain_Fields                   *
 *                                                                    *
 **********************************************************************
 */

// .US

$additionaldomainfields[".us"][] = array("Name" => "Nexus Category", "LangVar" => "ustldnexuscat", "Type" => "dropdown", "Options" => "C11,C12,C21,C31,C32", "Default" => "C11",);
$additionaldomainfields[".us"][] = array("Name" => "Nexus Country", "LangVar" => "ustldnexuscountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".us"][] = array("Name" => "Application Purpose", "LangVar" => "ustldapppurpose", "Type" => "dropdown", "Options" => "Business use for profit,Non-profit business,Club,Association,Religious Organization,Personal Use,Educational purposes,Government purposes", "Default" => "Business use for profit",);
