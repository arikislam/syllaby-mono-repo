<?php

namespace App\Syllaby\Generators\Prompts;

class KeywordPrompt
{
    public static function build(string $keyword, string $type): string
    {
        return str_replace(
            array_keys(self::bindings($keyword, $type)),
            array_values(self::bindings($keyword, $type)),
            self::prompt()
        );
    }

    private static function bindings(string $keyword, string $type): array
    {
        return [
            ':TYPE' => $type,
            ':KEYWORD' => $keyword,
        ];
    }

    private static function prompt(): string
    {
        return <<<EOT
            Provide 10 :TYPE related to the keyword ":KEYWORD" Use the following example to generate the questions:
                1. question: mountains interstellar
                2. search_volume: 1543882 
                3. trend: -0.03
                4. trend_line: 119427,197322,197964,197932,192164,127964,119427,197322,197964,197932,192164,127964
                5. cpc: 0.96
                6. competition: 0.21 
            Ensure each :TYPE includes the following attributes: search volume, trend (with percentage increase), 
            trend line (12 values separated by comma), CPC, and competition level (min: 0.00, max: 1.00). 
            When possible try to get accurate results otherwise ensure the values are randomly changing. 
            Each question should reflect various aspects of the keyword. Return data as a valid flatten json string. 
            No extra formating. Include the json and nothing else.
        EOT;
    }
}
