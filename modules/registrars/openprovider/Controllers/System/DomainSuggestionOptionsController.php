<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WeDevelopCoffee\wPower\Controllers\BaseController;

/**
 * Class DomainSuggestionOptionsController
 *
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DomainSuggestionOptionsController extends BaseController
{
    /**
     * Generate the configuration array.
     * @return array|mixed
     */
    public function getConfig()
    {
        // Get the basic data.
        return $this->getConfigArray();
    }

    /**
     * The configuration array base.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array
        (
            "preferredLanguage" => array
            (
                "FriendlyName"  => "Preferred language",
                "Type"          => "dropdown",
                "Options"       => $this->getLanguageOptions(),
                "Description"   => "Choose the language for which suggestions will be provided",
                "Default"       => "eng",
            ),
            "sensitive" => array
            (
                "FriendlyName"  => "Block explicit language",
                "Type"          => "yesno",
                "Description"   => "Return only suggestions which do not contain explicit language",
                "Default"       => "yes",
            ),
        );
    }

    /**
     * Array of preferred language for tlds
     *
     * @return string[]
     */
    protected function getLanguageOptions()
    {
        return array(
            "eng" => "English",
            "fre" => "French",
            "spa" => "Spanish",
            "ita" => "Italian",
            "por" => "Portuguese",
            "ger" => "German",
            "dut" => "Dutch",
            "tur" => "Turkish",
            "vie" => "Vietnamese",
            "chi" => "Mandarin",
            "jpn" => "Japanese",
            "kor" => "Korean",
            "hin" => "Hindi",
            "ind" => "Indonesian",
        );
    }
}