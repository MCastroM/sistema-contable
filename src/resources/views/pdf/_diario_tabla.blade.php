@php
    $fmt = fn ($v) => bccomp($v, '0', 2) === 1 ? number_format((float) $v, 0, ',', '.') : '';
@endphp
<table class="diario">
    <thead>
        <tr>
            <th style="width:6%">CÓD</th>
            <th style="width:9%">FECHA</th>
            <th style="width:9%">N° COMP</th>
            <th style="width:24%">CUENTA CONTABLE</th>
            <th style="width:26%">GLOSA</th>
            <th style="width:10%">C. COSTO</th>
            <th style="width:8%">DEBE</th>
            <th style="width:8%">HABER</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($diario['comprobantes'] as $c)
            @foreach ($c->movimientos as $m)
                <tr>
                    <td>{{ $m->cuenta->codigo }}</td>
                    <td>{{ $c->fecha->format('d-m-Y') }}</td>
                    <td>{{ $c->folio() }}</td>
                    <td class="izq">{{ $m->cuenta->nombre }}</td>
                    <td class="izq">{{ $m->glosa ?: $c->glosa }}</td>
                    <td>{{ $m->centroCosto?->codigo }}</td>
                    <td class="num">{{ $fmt($m->debe) }}</td>
                    <td class="num">{{ $fmt($m->haber) }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-asiento">
                <td colspan="6" style="text-align:right">Suma asiento {{ $c->folio() }}</td>
                <td class="num">{{ number_format((float) $c->totalDebe(), 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) $c->totalHaber(), 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-general">
            <td colspan="6">TOTAL GENERAL</td>
            <td class="num">{{ number_format((float) $diario['totalDebe'], 2, ',', '.') }}</td>
            <td class="num">{{ number_format((float) $diario['totalHaber'], 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
