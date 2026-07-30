<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 6.5px; color: #222; }
    table.libro { width: 100%; border-collapse: collapse; }
    table.libro th, table.libro td { border: 0.5px solid #999; padding: 1.3px 2px; font-size: 6.5px; }
    table.libro th { background: #ddd; text-align: center; }
    table.libro td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.libro td.izq { text-align: left; }
    tr.total-general td { background: #ccc; font-weight: bold; border-top: 1.5px solid #000; font-size: 7.5px; }
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
                    <th style="width:5%">N°</th><th style="width:6%">Tipo</th>
                    <th style="width:12%">RUT Cliente</th><th style="width:28%">Razón Social</th>
                    <th style="width:9%">Folio</th><th style="width:9%">Fecha</th>
                    <th style="width:10%">Exento</th><th style="width:10%">Neto</th>
                    <th style="width:5.5%">IVA</th><th style="width:5.5%">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $fmt = fn ($v) => bccomp($v,'0',2)===1 ? number_format((float)$v,0,',','.') : ''; @endphp
                @foreach ($bloque as $f)
                    @if ($f['tipo'] === 'doc')
                        @php $d = $f['doc']; @endphp
                        <tr>
                            <td>{{ $d->nro }}</td>
                            <td>{{ $d->tipo_dte }}</td>
                            <td class="izq">{{ $d->rut_cliente }}</td>
                            <td class="izq">{{ \Illuminate\Support\Str::limit($d->razon_social, 40, '') }}</td>
                            <td>{{ $d->folio }}</td>
                            <td>{{ $d->fecha->format('d-m-Y') }}</td>
                            <td class="num">{{ $fmt($d->exento) }}</td>
                            <td class="num">{{ $fmt($d->neto) }}</td>
                            <td class="num">{{ $fmt($d->iva) }}</td>
                            <td class="num">{{ $fmt($d->total) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general')
                        <tr class="total-general">
                            <td colspan="6">TOTAL GENERAL</td>
                            <td class="num">{{ number_format((float)$f['exento'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['neto'],0,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['iva'],0,',','.') }}</td>
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
