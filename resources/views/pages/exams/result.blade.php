@extends('layouts.app')

@section('title', 'Sınav Sonucu ve Analiz - ' . $siteName)

@section('content')
<x-question-bookmark-scripts />
<section x-data="questionBookmarks({ savedQuestionIds: @js($savedQuestionIds), toggleSaveUrl: @js(url('/soru')), csrfToken: @js(csrf_token()) })" class="pt-4 sm:pt-5 pb-10 sm:pb-14">
    @include('partials.exam-result')
</section>
@endsection
