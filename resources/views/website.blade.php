<!DOCTYPE html>
<html class="h-full bg-gray-100">
<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">

    @vite([
      'resources/website/js/website.js',
      'resources/website/css/bootstrap.css',
      'resources/website/css/aos.css',
      'resources/website/css/jquery.nice-number.css',
      'resources/website/css/responsive.css',
      'resources/website/css/style.css',
    ])

    <link
        rel="stylesheet"
        href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"
        crossorigin="anonymous"
    />

    @inertiaHead

</head>
<body>
<!-- ============================================================== -->
<!-- Main wrapper - style you can find in pages.scss -->
<!-- ============================================================== -->

@inertia

{{--<script src="/assets/js/bootstrap.bundle.min.js"></script>--}}
<script src="/assets/js/aos.js"></script>
{{--<script src="/assets/js/main.js"></script>--}}

</body>
</html>
