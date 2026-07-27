<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6.3px; color: #222; }

    table.diario { width: 100%; border-collapse: collapse; }
    table.diario th, table.diario td { border: 0.5px solid #999; padding: 1.3px 2px; font-size: 6.3px; }
    table.diario th { background: #ddd; text-align: center; }
    table.diario td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.diario td.izq { text-align: left; }
    tr.subtotal-asiento td { background: #eee; font-weight: bold; font-size: 6.3px; padding: 1.3px 2px; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7.5px; }

    .pagina { page-break-before: always; }
    .pagina:first-child { page-break-before: avoid; }
</style>
</head>
<body>
@foreach ($bloquesDiario as $bloque)
    <div class="pagina">
        <table class="diario">
            <thead>
                <tr>
                    <th style="width:6%">CÓD</th><th style="width:9%">FECHA</th>
                    <th style="width:9%">N° COMP</th><th style="width:24%">CUENTA CONTABLE</th>
                    <th style="width:26%">GLOSA</th><th style="width:10%">C. COSTO</th>
                    <th style="width:8%">DEBE</th><th style="width:8%">HABER</th>
                </tr>
            </thead>
            <tbody>
                @php $fmtd = fn ($v) => bccomp($v,'0',2)===1 ? number_format((float)$v,0,',','.') : ''; @endphp
                @foreach ($bloque as $f)
                    @if ($f['tipo'] === 'movimiento')
                        @php $m = $f['mov']; $c = $f['comprobante']; @endphp
                        <tr>
                            <td>{{ $m->cuenta->codigo }}</td>
                            <td>{{ $c->fecha->format('d-m-Y') }}</td>
                            <td>{{ $c->folio() }}</td>
                            <td class="izq">{{ Str::limit($m->cuenta->nombre, 34, '') }}</td>
                            <td class="izq">{{ Str::limit($m->glosa ?: $c->glosa, 40, '') }}</td>
                            <td>{{ $m->centroCosto?->codigo }}</td>
                            <td class="num">{{ $fmtd($m->debe) }}</td>
                            <td class="num">{{ $fmtd($m->haber) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'subtotal_asiento')
                        <tr class="subtotal-asiento">
                            <td colspan="6" style="text-align:right">Suma asiento {{ $f['comprobante']->folio() }}</td>
                            <td class="num">{{ number_format((float)$f['comprobante']->totalDebe(),0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['comprobante']->totalHaber(),0,',','.') }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general')
                        <tr class="total-general">
                            <td colspan="6">TOTAL GENERAL</td>
                            <td class="num">{{ number_format((float)$f['debe'],2,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['haber'],2,',','.') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
