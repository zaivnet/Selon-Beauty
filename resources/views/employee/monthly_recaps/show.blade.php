@extends('layouts.employee')

@section('title', 'Rekap Saya')

@section('content')
    @include('monthly_recaps._detail', ['isEmployeeView' => true])
@endsection
