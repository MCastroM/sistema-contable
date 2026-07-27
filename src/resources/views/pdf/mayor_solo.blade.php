<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /* @page reserva el margen superior SIEMPRE igual, tanto en el conteo
       como en el documento final — así los saltos de página caen en el
       mismo lugar en ambas pasadas y el conteo de páginas es confiable. */
    @page { margin: 34mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6.3px; color: #222; }

    table.mayor { width: 100%; border-collapse: collapse; }
    table.mayor th, table.mayor td { border: 0.5px solid #999; padding: 1.3px 2px; font-size: 6.3px; }
    table.mayor th { background: #ddd; text-align: center; }
    table.mayor td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.mayor td.izq { text-align: left; }
    tr.separador td { background: #333; color: #fff; font-weight: bold; font-size: 6.8px; }
    tr.saldo-anterior td { background: #f2f2f2; font-style: italic; }
    tr.subtotal td { background: #ddd; font-weight: bold; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7.5px; }
</style>
</head>
<body>
@include('pdf._mayor_tabla')
</body>
</html>
