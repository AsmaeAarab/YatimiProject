<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\Exo;
use App\Models\Rate;
use App\Models\Transaction;
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
use Cartalyst\Stripe\Stripe;
use Ixudra\Curl\Facades\Curl;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Models\Matiere;
use App\Models\Ecole;

class AuthController extends Controller
{
    public function profile() {
        if(auth()->user()->is_prof == 1){
            $data['user'] = auth()->user()->only('id','first_name','last_name','email','tel','matiere_id','rib','solde','is_prof','picture', 'twilio_id','level_prof','formation_principale','ecole_id','autre_ecole');
            $data['user']['solde'] = $this->montant_to_euro($data['user']['solde']);
            $data['exo_resolu'] = Exo::where('prof_id','=',auth()->user()->id)->where('issolved','=',1)->count();
            $data['rate'] = Rate::where('prof_id','=',auth()->user()->id)->avg('rate');
        }else{
            $data['user'] = auth()->user()->only('id','first_name','last_name','email','tel','level_id','school_name','solde','is_prof','picture', 'twilio_id');
            $data['user']['solde'] = $this->montant_to_euro($data['user']['solde']);
            $data['exo_resolu'] = Exo::where('etudiant_id','=',auth()->user()->id)->where('issolved','=',1)->count();
            $data['exo'] = Exo::where('etudiant_id','=',auth()->user()->id)->count();
        }
        return response()->json(compact('data'));
    }
    public function montant_to_euro($montant){
        $montant = round($montant, 2);
        $whole = floor($montant); 
        $fraction = $montant - $whole;
        if($fraction!=0){
            return $whole."€".$fraction*100;
        }else{
            return $whole."€";
        }
        
    }
     public function ephemeral_keys() {
        $stripe = Stripe::make(env('STRIPE_SECRET'));
        $user = auth()->user();
        $customer_id = $user->customer_id;
        
        if($customer_id==null){
            $customer = $stripe->customers()->create([
                'email' => $user->email,
            ]);
            $customer_id = $customer['id'];
            $user->customer_id = $customer_id;
            $user->save();
        }

        
        

        $key = $stripe->EphemeralKey()->create($customer_id);

        return $key;
    }

    public function saveDevice(Request $request){
        $user = auth()->user();
         DB::table('users')
                ->where('token_device', $request->token_device)
                ->update(['token_device' => NULL]);
        $user->token_device = $request->token_device;
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

    public function create_intent(Request $request) {
        $stripe = Stripe::make(env('STRIPE_SECRET'));
        $user = auth()->user();
        $customer_id = $user->customer_id;
        $amount = $request->amount;
        $payment = $stripe->PaymentIntents()->create([
            'customer' => $customer_id,
            'currency' => 'EUR',
            'amount'   => $request->amount,
            'payment_method_types' => ['card'],
            //'payment_method' => $request->payment_method_id,
            //'confirm' => true
        ]);
        
        
        $fees = ($amount*0.029)+0.25;
        $fees =  round($fees,2);
        $amount_net = $amount-$fees;

        $transaction = new Transaction;
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->amount_net = $amount_net;
        $transaction->payment_id = $payment['id'];
        $transaction->save();

        $user->solde = $user->solde+$amount_net;
        $user->save();

        return $payment;
    }

    public function create_payment_paysafe(Request $request) {
       $ch = curl_init();
       $amount = $request->amount;
       $cardnum = $request->cardnum;
       $month = $request->month;
       $year = $request->year;
       $zip = 'M5H 2N2';
        curl_setopt($ch, CURLOPT_URL, 'https://api.test.paysafe.com/cardpayments/v1/accounts/1001448140/auths');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
            "merchantRefNum": "postexo-'.mt_rand ( (int)1000000000000 , (int)9999999999999 ).'",
            "amount": '.($amount*100).',
            "settleWithAuth": true,
            "card": {
                "cardNum": '.$cardnum.',
                "cardExpiry": {
                    "month": '.$month.',
                    "year": '.$year.'
                }
            },
            "billingDetails": {
                "zip": "'.$zip.'"
            }
        }');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, 'test_hibaamarir' . ':' . 'B-qa2-0-5d13733e-0-302c0214506d8dafaadcbacf644baf80f2487e63512f3dd102147190183c8ddd2d50d903cbd341d9dcb257b194c7');

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        
        $result1 = json_decode($result);

        $id = $result1->id;

        $user = auth()->user();
        $transaction = new Transaction;
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->payment_id = $id;
        $transaction->save();

        $user->solde = $user->solde+$amount;
        $user->save();

        return $result;



                      
    }
    
    public function updateprofile(Request $request) {
        $user = auth()->user();
        $user->last_name = $request->last_name;
        $user->first_name = $request->first_name;
        $user->tel = $request->tel;
        if(auth()->user()->is_prof == 1){
            $user->matiere_id = $request->matiere_id;
            $matiere = Matiere::find($request->matiere_id);
            $matiere = $matiere->label;
            $user->formation_principale = $request->formation_principale;
            $user->level_prof = $request->level_prof;
            $user->prepa = $request->prepa;
            
            if(!empty($request->autre_ecole)){
                $user->autre_ecole = $request->autre_ecole;
                $user->ecole_id = NULL;
                $ecole = $request->autre_ecole;
            }else{
                $user->ecole_id = $request->ecole_id;
                $ecole = Ecole::find($request->ecole_id);
                $ecole = $ecole->nom;
            }
            $user->rib = $request->rib;
            //mail validation
            $mail = Mail::send('email', ['last_name' => $user->last_name, 'first_name' => $user->first_name,'formation_principale' => $user->formation_principale,'level_prof' => $user->level_prof,'prepa' => $user->prepa,'matiere' => $matiere,'ecole' => $ecole,'rib' => $user->rib], function ($m) use ($user,$matiere,$ecole) {
                    
                    $m->from(config('constants.MAIL_FROM'), 'PostExo ');
                    $m->to('postexo.contact@gmail.com', 'PostExo')->subject('PostExo - Nouveau compte professeur');
                    $m->getSwiftMessage()
                        ->getHeaders()
                        ->addTextHeader('X-MailjetLaravel-Template', config('constants.TMPL_ID_NEW_PROF'));
                    if($user->prepa){
                        $prepa = "oui";
                    }else{
                        $prepa = "non";
                    }
                    $variable = json_encode(['email'=>$user->email,'last_name' => $user->last_name, 'first_name' => $user->first_name,'formation_principale' => $user->formation_principale,'level_prof' => $user->level_prof,'prepa' => $prepa,'matiere' => $matiere,'ecole' => $ecole,'rib' => $user->rib,'tel'=>$user->tel]);

                    $m->getSwiftMessage()
                    ->getHeaders()
                    ->addTextHeader('X-MailjetLaravel-TemplateBody', $variable);
                });

        }else{
            $user->level_id = $request->level_id;
            $user->school_name = $request->school_name;
        }
        if($user->save())
            return response()->json(['state' => 1]);
        else
            return response()->json(['state' => 0]);        
   }
   public function editPicture(Request $request) {
        $user = auth()->user();
            
            //uniqid== generer un unique id
                $pict = 'users/'.md5(uniqid(rand(), true)).'.jpg';
                if(Storage::disk('uploads')->put($pict, file_get_contents($request->picture))){
                    User::where(['id' => $user->id])->update(['picture' => $pict]);
                    $user =auth()->user()->only('id','last_name','first_name','email','tel','matiere_id','solde','is_prof','picture');
                    $data['user'] = $user;
                    return response()->json(compact('data'));
                }
            
            $status = True;
            $message = 'user updated picture';

                
        
        $data = array('status' => $status, 'message' => $message);
        return response()->json(compact('data'));
        
    }
    
    public function resetpass(Request $request)
    {
        
        $user = User::where(array('email' => $request->email))->first();
        if(!empty($user)) {
            
            $code= $request->code;
            $user->code =$code;
            $now = new DateTime();
            $tomorrow = $now->add(new DateInterval('P1D'));
            $user->date_expired = $tomorrow;
            $user->save();
            $link ="PostExoDeeeplLink://postexo.com/resetpass/code/".$code."?email=".$user->email."";
            $mail = Mail::send('email', ['name' => $user->first_name, 'email' => $user->email], function ($m) use ($user,$link) {
                    
                    $m->from(config('constants.MAIL_FROM'), 'PostExo ');
                    $m->to($user->email, 'PostExo')->subject('PostExo - Réinitialisation mots de passe');
                    $m->getSwiftMessage()
                        ->getHeaders()
                        ->addTextHeader('X-MailjetLaravel-Template', config('constants.TMPL_ID_PASSWORD'));
                    
                    $variable = json_encode(['name' => $user->first_name,'link'=>$link]);

                    $m->getSwiftMessage()
                    ->getHeaders()
                    ->addTextHeader('X-MailjetLaravel-TemplateBody', $variable);
                });
            return response()->json(['message' => 'Email was sent to the user']);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function updatepass(Request $request) {
    
        
            $user = User::where('code', '=', $request->code)
                    ->where('email', '=', $request->email)
                    ->whereRaw('`date_expired` >"'.Carbon::now().'"')->first();
        
            if(!empty($user)) {
                
                $user->password = bcrypt($request->password);

                $user->date_expired = Carbon::now();
                if($user->save())
                    return response()->json(['status' => 1]);
                else
                    return response()->json(['status' => 0]);
            } else {
                return response()->json(['status' => 0]);
            }      
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $credentials['status'] = 1;
        
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        if(auth()->user()->isconfirmed){
            if(auth()->user()->is_prof == 1){
                $data['user'] = auth()->user()->only('id','first_name','last_name','email','rib','tel','matiere_id','solde', 'twilio_id');
            }else{
                $data['user'] = auth()->user()->only('id','first_name','last_name','email','tel','level_id','school_name','solde', 'twilio_id');
            }
            return response()->json(compact('token', 'data'));
        }else{
            $token = null;
            return response()->json(compact('token'));
        }  
    }




    public function verify_code(Request $request) {
        $user = User::where('code', '=', $request->code)
                    ->where('email','=', $request->email)
                    ->whereRaw('`date_expired` > now()')->first();
        if(!empty($user)) {
            $user->isconfirmed = true;
            $user->save();
            Auth::loginUsingId($user->id);
            $token = JWTAuth::fromUser(Auth::user());
            return response()->json(['status' => 1, 'token' => $token]);
        } else {
            return response()->json(['status' => 0, 'message' => 'Code invalide ou expiré']);
        }
    }
    public function register(Request $request) {
            $user_test = User::where(array('email' => $request->email))->first();
            if(!empty($user_test)) {
                $data = array('status' => false, 'message' => "Cet email déjà existe",'user'=>null);
                return response()->json(['token' =>null,'data'=>$data]);
            }
            if($request->is_prof == 1){
                $user = new User;
                $user->name =ucfirst(strtolower($request->first_name))." ".strtoupper($request->last_name[0]).".";
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->tel = $request->tel;
                $user->matiere_id = $request->matiere_id;
                $user->is_prof = $request->is_prof;
                $user->code = $request->code;
                $now = new DateTime();
                $tomorrow = $now->add(new DateInterval('P100D'));
                $user->date_expired = $tomorrow;
                $user->password = bcrypt($request->password);
                $user->twilio_id = md5($request->email);
                $user->save();
                $data['user'] = $user->only('id','first_name','last_name','rib','email','tel','matiere_id','solde');
            }else{

                $user = new User;
                $count_users = User::where('is_prof','=',0)->get()->count();
                
                if($count_users<500){
                    $user->solde=20;
                }
                $user->name = ucfirst(strtolower($request->first_name))." ".strtoupper($request->last_name[0]).".";
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->tel = $request->tel;
                $user->code = $request->code;
                $now = new DateTime();
                $tomorrow = $now->add(new DateInterval('P100D'));
                $user->date_expired = $tomorrow;
                $user->level_id = $request->level_id;
                $user->school_name = $request->school_name;
                $user->twilio_id = md5($request->email);
                $user->password = bcrypt($request->password);
                $user->save();
                $data['user'] = $user->only('id','first_name','last_name','email','tel','level_id','school_name','solde');
            }
            
            
            $link ="PostExoDeeeplLink://postexo.com/activation/code/".$request->code."?email=".$user->email."";
            $mail = Mail::send('email', ['name' => $user->first_name, 'email' => $user->email], function ($m) use ($user,$link) {
                    
                    $m->from(config('constants.MAIL_FROM'), 'PostExo ');
                    $m->to($user->email, 'PostExo')->subject('PostExo - Activer votre compte');
                    $m->getSwiftMessage()
                        ->getHeaders()
                        ->addTextHeader('X-MailjetLaravel-Template', config('constants.TMPL_ID_ACTIVE'));
                    
                    $variable = json_encode(['name' => $user->first_name,'link'=>$link]);

                    $m->getSwiftMessage()
                    ->getHeaders()
                    ->addTextHeader('X-MailjetLaravel-TemplateBody', $variable);
                });
            return response()->json(compact('data'));
    }

    public function contactUS(Request $request) {

        $user = auth()->user();
        $email = $user->email;
        $msg = $request->msg;

        $mail = Mail::send('email', ['name' => $user->first_name, 'email' => $user->email, 'msg' => $msg], function ($m) use ($user,$msg) {
                    $name = $user->first_name;
                    $m->from('hibaamarir95@gmail.com', 'PostExo ');
                    
                    
                        $m->to(config('constants.MAIL_FROM'), 'PostExo')->subject('PostExo - contactez-nous');
                         $m->getSwiftMessage()
                        ->getHeaders()
                        ->addTextHeader('X-MailjetLaravel-Template', config('constants.TMPL_ID_HELP'));
                    
                    $variable = json_encode(['name' => $first_name,'email' => $user->email ,'msg' => $msg]);

                    $m->getSwiftMessage()
                    ->getHeaders()
                    ->addTextHeader('X-MailjetLaravel-TemplateBody', $variable);
                });

        $data = array('status' => true, 'message' => "Message envoyé");
        
        return response()->json(compact('data'));
    }

    public function logout() {
        auth()->logout();
        return response()->json();
    }
}
