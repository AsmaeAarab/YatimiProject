<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Carbon\Carbon;

class BeneficiaireController extends Controller
{
	public function store(Request $request)
	{
		if(auth()->user()->id == "admin")
		{
		$beneficiaire = $request->isMethod('put') ? Beneficiaire::findOrFail($request->id) : new Beneficiaire ;
		$beneficiaire_translate= $request->isMethod('put') ? Beneficiaire::findOrFail($request->id) : new Beneficiaire_translate ;

		$beneficiaire->id = $request->input('id');
		$beneficiaire->birthday = $request->input('sex');
		$beneficiare->weight = $request->input('weight');
		$beneficiaire->length = $request->input('length');
		$beneficiaire->city_id = $request->input('city_id');
		$beneficiaire->country_id = $request->input('country_id');
		$beneficiaire->last_school_note =$request->input('last_school_note');
		$beneficiaire_translate->id = $request->input('id');
		$beneficiaire_translate->last_name = $request->input('last_name');
		$beneficiaire_translate->first_name = $request->input('first_name');
		$beneficiaire_translate->father_name = $request->input('father_name');
		$beneficiaire_translate->address = $request->input('address');
		$beneficiaire_translate->biography = $request->input('biography');
		$beneficiaire_translate->school_level = $request->input('school_level');
		$beneficiaire_translate->beneficiaire_id = $beneficiaire->id;
		$beneficiaire_translate->language_id = $request->input('language_id');
		$beneficiaire_translate->mother_name = $request->input('mother_name');
		$beneficiaire_translate->leisure = $request->input('leisure');

		$beneficiaire_translate->save();
		$beneficiaire->save();

		$data['beneficiaire_translate'] = $beneficiaire_translate->only('id','last_name','first_name','father_name','mother_name','address','biography','school_level','beneficiaire_id','language_id','leisure');
		$data['beneficiaire'] =$beneficiaire->only('id','birthday','sex','weight','length','city_id','country_id','last_school_note');
		return Response()->json(compact('data'));

		}
	}

	public function add_Wall(Request $request)

	{ $wall = new walls_beneficiares ;
				
				
				$wall->id = $request->input('id');
				$wall->title = $request->input('title');
				$wall->description = $request->input('description');
				$pic='beneficiaires/images'.md5(uniq(rand(),true)).'jpg';
				$wall->image=Storage::disk('uploads')->put($pic,file_get_contents($request->image));
				$vid='beneficiares/videos'.md5(uniqid(rand(),true)).'mp4';
				$wall->video = Storage::disk('uploads')->put($vid,file_get_contents($request->video));
				$wall->beneficiaire_id= $request->input('beneficiaire_id');
				$wall->language_id= $request->input('language_id');
				$wall->save();

			
		return Response()->json(compact('wall'),200);
	}

}