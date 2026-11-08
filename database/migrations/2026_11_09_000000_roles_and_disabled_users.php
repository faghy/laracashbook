<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nuovi ruoli: superadmin (tutto), admin (le sue entità), operatore (solo movimenti).
     * I vecchi "responsabile" diventano admin e gli "stagista" operatori.
     * Gli utenti si disattivano (disabled_at) invece di essere cancellati.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operatore')->change();
            $table->timestamp('disabled_at')->nullable()->after('role');
        });

        DB::table('users')->where('role', 'responsabile')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'stagista')->update(['role' => 'operatore']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->whereIn('role', ['superadmin', 'admin'])->update(['role' => 'responsabile']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
            $table->string('role')->default('stagista')->change();
        });
    }
};
