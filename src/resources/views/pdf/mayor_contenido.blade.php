<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28mm 12mm 12mm 12mm; }
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

    .pagina { page-break-before: always; }
    .pagina:first-child { page-break-before: avoid; }
</style>
</head>
<body>
@foreach ($bloquesMayor as $bloque)
    <div class="pagina">
        <table class="mayor">
            <thead>
                <tr>
                    <th style="width:9%">CÓD</th><th style="width:26%">CUENTA</th>
                    <th style="width:8%">FECHA</th><th style="width:8%">N° COMP</th>
                    <th style="width:24%">GLOSA</th><th style="width:8.3%">DEBE</th>
                    <th style="width:8.3%">HABER</th><th style="width:8.3%">SALDO</th>
                </tr>
            </thead>
            <tbody>
                @php $fmt = fn ($v) => bccomp($v,'0',2)===1||bccomp($v,'0',2)===-1 ? number_format((float)$v,0,',','.') : ''; @endphp
                @foreach ($bloque as $f)
                    @if ($f['tipo'] === 'separador')
                        <tr class="separador"><td colspan="8">{{ $f['cuenta']->codigo }} — {{ $f['cuenta']->nombre }}</td></tr>
                    @elseif ($f['tipo'] === 'saldo_anterior')
                        <tr class="saldo-anterior"><td colspan="7" style="text-align:right"><em>Saldo anterior</em></td><td class="num">{{ $fmt($f['saldo']) }}</td></tr>
                    @elseif ($f['tipo'] === 'movimiento')
                        @php $m = $f['linea']->movimiento; @endphp
                        <tr>
                            <td>{{ $f['cuenta']->codigo }}</td>
                            <td class="izq">{{ Str::limit($f['cuenta']->nombre, 32, '') }}</td>
                            <td>{{ $m->comprobante->fecha->format('d-m-Y') }}</td>
                            <td>{{ $m->comprobante->folio() }}</td>
                            <td class="izq">{{ Str::limit($m->glosa ?: $m->comprobante->glosa, 36, '') }}</td>
                            <td class="num">{{ $fmt($m->debe) }}</td>
                            <td class="num">{{ $fmt($m->haber) }}</td>
                            <td class="num">{{ $fmt($f['linea']->saldo) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'subtotal')
                        <tr class="subtotal">
                            <td colspan="5">SUBTOTAL CUENTA {{ $f['cuenta']->codigo }} - {{ $f['cuenta']->nombre }}</td>
                            <td class="num">{{ $fmt($f['debe']) }}</td><td class="num">{{ $fmt($f['haber']) }}</td><td class="num">{{ $fmt($f['saldo']) }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general')
                        <tr class="total-general">
                            <td colspan="5">TOTAL GENERAL</td>
                            <td class="num">{{ number_format((float)$f['debe'],2,',','.') }}</td>
                            <td class="num">{{ number_format((float)$f['haber'],2,',','.') }}</td>
                            <td class="num">{{ number_format((float)bcsub($f['debe'],$f['haber'],2),2,',','.') }}</td>
                        </tr>
                    @elseif ($f['tipo'] === 'total_general_anio')
                        <tr class="total-general">
                            <td colspan="5">TOTAL GENERAL AÑO {{ $f['anio'] }}</td>
                            @if (is_numeric($f['debe']))
                                <td class="num">{{ number_format((float)$f['debe'],0,',','.') }}</td>
                                <td class="num">{{ number_format((float)$f['haber'],0,',','.') }}</td>
                                <td class="num">{{ number_format((float)bcsub($f['debe'],$f['haber'],2),2,',','.') }}</td>
                            @else
                                <td colspan="3" style="color:red;text-align:left">{{ $f['debe'] }}</td>
                            @endif
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
