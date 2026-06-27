<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class RequestGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if ($definition->generateAdmin) {
            $this->generateAdminRequests($definition);
        }

        if ($definition->generateApi) {
            $this->generateApiRequests($definition);
        }
    }

    protected function generateAdminRequests(CrudDefinition $definition): void
    {
        $this->writeRequest($definition, 'admin_store_request', "{$definition->baseName}StoreRequest");
        $this->writeRequest($definition, 'admin_update_request', "{$definition->baseName}UpdateRequest");
    }

    protected function generateApiRequests(CrudDefinition $definition): void
    {
        $this->writeRequest($definition, 'api_store_request', "{$definition->baseName}ApiStoreRequest");
        $this->writeRequest($definition, 'api_update_request', "{$definition->baseName}ApiUpdateRequest");
        $this->writeRequest($definition, 'api_filter_request', "{$definition->baseName}ApiFilterRequest", false);
    }

    protected function writeRequest(CrudDefinition $definition, string $stub, string $className, bool $withFieldRules = true): void
    {
        $path = app_path("Http/Requests/{$definition->namespacePath}{$className}.php");
        $replacements = $this->replacements($definition);
        $replacements['{{requestClass}}'] = $className;

        $options = ['conditionals' => $this->conditionals($definition)];

        if ($withFieldRules) {
            $storeRules = $this->buildFieldRules($definition, 'store');
            $updateRules = $this->buildFieldRules($definition, 'update');
            $replacements['{{storeRules}}'] = $storeRules;
            $replacements['{{updateRules}}'] = $updateRules;
        }

        $content = $this->renderer->render($stub, $replacements, $options);
        $this->renderer->write($path, $content);
    }

    protected function buildFieldRules(CrudDefinition $definition, string $mode): string
    {
        $rules = '';

        foreach ($definition->fields as $field) {
            $rule = str_replace('{{table}}', $definition->table, $field['rules']);

            if ($mode === 'update') {
                $rule = str_replace('required|', 'sometimes|', $rule);
                $rule = str_replace('required', 'sometimes', $rule);

                if ($field['unique']) {
                    $rule = str_replace(
                        "unique:{$definition->table},{$field['name']}",
                        "unique:{$definition->table},{$field['name']},{\$this->route('{$definition->kebabName}')?->id ?? \$this->route('{$definition->kebabName}')}",
                        $rule
                    );
                }
            }

            $rules .= "            '{$field['name']}' => '{$rule}',\n";
        }

        if ($definition->hasStatus) {
            $statusRule = $mode === 'update' ? 'sometimes|boolean' : 'boolean';
            $rules .= "            'status' => '{$statusRule}',\n";
        }

        return $rules;
    }
}
