@php
// Look up presentation from global presentations collection
$presentation = $page->presentations->get($page->presentationId);
$firstStep = $presentation ? $presentation->getFirstStep() : null;
$redirectUrl = $firstStep ? $page->getPresentationStepUrl($presentation->id, $firstStep->id) : '/';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0; url={{ $redirectUrl }}">
    <title>Redirecting...</title>
</head>
<body>
    <p>Redirecting to <a href="{{ $redirectUrl }}">{{ $redirectUrl }}</a>...</p>
</body>
</html>
