<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorie i cui nuovi movimenti sono, di norma, esteri senza IVA.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categorie', function (Blueprint $table) {
            $table->boolean('estero_senza_iva')->default(false)->after('descriz_cat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categorie', function (Blueprint $table) {
            $table->dropColumn('estero_senza_iva');
        });
    }
};
