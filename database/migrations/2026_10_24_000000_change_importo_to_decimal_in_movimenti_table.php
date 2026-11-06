<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gli importi in euro vanno salvati come decimal per evitare errori di arrotondamento del float.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimenti', function (Blueprint $table) {
            $table->decimal('importo', 12, 2)->change();
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
            $table->float('importo', 10, 2)->change();
        });
    }
};
