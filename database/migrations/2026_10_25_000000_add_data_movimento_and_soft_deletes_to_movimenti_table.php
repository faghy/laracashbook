<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa la data del movimento (scelta dall'utente) da created_at (data di inserimento)
     * e aggiunge il cestino (soft delete).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimenti', function (Blueprint $table) {
            $table->date('data_movimento')->nullable()->after('importo');
            $table->softDeletes();
        });

        // Finora la data del movimento era salvata in created_at
        DB::table('movimenti')->update(['data_movimento' => DB::raw('DATE(created_at)')]);

        Schema::table('movimenti', function (Blueprint $table) {
            $table->date('data_movimento')->nullable(false)->change();
            $table->index(['entity_id', 'data_movimento']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // La versione precedente usa created_at come data del movimento
        DB::table('movimenti')->update(['created_at' => DB::raw('data_movimento')]);

        // Prima del cestino l'eliminazione era definitiva
        DB::table('movimenti')->whereNotNull('deleted_at')->delete();

        // La foreign key su entity_id usa l'indice composto: prima le serve un indice proprio
        $needsIndex = ! Schema::hasIndex('movimenti', ['entity_id']);

        Schema::table('movimenti', function (Blueprint $table) use ($needsIndex) {
            if ($needsIndex) {
                $table->index('entity_id');
            }
            $table->dropIndex(['entity_id', 'data_movimento']);
            $table->dropColumn(['data_movimento', 'deleted_at']);
        });
    }
};
