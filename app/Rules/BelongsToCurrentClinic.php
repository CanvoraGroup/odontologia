<?php

namespace App\Rules;

use App\Support\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BelongsToCurrentClinic implements ValidationRule
{
    public function __construct(private string $table, private string $column = 'id') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Schema::hasColumn($this->table, 'id_clinica')) {
            if (! DB::table($this->table)->where($this->column, $value)->exists()) $fail('El valor seleccionado no es válido.');
            return;
        }

        $valid = DB::table($this->table)->where($this->column, $value)->where('id_clinica', TenantContext::requireId())->exists();
        if (! $valid) $fail('El registro seleccionado no pertenece a la empresa activa.');
    }
}
