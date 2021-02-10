<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBeneficiaireUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('BeneficiaireUser', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('beneficiaire_id')->unsigned();
            $table->foreign('beneficiaire_id')->references('id')->on('beneficiaires');
            $table->float('montant');
            $table->enum('status', array('encours','validé'));
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
        
        Schema::table('BeneficiaireUser', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['beneficiaire_id']);

        Schema::dropIfExists('BeneficiaireUser');

        });


    }
}
