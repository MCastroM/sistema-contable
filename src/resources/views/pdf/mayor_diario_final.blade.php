<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 34mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6.3px; color: #222; }

    table.mayor, table.diario { width: 100%; border-collapse: collapse; }
    table.mayor th, table.mayor td, table.diario th, table.diario td {
        border: 0.5px solid #999; padding: 1.3px 2px; font-size: 6.3px;
    }
    table.mayor th, table.diario th { background: #ddd; text-align: center; }
    table.mayor td.num, table.diario td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.mayor td.izq, table.diario td.izq { text-align: left; }
    tr.separador td { background: #333; color: #fff; font-weight: bold; font-size: 6.8px; }
    tr.saldo-anterior td { background: #f2f2f2; font-style: italic; }
    tr.subtotal td, tr.subtotal-asiento td { background: #ddd; font-weight: bold; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7.5px; }

    .salto-diario { page-break-before: always; }
</style>
</head>
<body>

<script type="text/php">
if (isset($pdf)) {
    $folioMap = {!! $folioMapPhp !!};
    $tituloMap = {!! $tituloMapPhp !!};

    $folio = isset($folioMap[$PAGE_NUM]) ? $folioMap[$PAGE_NUM] : '';
    $titulo = isset($tituloMap[$PAGE_NUM]) ? $tituloMap[$PAGE_NUM] : '';

    // Fuentes YA RESUELTAS (rutas de archivo) que vienen del controlador --
    // aqui NO se llama a ningun metodo de fuentes, solo se usan como strings.
    $font = {!! json_encode($fontBoldPath) !!};
    $font_normal = {!! json_encode($fontNormalPath) !!};
    $w = $pdf->get_width();

    $pdf->page_text(28, 20, {!! json_encode($empNombre) !!}, $font, 9);
    $pdf->page_text(28, 32, {!! json_encode($empRutGiro) !!}, $font_normal, 7);
    $pdf->page_text(28, 42, {!! json_encode($empDireccion) !!}, $font_normal, 7);

    if ($folio !== '') {
        // Ancho fijo aproximado (evita depender de getTextWidth dentro
        // del script embebido, que no está disponible aqui de forma fiable)
        $texto = "FOLIO N\xC2\xB0 " . $folio;
        $pdf->page_text($w - 100, 30, $texto, $font, 10);
    }

    $pdf->line(28, 62, $w - 28, 62, array(0,0,0), 0.7);

    if ($titulo !== '') {
        $pdf->page_text(($w / 2) - 90, 70, $titulo, $font, 8.5);
    }
}
</script>

@include('pdf._mayor_tabla')

<div class="salto-diario"></div>
@include('pdf._diario_tabla')

</body>
</html>
