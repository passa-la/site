<?php

namespace App\Console;

use App\Domain\Transit\Change;

final class Report
{
    public const string CHANGED = '<!-- changed -->';

    public static function of(?Change $c): string
    {
        if (! $c) {
            return "## Linhas do Grande Recife\n\nO feed não mudou desde a última importação.\n";
        }

        $out = [
            '## Linhas do Grande Recife',
            '',
            "Feed `{$c->version}` · {$c->total} linhas de ônibus",
            '',
            self::list('Entraram', $c->added),
            self::list('Saíram', $c->removed),
        ];

        if (! $c->empty()) {
            $out[] = '';
            $out[] = self::CHANGED;
        }

        return implode("\n", $out)."\n";
    }

    private static function list(string $title, array $codes): string
    {
        return $codes
            ? sprintf('**%s (%d):** %s  ', $title, count($codes), implode(', ', $codes))
            : "**{$title}:** nenhuma  ";
    }
}
