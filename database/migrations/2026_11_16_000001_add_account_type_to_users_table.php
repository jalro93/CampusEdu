<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tipo de cuenta (profesor | alumno): solo las cuentas de profesor pueden crear cursos (R03F01). */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('alumno')->after('password');
        });

        // Quien ya es profesor de algún curso conserva la capacidad de crear cursos.
        DB::table('users')
            ->whereIn('id', DB::table('course_user')->where('role', 'profesor')->select('user_id'))
            ->update(['account_type' => 'profesor']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
