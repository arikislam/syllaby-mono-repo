<?php

namespace App\Syllaby\Publisher\Publications\Expressions;

use Illuminate\Database\Query\Expression;

class MetricsTypeExpression extends Expression
{
    public function __construct(
        string $slug,
        ?string $columnAlias = null,
        string $keysTable = 'publication_metric_keys',
        string $valuesTable = 'publication_metric_values'
    )
    {
        $query = "SUM(IF($keysTable.slug = '$slug', $valuesTable.value, 0))";

        if ($columnAlias) {
            $query .= " as $columnAlias";
        }

        parent::__construct($query);
    }
}
