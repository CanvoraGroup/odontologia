<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('odo_recetas')) {
            if (DB::connection()->getDriverName() === 'mysql') DB::statement("ALTER TABLE odo_recetas MODIFY estado ENUM('EMITIDA','REEMPLAZADA','ANULADA') NOT NULL DEFAULT 'EMITIDA'");
            Schema::table('odo_recetas', function (Blueprint $t) {
                if (!Schema::hasColumn('odo_recetas','id_clinica')) $t->unsignedBigInteger('id_clinica')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_paciente')) $t->unsignedBigInteger('id_paciente')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_odontologo')) $t->unsignedBigInteger('id_odontologo')->nullable();
                if (!Schema::hasColumn('odo_recetas','numero_receta')) $t->string('numero_receta',30)->nullable();
                if (!Schema::hasColumn('odo_recetas','idempotency_key')) $t->uuid('idempotency_key')->nullable();
                if (!Schema::hasColumn('odo_recetas','version')) $t->unsignedInteger('version')->default(1);
                if (!Schema::hasColumn('odo_recetas','fecha_emision')) $t->date('fecha_emision')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_receta_anterior')) $t->unsignedBigInteger('id_receta_anterior')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_usuario_creacion')) $t->unsignedBigInteger('id_usuario_creacion')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_usuario_modificacion')) $t->unsignedBigInteger('id_usuario_modificacion')->nullable();
                if (!Schema::hasColumn('odo_recetas','id_usuario_anulacion')) $t->unsignedBigInteger('id_usuario_anulacion')->nullable();
                if (!Schema::hasColumn('odo_recetas','fecha_anulacion')) $t->timestamp('fecha_anulacion')->nullable();
                if (!Schema::hasColumn('odo_recetas','motivo_anulacion')) $t->text('motivo_anulacion')->nullable();
            });
            DB::statement("UPDATE odo_recetas r JOIN odo_atenciones a ON a.id_atencion=r.id_atencion SET r.id_clinica=a.id_clinica, r.id_paciente=a.id_paciente, r.id_odontologo=a.id_odontologo, r.fecha_emision=COALESCE(r.fecha_emision,DATE(r.created_at)) WHERE r.id_clinica IS NULL");
            $indexes = collect(DB::select('SHOW INDEX FROM odo_recetas'))->pluck('Key_name')->all();
            if (!in_array('uk_odo_recetas_clinica_idempotency', $indexes, true)) Schema::table('odo_recetas', fn(Blueprint $t) => $t->unique(['id_clinica','idempotency_key'],'uk_odo_recetas_clinica_idempotency'));
            if (!in_array('uk_odo_recetas_clinica_numero', $indexes, true)) Schema::table('odo_recetas', fn(Blueprint $t) => $t->unique(['id_clinica','numero_receta'],'uk_odo_recetas_clinica_numero'));
        }
        if (Schema::hasTable('odo_receta_detalles')) Schema::table('odo_receta_detalles', function (Blueprint $t) {
            if (!Schema::hasColumn('odo_receta_detalles','id_clinica')) $t->unsignedBigInteger('id_clinica')->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','orden')) $t->unsignedInteger('orden')->default(1);
            if (!Schema::hasColumn('odo_receta_detalles','presentacion')) $t->string('presentacion',180)->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','via_administracion')) $t->string('via_administracion',120)->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','unidad')) $t->string('unidad',80)->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','cantidad')) $t->decimal('cantidad',10,2)->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','id_usuario_creacion')) $t->unsignedBigInteger('id_usuario_creacion')->nullable();
            if (!Schema::hasColumn('odo_receta_detalles','id_usuario_modificacion')) $t->unsignedBigInteger('id_usuario_modificacion')->nullable();
        });
        DB::statement("UPDATE odo_receta_detalles d JOIN odo_recetas r ON r.id_receta=d.id_receta SET d.id_clinica=r.id_clinica, d.medicamento_texto=COALESCE(d.medicamento_texto,'Medicamento no indicado'), d.presentacion=COALESCE(d.presentacion,'No indicada'), d.via_administracion=COALESCE(d.via_administracion,'No indicada'), d.unidad=COALESCE(d.unidad,'unidad'), d.cantidad=COALESCE(d.cantidad,1) WHERE d.id_clinica IS NULL");
    }
    public function down(): void { }
};
