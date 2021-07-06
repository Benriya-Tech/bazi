<?php

namespace App\Classes;

use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class BaziCalculator{

    public $user_info;
    public $date_now;
    public $dob;
    //---- local var
    private $_date = '01/00/1900';
    private $main_elements;
    private $element_method;
    private $main_zodiacs;
    private $hidden_elements;
    private $year_pillar;
    private $time_compare;

    function __construct($user_info) {
        $this->user_info = $user_info;
        $this->date_now = new DateTime();
        $this->LocationCal();
    }

    // lat-long cal
    private $long_hrs;
    private $gmt_mins;
    private $eot_mins;
    private $dst_n;
    private $dst_y;
    private $rst;
    private $dob_time;

    private function LocationCal(){
        if(!$this->user_info->location || !isset($this->user_info->location->name))
            $this->user_info->location = DB::table('locations')->where('id', $this->user_info->loccation_id)->first();
        $this->dob = $this->user_info->dob;
        $this->long_hrs = $this->user_info->location->longs * 4/60;
        $this->gmt_mins = ($this->user_info->location->gmt - $this->long_hrs) * 60;
        $this->eot_mins = DB::table('eot')->where('id', (int)$this->user_info->dob->format('d'))->value('m_'.(int)$this->user_info->dob->format("m"));
        $this->dst_n = $this->gmt_mins + $this->eot_mins;
        $this->dst_y = (($this->user_info->location->dst - $this->user_info->location->gmt) * 60) + $this->eot_mins + $this->gmt_mins;
        $this->rst = (int)($this->user_info->is_dst ? round($this->dst_y) : round($this->dst_n));
        $this->user_info->rst = $this->rst;
        $this->dob_time = (($this->user_info->dob->getTimestamp()/86400)+25569);
        $this->user_info->age = ((($this->date_now->getTimestamp()/86400)+25569) - $this->dob_time)/365;
    }

    public function Calculate(){
        $kc1 = $this->user_info->location->gmt/24;
        $this->main_elements = DB::table('elements')->get();
        $this->main_zodiacs = DB::table('zodiacs')->get();
        $this->element_method = DB::table('element_method')->get();
        $this->hidden_elements = DB::table('hidden_elements')->get();
        $this->time_compare = DB::table('time_compare')->get();

        $this->year_pillar = DB::table('yearly_pillar')->where('year', (int)$this->dob->format('Y'))->first();
        $year_ref = (array)DB::table('changing_month')->where('year', (int)$this->dob->format('Y'))->first();

        $m = $this->compareDOB($year_ref,$this->dob_time,$kc1);
        $monthly_pillar = (array)DB::table('monthly_pillar_hs')->where('year', (int)$this->dob->format('Y'))->first();
        $born_type = (array)DB::table('month_born')->where('id', $m)->first();
        $born_type = $born_type[strtolower($this->user_info->born_type).'_month'];
        $f151 = $monthly_pillar["m_$m"];

        $start_time = 35.2455208333333;
        $end_time = 0.999988425925926;
        $dst_x = ($this->user_info->location->dst - $this->user_info->location->gmt)*60;

        $e156 = ($this->dob_time-(int)($start_time + $kc1)-7)%60;
        $d157 = (((int)$this->dob->format('H')/24)+(int)$this->dob->format('i')/(60*24))-($this->eot_mins + $this->gmt_mins + $dst_x)/(60*24);
        $afag =  $d157 < 0 ? $e156 : ($d157 > $end_time ? $e156+2 : $e156+1);
        $afag_val = DB::table('monthly_ref')->where('id', $afag)->first();

        $birth_chart_y = $this->getBirthChart_Year($afag_val->af);
        $birth_chart_m = $this->getBirthChart($afag_val,$f151,$born_type);
        $birth_chart = array_merge_recursive($birth_chart_y,$birth_chart_m);

        //---- Day ------------------------------------------------------
        $birth_chart['main']['day'] = (array)$this->main_elements[$afag_val->af-1];
        $birth_chart['main']['day']['method'] = 'DM'; //$this->getElementMethod($afag_val->af,$afag_val->af-1);

        // Branches
        $birth_chart['branch']['day'] = (array)$this->main_zodiacs[$afag_val->ag-1];
        $hidden = $this->getHiddenElement($afag_val->ag,1);
        if($hidden == 0){
            $birth_chart['branch']['day']['method'] = null;
        }else{
            $birth_chart['branch']['day']['method'] = $this->getElementMethod($afag_val->af,$hidden-1);
        }

        //Hidden Stems
        $birth_chart['hidden']['day'][0] = (array)$this->main_elements[$hidden-1];
        $birth_chart['hidden']['day'][0]['method'] = $birth_chart['branch']['day']['method'];
        $hidden_2 = $this->getHiddenElement($afag_val->ag,2);


        if($hidden_2 == 0){
            $birth_chart['hidden']['day'][1] = null;
        }else{
            $birth_chart['hidden']['day'][1] = (array)$this->main_elements[$hidden_2-1];
            $birth_chart['hidden']['day'][1]['method'] = $this->getElementMethod($afag_val->af,$hidden_2-1);
        }

        $hidden_3 = $this->getHiddenElement($afag_val->ag,3);
        if($hidden_3 == 0){
            $birth_chart['hidden']['day'][2] = null;
        }else{
            $birth_chart['hidden']['day'][2] = (array)$this->main_elements[$hidden_3-1];
            $birth_chart['hidden']['day'][2]['method'] = $this->getElementMethod($afag_val->af,$hidden_3-1);
        }


        $max_time = ((strtotime($this->_date.' '.$this->time_compare[count($this->time_compare)-1]->end_hrs)/86400)+25569);
        $e157 = $d157 < 0 ? ($afag_val->af-2 > 5 ? $afag_val->af-4 : $afag_val->af) : ($d157 > $max_time-1 ? ($afag_val->af-1 > 5 ? $afag_val->af-6 : $afag_val->af-1) : ($afag_val->af > 5 ? $afag_val->af-5 : $afag_val->af));

        $time_sum = $this->getTimeCompare($d157);

        //---- Hour ------------------------------------------------------
        if((int)$this->dob->format('H') == 0 && (int)$this->dob->format('i') == 0){
            $birth_chart['main']['hrs'] = 'X';
            $birth_chart['main']['hrs']['method'] = 'X';
        }else{
            $time_refs = (array)DB::table('time_ref_collection')->where('id', $time_sum)->first();
            $birth_chart['main']['hrs'] = (array)$this->main_elements[$time_refs['ref_'.($e157+1)]-1];
            $birth_chart['main']['hrs']['method'] = $this->getElementMethod($afag_val->af,$time_refs['ref_'.($e157+1)]-1);

            // Branches
            $birth_chart['branch']['hrs'] = (array)$this->main_zodiacs[$time_refs['ref_1']-1];
            $hidden = $this->getHiddenElement($time_refs['ref_1'],1);
            if($hidden == 0){
                $birth_chart['branch']['hrs']['method'] = null;
            }else{
                $birth_chart['branch']['hrs']['method'] = $this->getElementMethod($afag_val->af,$hidden-1);
            }

            //Hidden Stems
            $birth_chart['hidden']['hrs'][0] = (array)$this->main_elements[$hidden-1];
            $birth_chart['hidden']['hrs'][0]['method'] = $birth_chart['branch']['hrs']['method'];

            $hidden_2 = $this->getHiddenElement($time_refs['ref_1'],2);
            if($hidden_2 == 0){
                $birth_chart['hidden']['hrs'][1] = null;
            }else{
                $birth_chart['hidden']['hrs'][1] = (array)$this->main_elements[$hidden_2-1];
                $birth_chart['hidden']['hrs'][1]['method'] = $this->getElementMethod($afag_val->af,$hidden_2-1);
            }

            $hidden_3 = $this->getHiddenElement($time_refs['ref_1'],3);
            if($hidden_3 == 0){
                $birth_chart['hidden']['hrs'][2] = null;
            }else{
                $birth_chart['hidden']['hrs'][2] = (array)$this->main_elements[$hidden_3-1];
                $birth_chart['hidden']['hrs'][2]['method'] = $this->getElementMethod($afag_val->af,$hidden_3-1);
            }
        }

        $date = new DateTime($year_ref["m_$m"]);
        $jie_ji_prv = (($date->getTimestamp()/86400)+25569)+($this->user_info->location->gmt/24);
        $date = DB::table('changing_month')->where('id', $m == 12 ? $year_ref['id']+1 : $year_ref['id'])->value('m_'.($m == 12 ? 1 : $m+1));
        $date = new DateTime($date);
        $jie_ji_next = (($date->getTimestamp()/86400)+25569)+($this->user_info->location->gmt/24);
        $fwd_bwd = (in_array($this->year_pillar->ref_1,[1,3,5,7,9]) ? 1 : -1) * (strtolower($this->user_info->gender) == 'm' ? 1 : -1);
        $e162 = ((int)$this->dob_time + $d157);
        $day_diff = $fwd_bwd == 1 ? $jie_ji_next - $e162 : $e162 - $jie_ji_prv;
        $today_10lp = $e162 + ($day_diff/3) * 360;
        $f166 = round(($today_10lp - $e162)/360);

        if($f166 == 0){
            $luck_plillar_10y[0]['title'] = null;
            $luck_plillar_10y[0]['age'] = null;
        }else{
            $luck_plillar_10y[0]['title'] = $f166 == 0 ? "" : gmdate("d M Y", ($e162 - 25569) * 86400).'-'.(int)((int)gmdate("Y", ($e162 - 25569) * 86400) + $f166);
            $luck_plillar_10y[0]['age'] = "Age 00 - ".str_pad((int)$f166,2,"0",STR_PAD_LEFT);
        }

        $decade_lucks = $this->tenYear_Luck_Pillar($today_10lp,$f166,$e162,$f151,$fwd_bwd,$afag_val,$born_type);
        $yearly_luck = $this->yearly_Luck_Pillar($today_10lp,$f166,$afag_val);

        return ['birthchart'=>$birth_chart,'decade'=>$decade_lucks,'yearly'=>$yearly_luck];
    }


    private function yearly_Luck_Pillar($today_10lp,$f166,$afag_val){
        $yearly_birth = gmdate("Y", ($today_10lp - 25569) * 86400);
        $yearly_lucks = [];
        $ini = [];
        for($i=0;$i<10;$i++){
            $yearly_lucks[$i]['title'] = strtolower($this->user_info->living_type) == 'n' ? '22 Dec '.($yearly_birth-1) : '22 Jun '.$yearly_birth;
            $yearly_lucks[$i]['age'] = $yearly_birth.' (Age '.str_pad($f166,2,"0",STR_PAD_LEFT).')';

            $ini[$i]['counter'] = (($yearly_birth-4)%60)+1;
            $md = $ini[$i]['counter']%10;
            $ini[$i]['elm'] = $md == 0 ? 10 : $md;
            $md = $ini[$i]['counter']%12;
            $ini[$i]['zoc'] = $md == 0 ? 12 : $md;

            $yearly_lucks[$i]['main'] = (array)$this->main_elements[$ini[$i]['elm']-1];
            $yearly_lucks[$i]['main']['method'] = $this->getElementMethod($afag_val->af,$ini[$i]['elm']-1);

            $yearly_lucks[$i]['branch'] = (array)$this->main_zodiacs[$ini[$i]['zoc']-1];
            $yearly_lucks[$i]['branch']['method'] = 'xx';

            $hidden = $this->getHiddenElement($ini[$i]['zoc'],1);
            $yearly_lucks[$i]['hidden'][0] = (array)$this->main_elements[$hidden-1];
            $yearly_lucks[$i]['hidden'][0]['method'] = $this->getElementMethod($afag_val->af,$hidden-1);
            $yearly_lucks[$i]['branch']['method'] = $yearly_lucks[$i]['hidden'][0]['method'];

            $hidden_2 = $this->getHiddenElement($ini[$i]['zoc'],2);
            if($hidden_2 == 0){
                $yearly_lucks[$i]['hidden'][1] = null;
            }else{
                $yearly_lucks[$i]['hidden'][1] = (array)$this->main_elements[$hidden_2-1];
                $yearly_lucks[$i]['hidden'][1]['method'] = $this->getElementMethod($afag_val->af,$hidden_2-1);
            }

            $hidden_3 = $this->getHiddenElement($ini[$i]['zoc'],3);
            if($hidden_3 == 0){
                $yearly_lucks[$i]['hidden'][2] = null;
            }else{
                $yearly_lucks[$i]['hidden'][2] = (array)$this->main_elements[$hidden_3-1];
                $yearly_lucks[$i]['hidden'][2]['method'] = $this->getElementMethod($afag_val->af,$hidden_3-1);
            }

            $yearly_birth++;
            $f166++;
        }
        //dd($yearly_lucks);
        return $yearly_lucks;
    }

    private function tenYear_Luck_Pillar($today_10lp,$f166,$e162,$f151,$fwd_bwd,$afag_val,$born_type){
        $tstamp = (($today_10lp - 25569) * 86400);
        $date[0] = gmdate("d M Y",$tstamp);
        $age[0] = 0;
        $year = [];
        $x=0;
        $yrs[0] = $f151;
        $branch_val[0] = $fwd_bwd == 1 ? ($born_type == 12 ? 1 : $born_type+1) : ($born_type == 1 ? 12 : $born_type-1);

        for($i=-10;$i<=90;$i+=10){
            $age[$x+1] = str_pad((int)$f166 + $i,2,"0",STR_PAD_LEFT);
            $date[$x+1] = gmdate("d M Y",strtotime("+{$i} year",$tstamp));
            $year[$x]['title'] = ($x == 1 ? $this->dob->format('d M Y') : $date[$x]).'-'.(int)((int)gmdate("Y", ($e162 - 25569) * 86400) + $f166+$i); //gmdate("Y-m-d H:i:s",strtotime("+{$i} year",$tstamp));
            $year[$x]['age'] = 'Age '.str_pad($age[$x] < 0 ? 0 : $age[$x],2,'0',STR_PAD_LEFT).'-'.str_pad($age[$x+1],2,'0',STR_PAD_LEFT);
            $x++;
        }
        $x = 0;
        $yrs[0] = $f151;
        $_10y_lucks = $year;
        $branch_val[0] = $fwd_bwd == 1 ? ($born_type == 12 ? 1 : $born_type+1) : ($born_type == 1 ? 12 : $born_type-1);
        foreach($_10y_lucks as $i=>$xyear){
            $yrs[$i+1] = $fwd_bwd == 1 ? ($yrs[$i] == 10 ? 1 : $yrs[$i]+1) : ($yrs[$i] == 1 ? 10 : $yrs[$i]-1);
            //$branch_val[$i] = $fwd_bwd == 1 ? ($branch_val[$i] == 12 ? 1 : $branch_val[$i]+1) : ($branch_val[$i] == 1 ? 12 : $branch_val[$i]-1);
            $branch_val[$i+1] = $fwd_bwd == 1 ? ($branch_val[$i] == 12 ? 1 : $branch_val[$i]+1) : ($branch_val[$i] == 1 ? 12 : $branch_val[$i]-1);
            if($i>0){
                $_10y_lucks[$i]['main'] = (array)$this->main_elements[$yrs[$i-1]-1];
                $_10y_lucks[$i]['main']['method'] = $this->getElementMethod($afag_val->af,$yrs[$i-1]-1);
                if($i > 1){
                    $xi = $i-2;
                    //if($i > 2)
                    //    dd($branch_val[$x]);
                    $_10y_lucks[$i]['branch'] = (array)$this->main_zodiacs[$branch_val[$i-2]-1];//$yrs[$i-1]-1];//$branch_val[$i]-1];
                    $_10y_lucks[$i]['branch']['method'] = 'xx';
                    $hidden = $this->getHiddenElement($branch_val[$i-2],1);
                    $_10y_lucks[$i]['hidden'][0] = (array)$this->main_elements[$hidden-1];
                    $_10y_lucks[$i]['hidden'][0]['method'] = $this->getElementMethod($afag_val->af,$hidden-1);

                    $_10y_lucks[$i]['branch']['method'] = $_10y_lucks[$i]['hidden'][0]['method'];

                    $hidden_2 = $this->getHiddenElement($branch_val[$i-2],2);
                    if($hidden_2 == 0){
                        $_10y_lucks[$i]['hidden'][1] = null;
                    }else{
                        $_10y_lucks[$i]['hidden'][1] = (array)$this->main_elements[$hidden_2-1];
                        $_10y_lucks[$i]['hidden'][1]['method'] = $this->getElementMethod($afag_val->af,$hidden_2-1);
                    }

                    $hidden_3 = $this->getHiddenElement($branch_val[$i-2],3);
                    if($hidden_3 == 0){
                        $_10y_lucks[$i]['hidden'][2] = null;
                    }else{
                        $_10y_lucks[$i]['hidden'][2] = (array)$this->main_elements[$hidden_3-1];
                        $_10y_lucks[$i]['hidden'][2]['method'] = $this->getElementMethod($afag_val->af,$hidden_3-1);
                    }
                }
            }
        }
        //dd($branch_val);
        array_shift($_10y_lucks);
        array_shift($_10y_lucks);
        array_pop($_10y_lucks);
        return $_10y_lucks;
    }


    private function compareDOB($year_ref,$dob_time,$kc1){

        $m = [];
        for($i=1;$i<=12;$i++){
            $date = new DateTime($year_ref["m_$i"]);
            $m[$i] = $dob_time > ((($date->getTimestamp()/86400)+25569) + $kc1) ? 1 : 0;
        }
        return array_sum($m);
    }

    private function getElementMethod($r,$c){
        $rs = [];
        foreach($this->element_method as $elm){
            if($elm->id == $r){
                $rs = (array)$elm;
                break;
            }
        }
        return $rs[$this->main_elements[$c]->code];
    }
    private function getHiddenElement($r,$c){
        $rs = [];
        foreach($this->hidden_elements as $elm){
            if($elm->id == $r){
                $rs = (array)$elm;
                break;
            }
        }
        return $rs['ref_'.$c];
    }
    private function getBirthChart($afag_val,$f151,$born_type,$type='month'){
        $birth_chart = [];
        $birth_chart['main'][$type] = (array)$this->main_elements[$f151-1];
        $birth_chart['main'][$type]['method'] = $this->getElementMethod($afag_val->af,$f151-1);

        // Branches
        $birth_chart['branch'][$type] = (array)$this->main_zodiacs[$born_type-1];
        $hidden = $this->getHiddenElement($born_type,1);
        if($hidden == 0){
            $birth_chart['branch'][$type]['method'] = null;
        }else{
            $birth_chart['branch'][$type]['method'] = $this->getElementMethod($afag_val->af,$hidden-1);
        }

        //Hidden Stems
        $birth_chart['hidden'][$type][0] = (array)$this->main_elements[$hidden-1];
        $birth_chart['hidden'][$type][0]['method'] = $birth_chart['branch'][$type]['method'];


        $hidden_2 = $this->getHiddenElement($born_type,2);
        if($hidden_2 == 0){
            $birth_chart['hidden'][$type][1] = null;
        }else{
            $birth_chart['hidden'][$type][1] = (array)$this->main_elements[$hidden_2-1];
            $birth_chart['hidden'][$type][1]['method'] = $this->getElementMethod($afag_val->af,$hidden_2-1);
        }
        $hidden_3 = $this->getHiddenElement($born_type,3);
        if($hidden_3 == 0){
            $birth_chart['hidden'][$type][2] = null;
        }else{
            $birth_chart['hidden'][$type][2] = (array)$this->main_elements[$hidden_3-1];
            $birth_chart['hidden'][$type][2]['method'] = $this->getElementMethod($afag_val->af,$hidden_3-1);
        }
        return $birth_chart;
    }

    private function getBirthChart_Year($e151){
        $birth_chart = [];
        $birth_chart['main']['year'] = (array)$this->main_elements[$this->year_pillar->ref_1-1];
        $birth_chart['main']['year']['method'] = $this->getElementMethod($e151,$this->year_pillar->ref_1-1);

        $birth_chart['branch']['year'] = (array)$this->main_zodiacs[$this->year_pillar->ref_2-1];
        $hidden = $this->getHiddenElement($this->year_pillar->ref_2,1);
        if($hidden == 0){
            $birth_chart['branch']['year']['method'] = null;
        }else{
            $birth_chart['branch']['year']['method'] = $this->getElementMethod($e151,$hidden-1);
        }

        $birth_chart['hidden']['year'][0] = (array)$this->main_elements[$hidden-1];
        $birth_chart['hidden']['year'][0]['method'] = $birth_chart['branch']['year']['method'];
        $hidden_2 = $this->getHiddenElement($this->year_pillar->ref_2,2);
        if($hidden_2 == 0){
            $birth_chart['hidden']['year'][1] = null;
        }else{
            $birth_chart['hidden']['year'][1] = (array)$this->main_elements[$hidden_2-1];
            $birth_chart['hidden']['year'][1]['method'] = $this->getElementMethod($e151,$hidden_2-1);
        }
        $hidden_3 = $this->getHiddenElement($this->year_pillar->ref_2,3);
        if($hidden_3 == 0){
            $birth_chart['hidden']['year'][2] = null;
        }else{
            $birth_chart['hidden']['year'][2] = (array)$this->main_elements[$hidden_3-1];
            $birth_chart['hidden']['year'][2]['method'] = $this->getElementMethod($e151,$hidden_3-1);
        }
        return $birth_chart;
    }

    private function getTimeCompare($checker){
        $s = [];
        $c = count($this->time_compare);
        foreach($this->time_compare as $time){
            if($c != $time->id)
                $s[] = $checker > ((strtotime($this->_date.' '.$time->end_hrs)/86400)+25569)-1 ? 1 : 0;
        }
        return array_sum($s)+1;
    }

}
