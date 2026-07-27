<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Melanjutkan tindakan</title></head>
<body>
    <form method="POST" action="{{ $action }}" id="reauth-resume-form">
        @csrf
        @if (! in_array($method, ['GET', 'POST'], true)) @method($method) @endif
        @foreach ($input as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        <button type="submit">Lanjutkan tindakan</button>
    </form>
    <script>document.getElementById('reauth-resume-form').submit();</script>
</body>
</html>
