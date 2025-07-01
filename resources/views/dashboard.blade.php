@extends('layouts.app')

@section('content')
    <h1>Tableau de bord par défaut</h1>
    <p>Bienvenue {{ Auth::user()->name }} !</p>
@endsection
