<?php
/**
 * @author Selcuk Mart
 * 29.07.2022
 * 11:37
 */

namespace App\Helper;

class GeneralHelpers
{

    public function __construct(private readonly mixed $mixed_value)
    {
    }

    public function dump($return = false): ?string
    {
        return $this->customDump($return);
    }

    public function customDump($return = false)
    {
        $dump_str = $this->dumpReturn();
        if ($return) {
            return $dump_str;
        }
        echo $dump_str;
    }

    private function dumpReturn(): string
    {
        $v = $this->mixed_value;
        $output = '<pre>';
        if (is_array($v) || is_object($v)) {
            $output .= print_r($v, true);
        } else {
            if (is_bool($v)) {
                $v = ' Boolean:' . ($v ? "true" : "false");
            }
            $output .= $v;
        }
        $output .= '</pre>';
        return $output;
    }

    public function slug(string $string): string
    {
        $string = strtolower(trim(str_replace(" ", "-", strip_tags($string))));
        if (!empty($string)) {
            $find = [
                "ç",
                "Ç",
                "Ğ",
                "ğ",
                "ı",
                "İ",
                "J",
                "ö",
                "Ö",
                "ş",
                "Ş",
                "ü",
                "Ü",
                '$',
                '€',
                "'",
                "\"",
                "?",
                "!",
                "^",
                "+",
                "%",
                "&",
                "/",
                "\\",
                "{",
                "}",
                "( ",
                " )",
                "[",
                "]",
                "=",
                "*",
                "_",
                ", ",
                ";",
                ":",
                ".",
                "<",
                ">",
                "|",
                "é",
                "’",
                "™",
                "“",
                "”",
                "`",
                "~",
                "#",
                "´",
                '’',
                ', '
            ];
            $replace = [
                "c",
                "c",
                "g",
                "g",
                "i",
                "I",
                "j",
                "o",
                "Ö",
                "s",
                "s",
                "u",
                "U",
                's',
                'e',
                "-",
                "-",
                "-",
                "",
                "",
                "-",
                "-",
                "",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "",
                "",
                "",
                "-",
                "e",
                "",
                "",
                "",
                "",
                "",
                "-",
                "-",
                "",
                '-',
                '-'
            ];
            $string = str_replace([...$find, "  ", "----", "---", "--"], [...$replace, "-", "-", "-", "-"], $string);
            $find = [
                'é',
                'è',
                'ë',
                'ê',
                'É',
                'È',
                'Ë',
                'Ê'
            ];
            $string = str_replace($find, 'e', $string);
            $find = [
                'í',
                'ì',
                'î',
                'ï',
                'I',
                'Í',
                'Ì',
                'Î',
                'Ï',
                'İ'
            ];
            $string = str_replace($find, 'i', $string);
            $find = [
                'ó',
                'ö',
                'Ö',
                'ò',
                'ô',
                'Ó',
                'Ò',
                'Ô'
            ];
            $string = str_replace($find, 'o', $string);
            $find = [
                'á',
                'ä',
                'â',
                'à',
                'â',
                'Ä',
                'Â',
                'Á',
                'À',
                'Â'
            ];
            $string = str_replace($find, 'a', $string);
            $find = [
                'ú',
                'ü',
                'Ü',
                'ù',
                'û',
                'Ú',
                'Ù',
                'Û'
            ];
            $string = str_replace($find, 'u', $string);
            $find = [
                'ç',
                'Ç'
            ];
            $string = str_replace($find, 'c', $string);
            return strtolower(trim($string, "-"));
        }

        return $string;
    }

    public function slugify(string $file): string
    {
        return $this->slug($file);
    }
}