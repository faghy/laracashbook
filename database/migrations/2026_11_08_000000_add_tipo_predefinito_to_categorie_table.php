<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo proposto nei nuovi movimenti della categoria: 1 entrata, 0 uscita, null nessuno.
     * Le categorie estere senza IVA esistenti partono con "uscita".
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categorie', function (Blueprint $table) {
            $table->boolean('tipo_predefinito')->nullable()->after('estero_senza_iva');
        });

        DB::table('categorie')->where('estero_senza_iva', true)->update(['tipo_predefinito' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categorie', function (Blueprint $table) {
            $table->dropColumn('tipo_predefinito');
        });
    }
};
