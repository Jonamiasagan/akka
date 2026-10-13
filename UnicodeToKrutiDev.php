<?php

declare(strict_types=1);

/**
 * Class UnicodeToKrutiDev
 *
 * Converts Unicode Devanagari (Hindi) text into Kruti Dev 010 legacy ASCII encoding.
 * Implements comprehensive character mapping, conjunct rules, and linguistic
 * repositioning for both short-i (ि) matras and reph (र्).
 */
class UnicodeToKrutiDev
{
    /**
     * Nukta normalization mapping (both decomposed and precomposed Unicode codepoints)
     */
    private const NUKTA_MAP = [
        "\u{0915}\u{093C}" => 'क़', // क़
        "\u{0916}\u{093C}" => 'ख़', // ख़
        "\u{0917}\u{093C}" => 'ग़', // ग़
        "\u{091C}\u{093C}" => 'ज़', // ज़
        "\u{0921}\u{093C}" => 'ड़', // ड़
        "\u{0922}\u{093C}" => 'ढ़', // ढ़
        "\u{092B}\u{093C}" => 'फ़', // फ़
        "\u{092F}\u{093C}" => 'य़', // य़
        "\u{0930}\u{093C}" => 'ऱ', // ऱ
        "\u{0928}\u{093C}" => 'ऩ', // ऩ
    ];

    /**
     * Primary character mapping table: Unicode Devanagari -> Kruti Dev 010
     */
    private const CHAR_MAP = [
        // Punctuation & Symbols
        '‘' => '^', '’' => '*', '“' => 'Þ', '”' => 'ß',
        '(' => '¼', ')' => '½', '{' => '¿', '}' => 'À',
        '=' => '¾', '॥' => 'AA', '।' => 'A', '?' => '\\',
        '-' => '&', 'µ' => '&', '॰' => 'Œ', ',' => ']',
        '.' => '-', '् ' => '~ ',

        // Devanagari Numerals (0-9)
        '०' => 'å', '१' => 'ƒ', '२' => '„', '३' => '…',
        '४' => '†', '५' => '‡', '६' => 'ˆ', '७' => '‰',
        '८' => 'Š', '९' => '‹',

        // Complex Conjuncts & Special Ligatures
        'त्त्' => 'Ù', 'त्त' => 'Ùk', 'क्त' => 'Dr',
        'दृ' => '–', 'कृ' => '—',
        'ह्न' => 'à', 'ह्य' => 'á', 'हृ' => 'â', 'ह्म' => 'ã',
        'ह्र' => 'ºz', 'ह्' => 'º', 'द्द' => 'í',
        'क्ष्' => '{', 'क्ष' => '{k', 'त्र्' => '«', 'त्र' => '=', 'ज्ञ' => 'K',
        'छ्य' => 'Nî', 'ट्य' => 'Vî', 'ठ्य' => 'Bî', 'ड्य' => 'Mî', 'ढ्य' => '<î',
        'द्य' => '|', 'द्व' => '}', 'श्र' => 'J',
        'ट्र' => 'Vª', 'ड्र' => 'Mª', 'ढ्र' => '<ªª', 'छ्र' => 'Nª',
        'क्र' => 'Ø', 'फ्र' => 'Ý', 'द्र' => 'æ', 'प्र' => 'ç',
        'ग्र' => 'xz', 'रु' => '#', 'रू' => ':', '्र' => 'z',

        // Nukta Consonants (Precomposed and Decomposed forms)
        'फ़्' => '¶', 'फ़' => 'Q', 'क़्' => 'D', 'क़' => 'd', 'क़' => 'd',
        'ख़्' => '[', 'ख़' => '[k', 'ख़्' => '[', 'ख़' => '[k',
        'ग़्' => 'X', 'ग़' => 'x', 'ग़्' => 'X', 'ग़' => 'x',
        'ज़्' => 'T', 'ज़' => 't', 'ज़्' => 'T', 'ज़' => 't',
        'ड़' => 'M+', 'ड़' => 'M+',
        'ढ़' => '<+', 'ढ़' => '<+',
        'फ़्' => '¶', 'फ़' => 'Q', 'फ़्' => '¶', 'फ़' => 'Q',
        'य़' => ';', 'य़' => ';',
        'ऱ' => 'j', 'ऩ' => 'u',

        // Independent Vowels
        'ओ' => 'vks', 'औ' => 'vkS', 'आ' => 'vk', 'अ' => 'v',
        'ई' => 'bZ',  'इ' => 'b',   'उ' => 'm',  'ऊ' => 'Å',
        'ऐ' => ',s',  'ए' => ',',   'ऋ' => '_',

        // Consonants (Half forms & Full forms)
        'क्क' => 'ô',  'क्' => 'D', 'क' => 'd',
        'ख्' => '[',   'ख' => '[k',
        'ग्' => 'X',   'ग' => 'x',
        'घ्' => '?',   'घ' => '?k',
        'ङ' => '³',
        'चै' => 'pkS', 'च्' => 'P', 'च' => 'p',
        'छ' => 'N',
        'ज्' => 'T',   'ज' => 't',
        'झ्' => '÷',   'झ' => '>',
        'ञ' => '¥',
        'ट्ट' => 'ê',  'ट्ठ' => 'ë', 'ट' => 'V', 'ठ' => 'B',
        'ड्ड' => 'ì',  'ड्ढ' => 'ï', 'ड' => 'M', 'ढ' => '<',
        'ण्' => '.',   'ण' => '.k',
        'त्' => 'R',   'त' => 'r',
        'थ्' => 'F',   'थ' => 'Fk',
        'द्ध' => ')',  'द' => 'n',
        'ध्' => '/',   'ध' => '/k',
        'न्' => 'U',   'न' => 'u',
        'प्' => 'I',   'प' => 'i',
        'फ्' => '¶',   'फ' => 'Q',
        'ब्' => 'C',   'ब' => 'c',
        'भ्' => 'H',   'भ' => 'Hk',
        'म्' => 'E',   'म' => 'e',
        'य्' => '¸',   'य' => ';',
        'र' => 'j',
        'ल्' => 'Y',   'ल' => 'y',
        'ळ' => 'G',
        'व्' => 'O',   'व' => 'o',
        'श्' => "'",   'श' => "'k",
        'ष्' => '"',   'ष' => '"k',
        'स्' => 'L',   'स' => 'l',
        'ह' => 'g',

        // Dependent Vowel Signs (Matras) & Modifiers
        'ऑ' => 'v‚', 'ॉ' => '‚',
        'ो' => 'ks', 'ौ' => 'kS',
        'ा' => 'k',  'ी' => 'h',
        'ु' => 'q',  'ू' => 'w',
        'ृ' => '`',  'े' => 's', 'ै' => 'S',
        'ं' => 'a',  'ँ' => '¡', 'ः' => '%', 'ॅ' => 'W',
        'ऽ' => '·',  '्' => '~', '़' => '+',

        // Reph temporary placeholder -> Kruti Dev 'Z'
        '±' => 'Z',
    ];

    /**
     * Convert Unicode Devanagari text to Kruti Dev 010 ASCII text.
     *
     * @param string $text Input Unicode Hindi text
     * @return string Converted Kruti Dev 010 ASCII encoded text
     */
    public static function convert(string $text): string
    {
        if ($text === '') {
            return '';
        }

        // Step 1: Normalize decomposed nukta forms (e.g. क + ़ -> क़)
        $normalized = strtr($text, self::NUKTA_MAP);

        // Step 2: Handle Reph (र् = र + ्) repositioning
        // In Unicode: र् appears before the consonant cluster (e.g., धर्म = ध + र् + म).
        // In Kruti Dev: Reph is placed after the consonant cluster and its upper matra (e.g., èkeZ).
        // Matches: र् + (cluster) + (optional matra) + (optional anusvara/visarga)
        $patternReph = '/\x{0930}\x{094D}((?:[\x{0915}-\x{0939}\x{0958}-\x{095F}]\x{094D})*[\x{0915}-\x{0939}\x{0958}-\x{095F}][\x{093E}\x{0940}\x{0941}\x{0942}\x{0943}\x{0947}\x{0948}\x{094B}\x{094C}\x{0945}\x{0949}\x{093F}]?)([\x{0901}\x{0902}\x{0903}]?)/u';
        $normalized = (string) preg_replace_callback($patternReph, static function (array $matches): string {
            // Cluster + Matra + Reph placeholder (±) + Anusvara
            return $matches[1] . '±' . $matches[2];
        }, $normalized);

        // Step 3: Handle Short-i (ि \x{093F}) matra repositioning
        // In Unicode: Cluster + ि (e.g., किताब = क + ि + त + ा + ब, स्थिति = स् + थ + ि + त + ि).
        // In Kruti Dev: 'f' is typed before the ENTIRE consonant cluster, including half-letters!
        // Matches: Preceding consonant cluster (half consonants + base consonant) followed by ि
        $patternShortI = '/((?:[\x{0915}-\x{0939}\x{0958}-\x{095F}]\x{094D})*[\x{0915}-\x{0939}\x{0958}-\x{095F}])\x{093F}/u';
        $normalized = (string) preg_replace($patternShortI, 'f$1', $normalized);

        // Step 4: Perform dictionary character & conjunct replacements
        return strtr($normalized, self::CHAR_MAP);
    }

    /**
     * Instance wrapper for convert()
     *
     * @param string $text
     * @return string
     */
    public function convertToKrutiDev(string $text): string
    {
        return self::convert($text);
    }
}
