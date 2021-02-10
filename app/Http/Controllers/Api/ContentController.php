<?php
namespace App\Http\Controllers\Api;




use App\Country;
use App\City;


class ContentController extends Controller
{
	public function get_contries()
	{
		$countries=Country::all();
		//$data['countries']=$countries->only('id','label');
		return response()->json(compact('countries'));
			}
	/*public function get_cities()
	{
		$cites=City::paginate(5);
		//$data['cities']=$cities->only('id','label');
		return response()->json(compact('cities'));
	}
	public function get_language()
	{
		$languages=Language::paginate(5);
		return response()->json(compact('languages'));
	}
	public function get_roles()
	{
		$roles=Role::paginate(5);
		return response()->json(compact('roles'));
	}*/
}