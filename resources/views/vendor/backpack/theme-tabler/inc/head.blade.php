<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
@if (backpack_theme_config('meta_robots_content'))
<meta name="robots" content="{{ backpack_theme_config('meta_robots_content', 'noindex, nofollow') }}">
@endif

@includeWhen(view()->exists('vendor.backpack.ui.inc.header_metas'), 'vendor.backpack.ui.inc.header_metas')

<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>{{ isset($title) ? $title.' :: '.backpack_theme_config('project_name') : backpack_theme_config('project_name') }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#f8fafc">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0f172a">

@yield('before_styles')
@stack('before_styles')

@include(backpack_view('inc.theme_styles'))
@include(backpack_view('inc.styles'))

@yield('after_styles')
@stack('after_styles')
