<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; }
    .pagina { page-break-before: always; }
    .pagina:first-child { page-break-before: avoid; }
</style>
</head>
<body>
@for ($i = 0; $i < $cantidad; $i++)
    <div class="pagina">&nbsp;</div>
@endfor
</body>
</html>
