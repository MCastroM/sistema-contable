<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 34mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6.3px; color: #222; }

    table.diario { width: 100%; border-collapse: collapse; }
    table.diario th, table.diario td { border: 0.5px solid #999; padding: 1.3px 2px; font-size: 6.3px; }
    table.diario th { background: #ddd; text-align: center; }
    table.diario td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.diario td.izq { text-align: left; }
    tr.subtotal-asiento td { background: #eee; font-weight: bold; font-size: 6px; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7.5px; }
</style>
</head>
<body>
@include('pdf._diario_tabla')
</body>
</html>
