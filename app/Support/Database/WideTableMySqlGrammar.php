<?php

namespace App\Support\Database;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Fluent;

/**
 * Gramatyka MySQL dla konwersji z SQLite: w „szerokich” tabelach (np. site_settings — ponad 150 kolumn
 * string) zwykłe VARCHAR(255) przekroczyłyby limit 65 535 bajtów na wiersz („Row size too large”).
 * Tam kolumny string bez indeksu tworzymy jako TEXT (poza limitem wiersza), a wartość domyślną
 * zapisujemy wyrażeniem ('…') — tak TEXT z domyślną wartością przyjmują MySQL 8.0.13+ i MariaDB 10.2+.
 * Kolumny z indeksem zostają VARCHAR (TEXT w indeksie wymagałby długości prefiksu).
 */
class WideTableMySqlGrammar extends MySqlGrammar
{
    /** @var list<string> */
    public array $wideTables = [];

    protected function getColumn(Blueprint $blueprint, $column)
    {
        $this->widen($blueprint, $column);

        return parent::getColumn($blueprint, $column);
    }

    public function compileChange(Blueprint $blueprint, Fluent $command)
    {
        $this->widen($blueprint, $command->column);

        return parent::compileChange($blueprint, $command);
    }

    private function widen(Blueprint $blueprint, Fluent $column): void
    {
        if ($column->type !== 'string' || ! in_array($blueprint->getTable(), $this->wideTables, true) || $this->indexed($blueprint, $column)) {
            return;
        }

        $column->type = 'text';
        if ($column->default !== null && ! $column->default instanceof Expression) {
            $value = is_bool($column->default) ? (string) (int) $column->default : (string) $column->default;
            $column->default = new Expression("('".str_replace(['\\', "'"], ['\\\\', "''"], $value)."')");
        }
    }

    private function indexed(Blueprint $blueprint, Fluent $column): bool
    {
        if ($column->unique || $column->index || $column->primary || $column->fulltext) {
            return true;
        }
        foreach ($blueprint->getCommands() as $command) {
            if (in_array($command->name, ['index', 'unique', 'primary', 'fulltext', 'spatialIndex'], true)
                && in_array($column->name, (array) $command->columns, true)) {
                return true;
            }
        }

        return false;
    }
}
