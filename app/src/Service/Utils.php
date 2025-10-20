<?php
namespace App\Service;

class Utils
{
    /**
     * Uncountable words - same form in singular and plural
     */
    private const UNCOUNTABLE = [
        'audio', 'bison', 'cattle', 'chassis', 'compensation', 'coreopsis',
        'data', 'deer', 'education', 'emoji', 'equipment', 'evidence', 'feedback',
        'firmware', 'fish', 'furniture', 'gold', 'hardware', 'information',
        'jedi', 'kin', 'knowledge', 'love', 'metadata', 'money', 'moose',
        'news', 'nutrition', 'offspring', 'plankton', 'pokemon', 'police',
        'rain', 'rice', 'series', 'sheep', 'software', 'species', 'swine',
        'traffic', 'wheat'
    ];

    /**
     * Irregular plurals - words that don't follow standard rules
     * Format: singular => plural
     */
    private const IRREGULAR_PLURALS = [
        'child' => 'children',
        'person' => 'people',
        'man' => 'men',
        'woman' => 'women',
        'tooth' => 'teeth',
        'foot' => 'feet',
        'mouse' => 'mice',
        'goose' => 'geese',
        'ox' => 'oxen',
        'leaf' => 'leaves',
        'life' => 'lives',
        'knife' => 'knives',
        'wife' => 'wives',
        'half' => 'halves',
        'self' => 'selves',
        'elf' => 'elves',
        'loaf' => 'loaves',
        'potato' => 'potatoes',
        'tomato' => 'tomatoes',
        'cactus' => 'cacti',
        'focus' => 'foci',
        'fungus' => 'fungi',
        'nucleus' => 'nuclei',
        'syllabus' => 'syllabi',
        'analysis' => 'analyses',
        'diagnosis' => 'diagnoses',
        'oasis' => 'oases',
        'thesis' => 'theses',
        'crisis' => 'crises',
        'phenomenon' => 'phenomena',
        'criterion' => 'criteria',
        'datum' => 'data'
    ];

    static public function diep($x=''){
        echo '<pre>';
        echo print_r($x,true);
        die();
    }

    static public function snakeToCamelCase(string $input, bool $ucFirst=true): string
    {
        $x = \lcfirst(\str_replace('_', '', \ucwords($input, '_')));
        if($ucFirst) $x = ucfirst($x);

        return $x;
    }

    static public function camelToSnakeCase(string $input, bool $ucFirst=true): string
    {
        $x = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
        if($ucFirst) $x = ucfirst($x);
        return $x;
    }

    static public function stringToSnake(string $input, bool $capitalize = false): string
    {
        // Store original for checking all-caps 2-letter words
        $original = $input;

        // Convert to ASCII, removing accents
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);

        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text);

        // Convert to lowercase
        $text = strtolower($text);

        // Remove leading/trailing underscores
        $text = trim($text, '_');

        // Replace multiple consecutive underscores with single underscore
        $text = preg_replace('/_+/', '_', $text);

        // Capitalize first letter of each word (if word has more than 2 letters)
        // Or keep all caps if original 2-letter word was all caps
        if ($capitalize) {
            $words = explode('_', $text);
            $words = array_map(function($word) use ($original) {
                if (strlen($word) > 2) {
                    return ucfirst($word);
                } elseif (strlen($word) === 2) {
                    // Check if this 2-letter word appears as all caps in original
                    if (preg_match('/\b' . strtoupper($word) . '\b/', $original)) {
                        return strtoupper($word);
                    }
                }
                return $word;
            }, $words);
            $text = implode('_', $words);
        }

        return $text ?: 'unnamed';
    }

    static public function stringToSlug(string $input): string
    {
        // Comprehensive transliteration map for accented characters
        $transliteration = [
            // Portuguese/Spanish
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            // Uppercase versions
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        // Apply transliteration
        $text = strtr($input, $transliteration);

        // Fallback: use iconv for any remaining special characters
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text);

        // Convert to lowercase
        $text = strtolower($text);

        // Remove leading/trailing underscores
        $text = trim($text, '_');

        // Replace multiple consecutive underscores with single underscore
        $text = preg_replace('/_+/', '_', $text);

        return $text ?: 'unnamed';
    }
    /**
     * Convert camelCase to PascalCase (ucfirst while preserving camelCase)
     * Example: accountManager -> AccountManager
     *
     * @param string $input The camelCase string
     * @return string PascalCase string
     */
    static public function toPascalCase(string $input): string
    {
        return ucfirst($input);
    }

    /**
     * Convert any string to proper camelCase
     * Example: account_manager -> accountManager, AccountManager -> accountManager
     *
     * @param string $input The input string
     * @param bool $ucFirst Whether to capitalize first letter (PascalCase)
     * @return string Properly formatted camelCase string
     */
    static public function toCamelCase(string $input, bool $ucFirst = false): string
    {
        // If contains underscores, use snakeToCamelCase
        if (strpos($input, '_') !== false) {
            return self::snakeToCamelCase($input, $ucFirst);
        }

        // Already camelCase, just handle ucFirst
        return $ucFirst ? ucfirst($input) : lcfirst($input);
    }

    /**
     * Convert singular English word to plural
     * Implements comprehensive English pluralization rules
     *
     * Based on Laravel Inflector and Doctrine patterns
     *
     * @param string $word The singular word
     * @return string The plural form
     */
    static public function toPlural(string $word): string
    {
        if (empty($word)) {
            return $word;
        }

        // Preserve original casing for later
        $firstCharUpper = ctype_upper($word[0]);
        $lowerWord = strtolower($word);

        // Check uncountable words
        if (in_array($lowerWord, self::UNCOUNTABLE)) {
            return $word;
        }

        // Check irregular plurals
        if (isset(self::IRREGULAR_PLURALS[$lowerWord])) {
            return $firstCharUpper ? ucfirst(self::IRREGULAR_PLURALS[$lowerWord]) : self::IRREGULAR_PLURALS[$lowerWord];
        }

        // Words ending in -us -> -i (Latin plurals)
        if (preg_match('/(.*)us$/i', $word, $matches)) {
            return $matches[1] . 'i';
        }

        // Words ending in -is -> -es
        if (preg_match('/(.*)is$/i', $word, $matches)) {
            return $matches[1] . 'es';
        }

        // Words ending in -on -> -a (Greek plurals)
        if (preg_match('/(.*)on$/i', $word, $matches)) {
            return $matches[1] . 'a';
        }

        // Words ending in consonant + y -> -ies
        if (preg_match('/([^aeiou])y$/i', $word, $matches)) {
            return $matches[1] . 'ies';
        }

        // Words ending in -o (preceded by consonant) -> -oes
        if (preg_match('/([^aeiou])o$/i', $word, $matches)) {
            // Exceptions that just add -s
            $exceptions = ['photo', 'piano', 'halo'];
            if (!in_array($lowerWord, $exceptions)) {
                return $matches[1] . 'oes';
            }
        }

        // Words ending in -f or -fe -> -ves
        if (preg_match('/(.*)fe?$/i', $word, $matches)) {
            return $matches[1] . 'ves';
        }

        // Words ending in -s, -ss, -sh, -ch, -x, -z -> -es
        if (preg_match('/(s|ss|sh|ch|x|z)$/i', $word)) {
            return $word . 'es';
        }

        // Default: add -s
        return $word . 's';
    }

    /**
     * Convert plural English word to singular
     * Implements comprehensive English singularization rules
     *
     * Based on Laravel Inflector and Doctrine patterns
     *
     * @param string $word The plural word
     * @return string The singular form
     */
    static public function toSingular(string $word): string
    {
        if (empty($word)) {
            return $word;
        }

        // Preserve original casing for later
        $firstCharUpper = ctype_upper($word[0]);
        $lowerWord = strtolower($word);

        // Check uncountable words (use shared constant)
        if (in_array($lowerWord, self::UNCOUNTABLE)) {
            return $word;
        }

        // Check irregular plurals (reverse the mapping: plural => singular)
        $irregularReversed = array_flip(self::IRREGULAR_PLURALS);
        if (isset($irregularReversed[$lowerWord])) {
            return $firstCharUpper ? ucfirst($irregularReversed[$lowerWord]) : $irregularReversed[$lowerWord];
        }

        // Words ending in -i -> -us (Latin plurals)
        if (preg_match('/(.*)i$/i', $word, $matches)) {
            return $matches[1] . 'us';
        }

        // Words ending in -es (from -is)
        if (preg_match('/(.*)([aeiou])ses$/i', $word, $matches)) {
            return $matches[1] . $matches[2] . 'sis';
        }

        // Words ending in -a -> -on (Greek plurals)
        if (preg_match('/(.*)a$/i', $word, $matches)) {
            return $matches[1] . 'on';
        }

        // Words ending in -ies -> -y
        if (preg_match('/(.*)ies$/i', $word, $matches)) {
            return $matches[1] . 'y';
        }

        // Words ending in -ves -> -f or -fe
        if (preg_match('/(.*)ves$/i', $word, $matches)) {
            return $matches[1] . 'f';
        }

        // Words ending in -oes -> -o
        if (preg_match('/(.*)oes$/i', $word, $matches)) {
            return $matches[1] . 'o';
        }

        // Words ending in -ses, -xes, -zes, -ches, -shes -> remove -es
        if (preg_match('/(.*)(s|x|z|ch|sh)es$/i', $word, $matches)) {
            return $matches[1] . $matches[2];
        }

        // Words ending in -sses -> -ss
        if (preg_match('/(.*)sses$/i', $word, $matches)) {
            return $matches[1] . 'ss';
        }

        // Words ending in -s (but not -ss) -> remove -s
        if (preg_match('/(.*)([^s])s$/i', $word, $matches)) {
            return $matches[1] . $matches[2];
        }

        // If no rule matched, return as-is
        return $word;
    }

    static public function isNullEmpty(?string $input = '', string $default=''):string
    {
        if(is_null($input)) return $default;
        else if($input == null) return $default;
        else if($input == '') return $default;
        else return $input;
    }
    static public function isNullZero(null|float|int $input=0):float|int
    {
        if(is_null($input)) return 0;
        else if($input == null) return 0;
        else if($input == '') return 0;
        else if($input == 0) return 0;
        else return $input;
    }
    static public function toMinute(?DateTime $d):int
    {
        if($d instanceof DateTime){
            return (int) ((int) $d->format('H')) * 60 + ((int) $d->format('i'));
        }
        return 0;
    }
    static public function toHour(?DateTime $d):float
    {
        if($d instanceof DateTime){
            return (floatval($d->format('i')) / 60) + (float) ((float) $d->format('H'));
        }
        return 0;
    }
}
