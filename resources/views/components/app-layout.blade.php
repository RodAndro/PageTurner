@extends('layouts.main-layout')

@section('nav')
    <x-main-navigation />
@endsection

<!-- Floating Chatbot for authenticated users -->
<x-simple-chatbot />
