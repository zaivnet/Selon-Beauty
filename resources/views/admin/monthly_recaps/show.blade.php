@extends('layouts.admin')

@section('title', 'Detail Rekap Bulanan')
@section('page-title', 'Detail Rekap Kehadiran')

@section('content')
    @include('monthly_recaps._detail', ['isEmployeeView' => false])
@endsection
