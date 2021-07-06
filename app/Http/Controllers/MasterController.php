<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Classes\ClientDataModel;
use App\Classes\UserInfo;
use App\Classes\BaziCalculator;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Symfony\Component\VarDumper\Cloner\Data;

class MasterController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

    }

    public function index() {
        return view('master.index');
    }


    public function test() {

        $sample = new ClientDataModel();
        $user_info = new UserInfo();

        //--------------------------------------------------------------------

        $dob = new DateTime("20-05-1999 20:35:00");
        $user_info->name = 'Test name';
        $user_info->gender = 'F';
        $user_info->is_dst = false;
        $user_info->born_type = 'N';
        $user_info->living_type ='N';

        //--------------------------------------------------------------------

        $user_info->loccation_id = 83607;
        $user_info->dob = $dob;

        $user_info->location = DB::table('locations')->where('id', $user_info->loccation_id)->first();

        $bazi = new BaziCalculator($user_info);
        $sample->data = $bazi->Calculate();
        $sample->user_info = $bazi->user_info;

        dd($sample->data);

        return view('master.test',['sample'=>$sample]);
    }
}
