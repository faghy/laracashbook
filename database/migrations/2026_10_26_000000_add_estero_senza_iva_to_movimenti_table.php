<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Segna i movimenti esteri senza IVA.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimenti', function (Blueprint $table) {
            $table->boolean('estero_senza_iva')->default(false)->after('importo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimenti', function (Blueprint $table) {
            $table->dropColumn('estero_senza_iva');
        });
    }
};
