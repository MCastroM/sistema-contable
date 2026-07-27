@php
    $fmt = fn ($v) => bccomp($v, '0', 2) === 1 || bccomp($v, '0', 2) === -1
        ? number_format((float) $v, 0, ',', '.') : '';
@endphp
<table class="mayor">
    <thead>
        <tr>
            <th style="width:9%">CÓD</th>
            <th style="width:26%">CUENTA</th>
            <th style="width:8%">FECHA</th>
            <th style="width:8%">N° COMP</th>
            <th style="width:24%">GLOSA</th>
            <th style="width:8.3%">DEBE</th>
            <th style="width:8.3%">HABER</th>
            <th style="width:8.3%">SALDO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($mayor['cuentas'] as $c)
            <tr class="separador"><td colspan="8">{{ $c['cuenta']->codigo }} — {{ $c['cuenta']->nombre }}</td></tr>
            <tr class="saldo-anterior">
                <td colspan="7" style="text-align:right"><em>Saldo anterior</em></td>
                <td class="num">{{ $fmt($c['saldoAnterior']) }}</td>
            </tr>
            @foreach ($c['lineas'] as $l)
                @php $m = $l->movimiento; @endphp
                <tr>
                    <td>{{ $c['cuenta']->codigo }}</td>
                    <td class="izq">{{ $c['cuenta']->nombre }}</td>
                    <td>{{ $m->comprobante->fecha->format('d-m-Y') }}</td>
                    <td>{{ $m->comprobante->folio() }}</td>
                    <td class="izq">{{ $m->glosa ?: $m->comprobante->glosa }}</td>
                    <td class="num">{{ $fmt($m->debe) }}</td>
                    <td class="num">{{ $fmt($m->haber) }}</td>
                    <td class="num">{{ $fmt($l->saldo) }}</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="5">SUBTOTAL CUENTA {{ $c['cuenta']->codigo }} - {{ $c['cuenta']->nombre }}</td>
                <td class="num">{{ $fmt($c['totalDebe']) }}</td>
                <td class="num">{{ $fmt($c['totalHaber']) }}</td>
                <td class="num">{{ $fmt($c['saldoFinal']) }}</td>
            </tr>
        @endforeach
        <tr class="total-general">
            <td colspan="5">TOTAL GENERAL</td>
            <td class="num">{{ number_format((float) $mayor['totalDebe'], 2, ',', '.') }}</td>
            <td class="num">{{ number_format((float) $mayor['totalHaber'], 2, ',', '.') }}</td>
            <td class="num">{{ number_format((float) bcsub($mayor['totalDebe'], $mayor['totalHaber'], 2), 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
