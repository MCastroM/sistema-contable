<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 7px; color: #222; }
    table.libro { width: 100%; border-collapse: collapse; }
    table.libro th, table.libro td { border: 0.5px solid #999; padding: 1.5px 3px; font-size: 7px; }
    table.libro th { background: #ddd; text-align: center; }
    table.libro td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.libro td.izq { text-align: left; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 8px; }
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
                    <th style="width:8%">N° Boleta</th><th style="width:12%">Fecha</th>
                    <th style="width:14%">RUT</th><th style="width:36%">Nombre o Razón Social</th>
                    <th style="width:10%">Brutos</th><th style="width:10%">Retención</th><th style="width:10%">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $fmt = fn ($v) => bccomp($v,'0',2)===1 ? number_format((float)$v,0,',','.') : ''; @endphp
                @foreach ($bloque as $f)
                    @if ($f['tipo'] === 'boleta')
                        @php $b = $f['boleta']; @endphp
                        <tr>
                            <td>{{ $b->boleta ?: $b->nro }}</td>
                            <td>{{ $b->fecha->format('d-m-Y') }}</td>
                            <td class="izq">{{ $b->rut_prestador }}</td>
                            <td class="izq">{{ \Illuminate\Support\Str::limit($b->nombre_prestador, 45, '') }}</td>
                            <td class="num">{{ $fmt($b->brutos) }}</td>
                            <td class="num">{{ $fmt($b->retencion) }}</td>
                            <td class="num">{{ $fmt($b->total) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general')
                        <tr class="total-general">
                            <td colspan="4">TOTAL GENERAL</td>
                            <td class="num">{{ number_format((float)$f['brutos'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['retencion'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['total'],0,',','.') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
