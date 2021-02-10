<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Beneficiaire;

class test extends Controller
{
	public function test()
		{
			$test=Beneficiaire::all();
			return response()->json(compact('test'));
		}
}