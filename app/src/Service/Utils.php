<?php
namespace App\Service;

class Utils
{
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
