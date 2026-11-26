@extends('layouts.app')
@section('title', $publicProfile['name'].' - Üye Profili | ' . $siteName)
@section('meta_description', $publicProfile['name'].' adlı üyenin ' . $siteName . ' profili.')
@section('content')
<div class="site-page-content">
    <div class="site-container pt-5 pb-12">

<div class="max-w-4xl mx-auto"><x-public-profile-card :profile="$publicProfile" kind="Üye" /></div>
    </div>
</div>
@endsection