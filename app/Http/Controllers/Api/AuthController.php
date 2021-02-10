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
use DateTime;
use DatePeriod;
use DateInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;

//---------Models--------
use App\User;
use App\BeneficiaireUser;
use App\Beneficiaire;
use App\Country;
use App\City;



class AuthController extends Controller
{
	    public function saveDevice(Request $request)
        {
        $user = auth()->user();
        
        $user->device_token = $request->device_token;
        if($user->save()) {
                $status = TRUE;
                $message = 'token updated successfully';
        }else{
                $status = FALSE;
                $message = 'user not updated';
        }
        $data = array('status' => $status, 'message' => $message);
        return response()->json(compact('data'));
    }
//------------login-------------------
        public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

 
            return response()->json(compact('token'));
        
    }
    //---------------logout------------------
        public function logout() {
        auth()->logout();
        return response()->json();
    }

    //----------register--------------
    public function register(Request $request)
    {	//---------------------------
    	$user_test= User::where(array('email'=>$request->email))->first();
    	if(!empty($user_test))
    	{
    		$data = array('statut' => false,'message' => "cet email existe deja",'user' => null);
    		return response()->json(['token' => null,'data' =>$data]);
    	}
    	//----------------------------
    		$user = new User ;
            $user->id = $request->id;
    		$user->email = $request->email;
    		$user->password = bcrypt($request->password);		
    		$user->role_id=2;
            $user->city_id=$request->city_id;
            $user->lastname =$request->lastname;
            $user->firstname =$request->firstname;
            $user->age =$request->age;
            $user->description =$request->description;
    		$user->save();
    		$data['user']=array('id'=>$user->id,'email'=>$user->email,'role_id'=>$user->role_id,'lastname'=>$user->lastname,'firstname'=>$user->firstname,'age'=>$user->age);
            return response()->json(compact('data'),200);
    	
    }


    public function prise_en_charge(Request $request)
    {
        $beneficiaire_user = new BeneficiaireUser;
        $beneficiaire_user->user_id = auth()->user()->id;
        $beneficiaire_user->beneficiaire_id = $request->beneficiaire_id;
        $beneficiaire_user->montant = $request->montant;
        $beneficiaire_user->status = 'encours';
        $beneficiaire_user->save();
        $status=true;
        return response()->json(array('status' =>$status));
    }
    
        public function getBeneficiaire()
        {  

            $cities=DB::table('cities')->join('cities_translate','cities.id','=','cities_translate.city_id')->select('cities_translate.*');

          $beneficiaires = DB::table('beneficiaires')->join('beneficiairetranslate','beneficiaires.id','=','beneficiairetranslate.beneficaire_id')->join("cities","cities.id",'beneficiaires.city_id')->join('citiestranslate','citiestranslate.city_id','=','cities.id')->select('beneficiaires.age',"citiestranslate.label",'beneficiairetranslate.last_name','beneficiairetranslate.first_name','beneficiairetranslate.language_id')->get();
         
          if(count($beneficiaires))
          {  $data=array();
            foreach ($beneficiaires as $beneficiaire)
            {

                 $data[]=array('last_name' => $beneficiaire->last_name,
                    'first_name' => $beneficiaire->first_name,
                    'city_name'=>$beneficiaire->label,
                    'age' => $beneficiaire->age);
             return response()->json(compact('data'));
         
     }

            }
                     
        }




    public function profile()
    {   
        $data['user']=auth()->user()->only('id','email','lastname','firstname','age','description','city_id','image');
        $city=City::where('id','=',$data['user']['city_id'])->first();
        $data['user']['city_name']=$city->label;
        return response()->json(compact('data'));
    }
    public function update_profile(Request $request)
    {   $status=false;
        $user=auth()->user();
        $user->lastname=$request->lastname;
        $user->firstname=$request->firstname;
        $user->description=$request->description;
        $user->age=$request->age;
        $user->city_id=$request->city_id;
        
        if($user->save())
        {
            $status=true;
        }
        return response()->json(array('status'=>$status));

    }

    public function get_cities()
    {
        $cities=City::all();
        return response()->json(compact('cities'));
     }
       public function editPicture(Request $request) {
        $user = auth()->user();
            
            //uniqid== generer un unique id
                $pict = 'users/'.md5(uniqid(rand(), true)).'.jpg';
                if(Storage::disk('uploads')->put($pict, file_get_contents($request->image))){
                    User::where(['id' => $user->id])->update(['image' => $pict]);
                }
            
            $status = True;
            $message = 'user updated picture';

                
        
        $data = array('status' => $status, 'message' => $message);
        return response()->json(compact('data'));
        
    }

}