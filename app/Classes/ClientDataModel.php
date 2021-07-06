<?php

namespace App\Classes;

class ClientDataModel{
    public $data;
    public $user_info;
}

class UserInfo{
    public $name;
    public $dob;
    public $is_dst;
    public $age;
    public $born_type;
    public $gender;
    public $living_type;
    public $location_id;

    //--- Optional
    public $rst;

    //--- Object
    public $location;
}
