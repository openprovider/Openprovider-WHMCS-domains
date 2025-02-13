<?php
namespace VIISON\AddressSplitter;

use VIISON\AddressSplitter\Exceptions\SplittingException;

/**
 * @copyright Copyright (c) 2017 VIISON GmbH
 */
class AddressSplitter
{
    private static $generalNumberPrefixes = array(
        // English
        'No [.:]?',
        'Nos ([.:]|\s)',
        'Number \s',

        // German
        'Nr [.:]?',
        'Nummer \s',

        // Other
        '№ [.:]?',
        'Nº [.:]?',
        'n° [.:]?'
    );

    private static $additionalHouseNumberPrefixes = array(
        // German
        'Hausnummer \s',
        'Hausnr [.:]?'
    );

    private static function getHouseNumberPrefixes()
    {
        return self::getRegexForNumberPrefixes(self::$generalNumberPrefixes + self::$additionalHouseNumberPrefixes);
    }

    private static function getGeneralNumberPrefixes()
    {
        return self::getRegexForNumberPrefixes(self::$generalNumberPrefixes);
    }

    /**
     * Creates an anonymous group matching the supplied alternative regular expressions for number prefixes.
     *
     * The generated regular expression
     *
     * * assumes 'x' and 'u' pattern modifiers,
     *
     * * assumes each alternative must either be at the start of the string or be preceded by whitespace,
     *
     * * assumes each alternative to be case insensitive,
     *
     * * checks that the prefix will be succeeded by a number (following optional spaces).
     *
     * @param array $numberPrefixes a list of regular expressions (assuming 'x' and 'u' flags)
     * @return string a regular expression for an anonymous group matching the alternatives supplied in the argument
     */
    private static function getRegexForNumberPrefixes(array $numberPrefixes)
    {
         return self::concatenateRegexAlternatives(array_map(
            function ($numberPrefix) {
                return '(?: ^ | (?<=\s)) (?i: ' . $numberPrefix . ' (?= \s* \pN+) )';
            },
            $numberPrefixes
        ));
    }

    private static function concatenateRegexAlternatives(array $alternatives)
    {
        return '(?:' . implode('|', $alternatives) . ')';
    }

    /**
     * This function splits an address line like for example "Pallaswiesenstr. 45 App 231" into its individual parts.
     * Supported parts are additionToAddress1, streetName, houseNumber and additionToAddress2. AdditionToAddress1
     * and additionToAddress2 contain additional information that is given at the start and the end of the string, respectively.
     * Unit tests for testing the regular expression that this function uses exist over at https://regex101.com/r/vO5fY7/1.
     * More information on this functionality can be found at http://blog.viison.com/post/115849166487/shopware-5-from-a-technical-point-of-view#address-splitting.
     *
     * @param string $address
     * @return array
     * @throws SplittingException
     */
    public static function splitAddress($address, $country = '')
    {
        $country = is_string($country) ? trim($country) : '';

        $houseNumberPrefixes = self::getHouseNumberPrefixes();
        $numberPrefixes = self::getGeneralNumberPrefixes();

        /* Matching this group signifies the following text is part of
         * additionToAddress2.
         *
         * See [1] for some of the English language stop words and abbreviations.
         *
         * [1] <https://web.archive.org/web/20180410130330/http://maf.directory/zp4/abbrev.html>
         */
        $addition2Introducers = '(?:

            # {{{ Additions relating to who (a natural person) is addressed

            \s+ [Cc] \s* \/ \s* [Oo] \s
            | ℅
            | \s+ care \s+ of \s+

            # German, Swiss, Austrian
            | \s+ (?: p|p.\s*|per\s+ ) (?: A|A.|Adr.|(?<=\s)Adresse ) \s
            | \s+ p. \s* A. \s
            | \s+ (?: z | z.\s* | zu\s+ ) (?: Hd|Hd.|(?<=\s)Händen|(?<=\s)Haenden|(?<=\s)Handen) \s+

            ## o. V. i. A. = oder Vertreter im Amt
            | \s+ (?: o | o.\s* | oder\s+ )
                (?: V | V.\s* | (?<=\s)Vertreter\s+ )
                (?: i | i.\s* | (?<=\s)im\s+ )
                (?: A | A.\s* | (?<=\s)Amt\s+ )

            # }}}
            # {{{ Additions which further specify more precisely the location

            | \s+ (?: Haus ' . $numberPrefixes . '? ) \s
            | \s+ (?: WG | W\.G\. | WG\. | Wohngemeinschaft ) ' . $numberPrefixes .'? ($ | \s)
            | \s+ (?: [Aa]partment | APT \.? | Apt \.? ) ' . $numberPrefixes .'? \s
            | \s+ (?: [Ff]lat ) ' . $numberPrefixes .'? \s
            | (?: # Numeric-based location specifiers (e.g., "3. Stock"):
                \s+
                (?:
                    [\p{N}]+ # A number, …
                    (?i: st | nd | rd | th)? # …, optionally followed by an English number suffix
                    \.? # …, followed by an optional dot,
                    \s* # …, followed by optional spacing
                )?
                (?: # Specifying category:
                    (?i: Stock | Stockwerk)
                    | App \.? | Apt \.? | (?i: Appartment | Apartment)
                )
                # At the end of the string or followed by a space
                (?: $ | \s)
            )
            | (?:
                \s+ (?:
                    # English language stop words wrt location from source [1]
                    # (extracted only those which may not be _exclusively_ part of
                    # street names):
                    | ANX \.? | (?i: ANNEX)
                    | APT \.? | (?i: APARTMENT)
                    | ARC \.? | (?i: ARCADE)
                    | AVE \.? | (?i: AVENUE)
                    | BSMT \.? | (?i: BASEMENT)
                    | BLDG \.? | (?i: BUILDING)
                    | CP \.? | (?i: CAMP)
                    | COR \.? | (?i: CORNER)
                    | CORS \.? | (?i: CORNERS)
                    | CT \.? | (?i: COURT)
                    | CTS \.? | (?i: COURTS)
                    | DEPT \.? | (?i: DEPARTMENT)
                    | DV \.? | (?i: DIVIDE)
                    | EST \.? | (?i: ESTATE)
                    | EXT \.? | (?i: EXTENSION)
                    | FRY \.? | (?i: FERRY)
                    | FLD \.? | (?i: FIELD)
                    | FLDS \.? | (?i: FIELDS)
                    | FLT \.? | (?i: FLAT)
                    | FL \.? | (?i: FLOOR)
                    | FRNT \.? | (?i: FRONT)
                    | GDNS \.? | (?i: GARDEN)
                    | GDNS \.? | (?i: GARDENS)
                    | GTWY \.? | (?i: GATEWAY)
                    | GRN \.? | (?i: GREEN)
                    | GRV \.? | (?i: GROVE)
                    | HNGR \.? | (?i: HANGER)
                    | HBR \.? | (?i: HARBOR)
                    | HVN \.? | (?i: HAVEN)
                    | KY \.? | (?i: KEY)
                    | LBBY \.? | (?i: LOBBY)
                    | LCKS \.? | (?i: LOCK)
                    | LCKS \.? | (?i: LOCKS)
                    | LDG \.? | (?i: LODGE)
                    | MNR \.? | (?i: MANOR)
                    | OFC \.? | (?i: OFFICE)
                    | PKWY \.? | (?i: PARKWAY)
                    | PH \.? | (?i: PENTHOUSE)
                    | PRT \.? | (?i: PORT)
                    | RADL \.? | (?i: RADIAL)
                    | RM \.? | (?i: ROOM)
                    | SPC \.? | (?i: SPACE)
                    | SQ \.? | (?i: SQUARE)
                    | STA \.? | (?i: STATION)
                    | STE \.? | (?i: SUITE)
                    | TER \.? | (?i: TERRACE)
                    | TRAK \.? | (?i: TRACK)
                    | TRL \.? | (?i: TRAIL)
                    | TRLR \.? | (?i: TRAILER)
                    | TUNL \.? | (?i: TUNNEL)
                    | VW \.? | (?i: VIEW)
                    | VIS \.? | (?i: VISTA)

                    # Custom custom additions:
                    | (?i: Story | Storey)
                    | LVL \.? | (?i: Level)
                )
                ' . $numberPrefixes . '?
                # May optionally be followed directly by a number+letter
                # combination (e.g., "LVL3C"):
                (?: [\p{N}]+[\p{L}]* )?
                # Occurs at the end of the string or followed by a space:
                ($ | \s)
            )

            # Heuristic to match location specifiers. These must not be
            # conflated with house number extensions as in "12 AB". Hence
            # our heuristic is at least 3 letters with the first letter being
            # spelled as a capital. E.g., it would match "Haus", "Gebäude" or
            # "Arbeitspl.", but not "AAB".
            | \s+ ( [\p{Lu}\p{Lt}] [\p{Ll}\p{Lo}]{2,}  \.? ) ($ | \s)

            # }}}
        )';
        // Regex for addresses in the United States (House number first)
        $regex_US = '/
            (?P<C_House_number>\d+)\s+                     # House number
            (?P<C_Street_name>(?:[a-zA-Z]+\s?)+?)\s       # Street name
            (?P<C_Addition_to_address_2>.+)?              # Additional address information
            \s*\Z # Trim white spaces at the end
            /xu';

        // Regex for non-US addresses (Street name first, reintegrating addition2Introducers)
        $regex_Default = '
        /\A\s*
        (?: #########################################################################
            # Option A: [<Addition to address 1>] <House number> <Street name>      #
            # [<Addition to address 2>]                                             #
            #########################################################################
            (?:(?P<A_Addition_to_address_1>.*?),\s*)? # Addition to address 1
            (?: ' . $houseNumberPrefixes . ' \s*)?
            (?P<A_House_number_match>
                 (?P<A_House_number_base>
                    \pN+(\s+\d+\/\d+)?
                 )
                 (?:
                    \s*[\-\/\.]?\s*
                    (?P<A_House_number_extension>(?:[a-zA-Z\pN]){1,2})
                    \s+
                 )?
            )
        \s*,?\s*
            (?P<A_Street_name>(?:[a-zA-Z]\s*|\pN\pL{2,}\s\pL)\S[^,#]*?(?<!\s)) # Street name
        \s*(?:(?:[,\/]|(?=\#))\s*(?!\s* ' . $houseNumberPrefixes . ')
            (?P<A_Addition_to_address_2>(?!\s).*?))? # Addition to address 2
        |   #########################################################################
            # Option B: [<Addition to address 1>] <Street name> <House number>      #
            # [<Addition to address 2>]                                             #
            #########################################################################
            (?:(?P<B_Addition_to_address_1>.*?),\s*(?=.*[,\/]))? # Addition to address 1
            (?!\s* ' . $houseNumberPrefixes . ')
            (?P<B_Street_name>[^0-9# ]\s*\S(?:[^,#](?!\b\pN+\s))*?(?<!\s)) # Street name
        \s*[\/,]?\s*(?:\s ' . $houseNumberPrefixes . ')?\s*
            (?P<B_House_number_match>
                 (?P<B_House_number_base>
                    \pN+
                 )
                 (?:
                    (?: \s*[\-\/]\s* )*
                    (?P<B_House_number_extension>
                        (?!' . $addition2Introducers .')
                        \s*[\pL\pN]+
                    )
                    (?:
                        (?!' . $addition2Introducers .')
                        \s* [\-\/] \s*
                        [\pL\pN]+
                    )*
                 )?
            ) # House number
            (?:
                (?:\s*[-,\/]|(?=\#)|\s)
                \s*
                (?!\s* ' . $houseNumberPrefixes . ' )
                \s*
                (?P<B_Addition_to_address_2>(?!\s).*?)
            )?
        )
        \s*\Z/xu';

        $regex = ($country === 'US') ? $regex_US : $regex_Default;

        $result = preg_match($regex, $address, $matches);
        if ($result === 0) {
            throw new SplittingException(SplittingException::CODE_ADDRESS_SPLITTING_ERROR, $address);
        } elseif ($result === false) {
            throw new \RuntimeException(sprintf('Error occurred while trying to split address \'%s\'', $address));
        }

        if ($country === 'US' && !empty($matches['C_Street_name'])) {
            return array(
                'additionToAddress1' => '',
                'streetName' => $matches['C_Street_name'],
                'houseNumber' => $matches['C_House_number'],
                'houseNumberParts' => self::splitHouseNumber($matches['C_House_number']),
                'additionToAddress2' => isset($matches['C_Addition_to_address_2']) ? $matches['C_Addition_to_address_2'] : ''
            );
        }else if (!empty($matches['A_Street_name']) || !empty($matches['B_Street_name'])) {
            return array(
                'additionToAddress1' => isset($matches['A_Addition_to_address_1']) ? $matches['A_Addition_to_address_1'] : (isset($matches['B_Addition_to_address_1']) ? $matches['B_Addition_to_address_1'] : ''),
                'streetName' => isset($matches['A_Street_name']) ? $matches['A_Street_name'] : $matches['B_Street_name'],
                'houseNumber' => isset($matches['A_House_number_match']) ? $matches['A_House_number_match'] : $matches['B_House_number_match'],
                'houseNumberParts' => isset($matches['A_House_number_match']) ? self::splitHouseNumber($matches['A_House_number_match']) : self::splitHouseNumber($matches['B_House_number_match']),
                'additionToAddress2' => isset($matches['A_Addition_to_address_2']) ? $matches['A_Addition_to_address_2'] : (isset($matches['B_Addition_to_address_2']) ? $matches['B_Addition_to_address_2'] : '')
            );
        }else {
            throw new SplittingException(SplittingException::CODE_ADDRESS_SPLITTING_ERROR, $address);
        }
    }

    /**
     * @param string $houseNumber A house number string to split in base and extension
     * @return array
     * @throws SplittingException
     */
    public static function splitHouseNumber($houseNumber)
    {
        $regex =
            '/
            \A\s* # Trim white spaces at the beginning
            (?: ' . self::getHouseNumberPrefixes() . '?\s*)?
            (?:\#\s*)? # Trim #
            (?<House_number_base>
                [\pN]+ # House Number base (only the number)
            )
            \s*[\/\-\.]?\s* # Separator (optional)
            (?<House_number_extension> # House number extension (optional)
                .*? # Here we allow every character. Everything after the separator is interpreted as extension
            ) 
            \s*\Z # Trim white spaces at the end
            /xu'; // Option (u)nicode and e(x)tended syntax

        $result = preg_match($regex, $houseNumber, $matches);

        if ($result === 0) {
            throw new SplittingException(SplittingException::CODE_HOUSE_NUMBER_SPLITTING_ERROR, $houseNumber);
        } elseif ($result === false) {
            throw new \RuntimeException(sprintf('Error occurred while trying to house number \'%s\'', $houseNumber));
        }

        return array(
            'base' => $matches['House_number_base'],
            'extension' => $matches['House_number_extension']
        );
    }
}
