<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBeneficiaireTranslateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('BeneficiaireTranslate', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('last_name',60);
            $table->string('first_name',60);
            $table->string('father_name',60);
            $table->string('mother_name',60);
            $table->string('leisure');
            $table->string('address',120);
            $table->text('biography');
            $table->enum('school_level', array( 'Primary', 'Secondary',))->index();
            $table->integer('beneficaire_id')->unsigned();
            $table->foreign('beneficaire_id')->references('id')->on('beneficaires');
            $table->integer('language_id')->unsigned();
            $table->foreign('language_id')->references('id')->on('languages');
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
        Schema::table('BeneficiaireTranslate', function (Blueprint $table) {
            $table->dropForeign(['beneficiaire_id']);
            $table->dropForeign(['language_id']);

        });

        Schema::dropIfExists('BeneficiaireTranslate');
    }


}
