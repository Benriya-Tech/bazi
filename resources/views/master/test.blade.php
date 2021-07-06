@extends('layouts.app')
@section('header')
@parent
@stop
@section('content')
<div class="col-12"><h1>Bazi Astrology</h1></div>
<div class="row">
        <div class="col-4">
            <br><br>

            <div><b>Location:</b> {{$sample->user_info->location->city}}, {{$sample->user_info->location->country}}</div>
            <div><b>GMT:</b> {{$sample->user_info->location->gmt > 0 ? '+' : ''}}{{$sample->user_info->location->gmt}}</div>
            <div><b>DST:</b> {{$sample->user_info->is_dst ? 'Y' : 'N'}}</div>
            <div><b>Long:</b> {{$sample->user_info->location->long}}</div>
            <div><b>Born:</b> {{$sample->user_info->born_type}}</div>
            <div><b>Living:</b> {{$sample->user_info->living_type}}</div>
            <hr/>
            <div><b>DOB:</b> {{$sample->user_info->dob->format('d/m/Y H:i')}}</div>
            <div><b>Age:</b> {{$sample->user_info->age}}</div>
            <div><b>Gender: </b>{{$sample->user_info->gender}}</div>
            <div><b>RST: </b>{{$sample->user_info->rst}}</div>
        </div>
        <div class="col-8">
            dsss
        </div>
</div>
@endsection
