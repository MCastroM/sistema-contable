<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 8px; color: #222; }
    h1 { text-align: center; font-size: 12px; margin: 2px 0 6px 0; }

    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th, table.datos td {
        border: 0.5px solid #999; padding: 1px 3px; font-size: 6.3px;
    }
    table.datos th { background: #ddd; text-align: center; }
    table.datos td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.datos td.izq { text-align: left; }
    tr.clase-header td {
        background: #eee; font-weight: bold; font-size: 7px; padding: 2px;
    }
    tr.totales td {
        background: #ccc; font-weight: bold; border-top: 1.5px solid #000; padding: 1.3px 3px;
    }
    tr.resultado td {
        background: #f5f5f5; font-style: italic; padding: 1.3px 3px;
    }
    tr.totales-iguales td {
        background: #ccc; font-weight: bold; border-top: 1px solid #000; padding: 1.3px 3px;
    }
</style>
</head>
<body>

    <h1>BALANCE TRIBUTARIO DE 8 COLUMNAS - EJERCICIO {{ $hasta->format('Y') }}</h1>

    <table class="datos">
        <thead>
            <tr>
                <th rowspan="2">CÓDIGO</th>
                <th rowspan="2">CUENTA</th>
                <th colspan="2">SUMAS</th>
                <th colspan="2">SALDOS</th>
                <th colspan="2">INVENTARIO (BALANCE)</th>
                <th colspan="2">RESULTADO (PÉRD. Y GAN.)</th>
            </tr>
            <tr>
                <th>DEBE</th><th>HABER</th>
                <th>DEUDOR</th><th>ACREEDOR</th>
                <th>ACTIVO</th><th>PASIVO</th>
                <th>PÉRDIDA</th><th>GANANCIA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $etiquetas = ['activo' => 'ACTIVO', 'pasivo' => 'PASIVO',
                              'patrimonio' => 'PATRIMONIO', 'resultado' => 'RESULTADO (Ganancias y Pérdidas)'];
                $fmt = fn ($v) => bccomp($v, '0', 2) === 1 ? number_format((float) $v, 0, ',', '.') : '';
            @endphp
            @foreach ($etiquetas as $clase => $etiqueta)
                @continue(! $porClase->has($clase))
                <tr class="clase-header"><td colspan="10">{{ $etiqueta }}</td></tr>
                @foreach ($porClase[$clase] as $f)
                    <tr>
                        <td>{{ $f->cuenta->codigo }}</td>
                        <td class="izq">{{ $f->cuenta->nombre }}</td>
                        <td class="num">{{ $fmt($f->debe) }}</td>
                        <td class="num">{{ $fmt($f->haber) }}</td>
                        <td class="num">{{ $fmt($f->saldoDeudor) }}</td>
                        <td class="num">{{ $fmt($f->saldoAcreedor) }}</td>
                        <td class="num">{{ $fmt($f->activoCol) }}</td>
                        <td class="num">{{ $fmt($f->pasivoCol) }}</td>
                        <td class="num">{{ $fmt($f->perdidaCol) }}</td>
                        <td class="num">{{ $fmt($f->ganciaCol) }}</td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="totales">
                <td colspan="2">SUMAS TOTALES</td>
                <td class="num">{{ number_format((float) $totales['debe'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['haber'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['deudor'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['acreedor'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['activo'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['pasivo'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['perdida'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totales['ganancia'], 0, ',', '.') }}</td>
            </tr>
            @php $fmtP = fn ($v) => bccomp($v,'0',2)===1 ? number_format((float)$v,0,',','.') : '-'; @endphp
            <tr class="resultado">
                <td colspan="2">RESULTADO DEL EJERCICIO</td>
                <td class="num">-</td>
                <td class="num">-</td>
                <td class="num">-</td>
                <td class="num">-</td>
                <td class="num">{{ $fmtP($resultadoEjercicio['activo']) }}</td>
                <td class="num">{{ $fmtP($resultadoEjercicio['pasivo']) }}</td>
                <td class="num">{{ $fmtP($resultadoEjercicio['perdida']) }}</td>
                <td class="num">{{ $fmtP($resultadoEjercicio['ganancia']) }}</td>
            </tr>
            <tr class="totales-iguales">
                <td colspan="2">TOTALES IGUALES</td>
                <td class="num">{{ number_format((float) $totalesIguales['debe'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['haber'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['deudor'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['acreedor'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['activo'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['pasivo'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['perdida'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $totalesIguales['ganancia'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
