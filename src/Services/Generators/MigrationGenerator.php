<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class MigrationGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        $path = database_path('migrations/' . date('Y_m_d_His') . "_create_{$definition->table}_table.php");

        $columnsLoop = '';
        foreach ($definition->fields as $field) {
            $nullable = $field['nullable'] ? '->nullable()' : '';
            $unique = $field['unique'] ? '->unique()' : '';
            $columnsLoop .= "            \$table->{$field['type']}('{$field['name']}'){$nullable}{$unique};\n";
        }

        if ($definition->hasStatus) {
            $columnsLoop .= "            \$table->boolean('status')->default(true);\n";
        }

        if ($definition->softDeletes) {
            $columnsLoop .= "            \$table->softDeletes();\n";
        }

        $replacements = $this->replacements($definition);
        $replacements['{{fields_loop}}'] = $columnsLoop;
        $replacements['{{tableName}}'] = $definition->table;

        $content = $this->renderer->render('migration', $replacements, [
            'conditionals' => $this->conditionals($definition),
        ]);

        $this->renderer->write($path, $content);
    }
}
