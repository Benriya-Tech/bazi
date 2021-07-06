<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\ClientDataModel;
use App\Classes\UserInfo;
use App\Classes\BaziCalculator;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Symfony\Component\VarDumper\Cloner\Data;


class CalculateAPIController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // public function __invoke(Request $request)
    // {

    // }

    public function index(Request $request)
    {
        return response()->json(['request'=>$request->all()]);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $xdata = new ClientDataModel();
        $user_info = new UserInfo();
        $errors = [];
        try{
            $dob = new DateTime($input['dob']);
            if (DateTime::createFromFormat('Y-m-d G:i:s', $input['dob']) !== FALSE) {
                $errors[] = 'Date format invalid';
            }
            if($dob->format('Y') > 2043 || $dob->format('Y') < 1900){
                $errors[] = 'Year must be  be between 1900 and 2043';
            }

            $user_info->name = $input['name'];
            $user_info->gender = strtoupper($input['gender']);

            if(!in_array($user_info->gender,['M','F'])){
                $errors[] = 'Gender should be M or F';
            }
            $user_info->is_dst = $input['is_dst'] === true || strtolower($input['is_dst']) == 'true';

            $user_info->born_type = strtoupper($input['born_type']);
            if(!in_array($user_info->born_type,['N','S'])){
                $errors[] = 'Born with one hemisphereshould N or S';
            }

            $user_info->living_type = strtoupper($input['living_type']);
            if(!in_array($user_info->living_type,['N','S'])){
                $errors[] = 'Living with one hemisphereshould N or S';
            }

            $user_info->loccation_id = $input['loccation_id'];
            $user_info->dob = $dob;

            $user_info->location = DB::table('locations')->where('id', $user_info->loccation_id)->first();
            if($user_info->location == null || empty($user_info->location)){
                $errors[] = 'Location not found';
            }
            if(count($errors) > 0){
                return response()->json(['errors'=>$errors],500);
            }

            $bazi = new BaziCalculator($user_info);
            $xdata->data = $bazi->Calculate();
            $xdata->user_info = $bazi->user_info;
            return response()->json($xdata);
        }catch(Exception $ex){
            return response()->json(['errors'=>[$ex->getMessage()]],500);
        }
    }

}
