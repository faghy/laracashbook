<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categorie', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id');
            $table->unique(['descriz_cat', 'entity_id']);
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
            $table->dropColumn('entity_id');
            $table->dropUnique('categorie_descriz_cat_entity_id_unique');
        });
    }
};
