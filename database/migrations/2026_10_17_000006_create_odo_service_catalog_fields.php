<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_categorias_servicios')) {
            Schema::create('odo_categorias_servicios', function (Blueprint $table) {
                $table->id('id_categoria');
                $table->string('nombre', 100)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
                $table->timestamps();
            });
        }

        $columns = [
            'id_categoria' => fn (Blueprint $table) => $table->unsignedBigInteger('id_categoria')->nullable()->after('id_servicio'),
            'forma_cobro' => fn (Blueprint $table) => $table->enum('forma_cobro', ['PROCEDIMIENTO', 'PIEZA', 'SESION'])->default('PROCEDIMIENTO')->after('precio_base'),
            'requiere_pieza' => fn (Blueprint $table) => $table->boolean('requiere_pieza')->default(false)->after('forma_cobro'),
            'requiere_caras' => fn (Blueprint $table) => $table->boolean('requiere_caras')->default(false)->after('requiere_pieza'),
            'sesiones_estimadas' => fn (Blueprint $table) => $table->unsignedSmallInteger('sesiones_estimadas')->nullable()->after('duracion_minutos'),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('odo_servicios', $column)) {
                Schema::table('odo_servicios', $definition);
            }
        }

        $foreignKeyExists = collect(Schema::getForeignKeys('odo_servicios'))
            ->contains(fn (array $foreignKey) => in_array('id_categoria', $foreignKey['columns'] ?? [], true));

        if (! $foreignKeyExists) {
            Schema::table('odo_servicios', function (Blueprint $table) {
                $table->foreign('id_categoria', 'fk_odo_servicios_categoria')
                    ->references('id_categoria')
                    ->on('odo_categorias_servicios')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('odo_servicios')) {
            $foreignKeyExists = collect(Schema::getForeignKeys('odo_servicios'))
                ->contains(fn (array $foreignKey) => ($foreignKey['name'] ?? null) === 'fk_odo_servicios_categoria');

            if ($foreignKeyExists) {
                Schema::table('odo_servicios', function (Blueprint $table) {
                    $table->dropForeign('fk_odo_servicios_categoria');
                });
            }

            foreach (['id_categoria', 'forma_cobro', 'requiere_pieza', 'requiere_caras', 'sesiones_estimadas'] as $column) {
                if (Schema::hasColumn('odo_servicios', $column)) {
                    Schema::table('odo_servicios', fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }

        Schema::dropIfExists('odo_categorias_servicios');
    }
};
