<!DOCTYPE html>
<html class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    {{-- Inertia --}}
    <script src="https://polyfill.io/v3/polyfill.min.js?features=smoothscroll,NodeList.prototype.forEach,Promise,Object.values,Object.assign" defer></script>

    @vite([
      'resources/backoffice/js/backoffice.js',
      // 'resources/backoffice/css/backoffice.css',
    ])

    @inertiaHead
</head>
<body class="horizontal-nav skin-bo-theme fixed-layout">
<!-- ============================================================== -->
<!-- Main wrapper - style you can find in pages.scss -->
<!-- ============================================================== -->

@routes
@inertia

</body>
</html>
