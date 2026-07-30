<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6px; color: #222; }
    table.libro { width: 100%; border-collapse: collapse; }
    table.libro th, table.libro td { border: 0.5px solid #999; padding: 1.2px 1.5px; font-size: 6px; }
    table.libro th { background: #ddd; text-align: center; }
    table.libro td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.libro td.izq { text-align: left; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7px; }
    .pagina { page-break-before: always; }
    .pagina:first-child { page-break-before: avoid; }
</style>
</head>
<body>
@foreach ($bloques as $bloque)
    <div class="pagina">
        <table class="libro">
            <thead>
                <tr>
                    <th style="width:11%">RUT</th><th style="width:16%">Nombre</th>
                    <th style="width:6.4%">Sueldo</th><th style="width:6.4%">Gratif.</th>
                    <th style="width:6.4%">Movil.</th><th style="width:6.4%">Colación</th>
                    <th style="width:6.4%">Otros</th><th style="width:6.4%">T. Haberes</th>
                    <th style="width:6.4%">AFP</th><th style="width:6.4%">Salud</th>
                    <th style="width:6.4%">Cesantía</th><th style="width:6.4%">Imp. Único</th>
                    <th style="width:6.4%">Préstamo</th><th style="width:6.4%">Anticipo</th>
                    <th style="width:6.4%">Líquido</th>
                </tr>
            </thead>
            <tbody>
                @php $fmt = fn ($v) => bccomp($v,'0',2)===1 ? number_format((float)$v,0,',','.') : ''; @endphp
                @foreach ($bloque as $f)
                    @if ($f['tipo'] === 'trabajador')
                        @php $t = $f['trabajador']; @endphp
                        <tr>
                            <td class="izq">{{ $t->rut_trabajador }}</td>
                            <td class="izq">{{ \Illuminate\Support\Str::limit($t->nombre_trabajador, 22, '') }}</td>
                            <td class="num">{{ $fmt($t->sueldo) }}</td>
                            <td class="num">{{ $fmt($t->gratificacion) }}</td>
                            <td class="num">{{ $fmt($t->movilizacion) }}</td>
                            <td class="num">{{ $fmt($t->colacion) }}</td>
                            <td class="num">{{ $fmt($t->otros_haberes) }}</td>
                            <td class="num">{{ $fmt($t->total_haberes) }}</td>
                            <td class="num">{{ $fmt($t->afp) }}</td>
                            <td class="num">{{ $fmt($t->salud) }}</td>
                            <td class="num">{{ $fmt($t->cesantia) }}</td>
                            <td class="num">{{ $fmt($t->impuesto_unico) }}</td>
                            <td class="num">{{ $fmt($t->prestamo) }}</td>
                            <td class="num">{{ $fmt($t->anticipo) }}</td>
                            <td class="num">{{ $fmt($t->liquido) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general')
                        <tr class="total-general">
                            <td colspan="2">TOTAL GENERAL</td>
                            <td class="num">{{ number_format((float)$f['sueldo'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['gratificacion'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['movilizacion'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['colacion'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['otros_haberes'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['total_haberes'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['afp'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['salud'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['cesantia'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['impuesto_unico'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['prestamo'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['anticipo'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['liquido'],0,',','.') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
