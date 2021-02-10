<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaiementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('benficaire_user_id')->unsigned();
            $table->foreign('benficaire_user_id')->references('id')->on('benficaire_user');
            $table->float('montant');
            $table->foreign('montant')->references('montant')->on('benficaire_user');
            $table->date('date_paiment');
           $table->timestamp('created_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign('paiements_benficaire_user_id_foreign');
            $table->dropForeign('paiements_montant_foreign');

        });


        Schema::dropIfExists('paiements');
    }
}
