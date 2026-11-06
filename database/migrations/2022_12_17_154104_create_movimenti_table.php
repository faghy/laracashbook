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
        Schema::create('movimenti', function (Blueprint $table) {
            $table->id();
            $table->ForeignId('categoria')->index('cat_fk');
            $table->foreign('categoria')->on('categorie')->references('cat_id');
            $table->string('descrizione',255);
          //  $table->date('datecreated');
            $table->tinyInteger('entratauscita');
            $table->float('importo',10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movimenti');
    }
};
