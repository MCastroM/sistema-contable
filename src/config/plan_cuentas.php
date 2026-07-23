<?php

/**
 * Plantilla del plan de cuentas estándar chileno.
 *
 * Convención del código inteligente:
 *   Nivel 1  X          -> clase (1 Activo, 2 Pasivo, 3 Patrimonio, 4 Ganancias, 5 Pérdidas)
 *   Nivel 2  X.X        -> rubro
 *   Nivel 3  X.X.NN     -> grupo
 *   Nivel 4  X.X.NN.NNN -> cuenta imputable (la única que recibe movimientos)
 *
 * La clase se deduce del primer dígito; la imputabilidad, de no tener hijas.
 * Edita libremente: agrega, quita o renombra cuentas antes de instalar.
 */

return [

    // ═══════════════════ 1. ACTIVO ═══════════════════
    ['codigo' => '1', 'nombre' => 'ACTIVO', 'hijas' => [

        ['codigo' => '1.1', 'nombre' => 'Activo Corriente', 'hijas' => [

            ['codigo' => '1.1.01', 'nombre' => 'Efectivo y Equivalentes', 'hijas' => [
                ['codigo' => '1.1.01.001', 'nombre' => 'Caja'],
                ['codigo' => '1.1.01.002', 'nombre' => 'Caja chica'],
                ['codigo' => '1.1.01.003', 'nombre' => 'Banco cuenta corriente'],
            ]],

            ['codigo' => '1.1.02', 'nombre' => 'Deudores Comerciales', 'hijas' => [
                ['codigo' => '1.1.02.001', 'nombre' => 'Clientes'],
                ['codigo' => '1.1.02.002', 'nombre' => 'Documentos por cobrar'],
                ['codigo' => '1.1.02.003', 'nombre' => 'Deudores varios'],
                ['codigo' => '1.1.02.004', 'nombre' => 'Anticipos a proveedores'],
                ['codigo' => '1.1.02.005', 'nombre' => 'Cuentas por cobrar a socios'],
            ]],

            ['codigo' => '1.1.03', 'nombre' => 'Impuestos por Recuperar', 'hijas' => [
                ['codigo' => '1.1.03.001', 'nombre' => 'IVA crédito fiscal'],
                ['codigo' => '1.1.03.002', 'nombre' => 'Remanente de IVA'],
                ['codigo' => '1.1.03.003', 'nombre' => 'Pagos provisionales mensuales (PPM)'],
                ['codigo' => '1.1.03.004', 'nombre' => 'Otros impuestos por recuperar'],
            ]],

            ['codigo' => '1.1.04', 'nombre' => 'Existencias', 'hijas' => [
                ['codigo' => '1.1.04.001', 'nombre' => 'Mercaderías'],
                ['codigo' => '1.1.04.002', 'nombre' => 'Materias primas'],
                ['codigo' => '1.1.04.003', 'nombre' => 'Productos en proceso'],
                ['codigo' => '1.1.04.004', 'nombre' => 'Productos terminados'],
            ]],

            ['codigo' => '1.1.05', 'nombre' => 'Otros Activos Corrientes', 'hijas' => [
                ['codigo' => '1.1.05.001', 'nombre' => 'Gastos pagados por anticipado'],
                ['codigo' => '1.1.05.002', 'nombre' => 'Garantías de arriendo'],
            ]],
        ]],

        ['codigo' => '1.2', 'nombre' => 'Activo No Corriente', 'hijas' => [

            ['codigo' => '1.2.01', 'nombre' => 'Propiedad, Planta y Equipo', 'hijas' => [
                ['codigo' => '1.2.01.001', 'nombre' => 'Terrenos'],
                ['codigo' => '1.2.01.002', 'nombre' => 'Construcciones y edificaciones'],
                ['codigo' => '1.2.01.003', 'nombre' => 'Vehículos'],
                ['codigo' => '1.2.01.004', 'nombre' => 'Muebles y útiles'],
                ['codigo' => '1.2.01.005', 'nombre' => 'Equipos computacionales'],
                ['codigo' => '1.2.01.006', 'nombre' => 'Maquinarias y equipos'],
            ]],

            ['codigo' => '1.2.02', 'nombre' => 'Depreciación Acumulada', 'hijas' => [
                ['codigo' => '1.2.02.001', 'nombre' => 'Depreciación acumulada (menos)'],
            ]],

            ['codigo' => '1.2.03', 'nombre' => 'Activos Intangibles', 'hijas' => [
                ['codigo' => '1.2.03.001', 'nombre' => 'Software y licencias'],
                ['codigo' => '1.2.03.002', 'nombre' => 'Marcas y patentes'],
            ]],
        ]],
    ]],

    // ═══════════════════ 2. PASIVO ═══════════════════
    ['codigo' => '2', 'nombre' => 'PASIVO', 'hijas' => [

        ['codigo' => '2.1', 'nombre' => 'Pasivo Corriente', 'hijas' => [

            ['codigo' => '2.1.01', 'nombre' => 'Cuentas por Pagar Comerciales', 'hijas' => [
                ['codigo' => '2.1.01.001', 'nombre' => 'Proveedores'],
                ['codigo' => '2.1.01.002', 'nombre' => 'Documentos por pagar'],
                ['codigo' => '2.1.01.003', 'nombre' => 'Acreedores varios'],
                ['codigo' => '2.1.01.004', 'nombre' => 'Anticipos de clientes'],
            ]],

            ['codigo' => '2.1.02', 'nombre' => 'Impuestos por Pagar', 'hijas' => [
                ['codigo' => '2.1.02.001', 'nombre' => 'IVA débito fiscal'],
                ['codigo' => '2.1.02.002', 'nombre' => 'IVA por pagar'],
                ['codigo' => '2.1.02.003', 'nombre' => 'Impuesto único a los trabajadores'],
                ['codigo' => '2.1.02.004', 'nombre' => 'Retención honorarios'],
                ['codigo' => '2.1.02.005', 'nombre' => 'Impuesto a la renta por pagar'],
            ]],

            ['codigo' => '2.1.03', 'nombre' => 'Obligaciones Laborales', 'hijas' => [
                ['codigo' => '2.1.03.001', 'nombre' => 'Remuneraciones por pagar'],
                ['codigo' => '2.1.03.002', 'nombre' => 'Cotizaciones previsionales por pagar'],
                ['codigo' => '2.1.03.003', 'nombre' => 'Provisión de vacaciones'],
                ['codigo' => '2.1.03.004', 'nombre' => 'Provisión indemnizaciones'],
            ]],

            ['codigo' => '2.1.04', 'nombre' => 'Obligaciones Financieras Corto Plazo', 'hijas' => [
                ['codigo' => '2.1.04.001', 'nombre' => 'Préstamos bancarios corto plazo'],
                ['codigo' => '2.1.04.002', 'nombre' => 'Línea de crédito'],
                ['codigo' => '2.1.04.003', 'nombre' => 'Tarjetas de crédito'],
            ]],

            ['codigo' => '2.1.05', 'nombre' => 'Cuentas por Pagar a Socios', 'hijas' => [
                ['codigo' => '2.1.05.001', 'nombre' => 'Cuenta corriente socios (pasivo)'],
            ]],
        ]],

        ['codigo' => '2.2', 'nombre' => 'Pasivo No Corriente', 'hijas' => [
            ['codigo' => '2.2.01', 'nombre' => 'Obligaciones Financieras Largo Plazo', 'hijas' => [
                ['codigo' => '2.2.01.001', 'nombre' => 'Préstamos bancarios largo plazo'],
                ['codigo' => '2.2.01.002', 'nombre' => 'Leasing por pagar'],
            ]],
        ]],
    ]],

    // ═══════════════════ 3. PATRIMONIO ═══════════════════
    ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'hijas' => [
        ['codigo' => '3.1', 'nombre' => 'Patrimonio', 'hijas' => [
            ['codigo' => '3.1.01', 'nombre' => 'Capital y Reservas', 'hijas' => [
                ['codigo' => '3.1.01.001', 'nombre' => 'Capital'],
                ['codigo' => '3.1.01.002', 'nombre' => 'Reservas'],
                ['codigo' => '3.1.01.003', 'nombre' => 'Revalorización del capital propio'],
            ]],
            ['codigo' => '3.1.02', 'nombre' => 'Resultados', 'hijas' => [
                ['codigo' => '3.1.02.001', 'nombre' => 'Resultados acumulados'],
                ['codigo' => '3.1.02.002', 'nombre' => 'Resultado del ejercicio'],
                ['codigo' => '3.1.02.003', 'nombre' => 'Retiros de socios (menos)'],
            ]],
        ]],
    ]],

    // ═══════════════════ 4. GANANCIAS ═══════════════════
    ['codigo' => '4', 'nombre' => 'GANANCIAS', 'hijas' => [
        ['codigo' => '4.1', 'nombre' => 'Ingresos Operacionales', 'hijas' => [
            ['codigo' => '4.1.01', 'nombre' => 'Ventas y Servicios', 'hijas' => [
                ['codigo' => '4.1.01.001', 'nombre' => 'Ventas'],
                ['codigo' => '4.1.01.002', 'nombre' => 'Ingresos por servicios'],
                ['codigo' => '4.1.01.003', 'nombre' => 'Devoluciones sobre ventas (menos)'],
            ]],
        ]],
        ['codigo' => '4.2', 'nombre' => 'Ingresos No Operacionales', 'hijas' => [
            ['codigo' => '4.2.01', 'nombre' => 'Otros Ingresos', 'hijas' => [
                ['codigo' => '4.2.01.001', 'nombre' => 'Ingresos financieros'],
                ['codigo' => '4.2.01.002', 'nombre' => 'Utilidad en venta de activos'],
                ['codigo' => '4.2.01.003', 'nombre' => 'Corrección monetaria (ganancia)'],
                ['codigo' => '4.2.01.004', 'nombre' => 'Otros ingresos'],
            ]],
        ]],
    ]],

    // ═══════════════════ 5. PÉRDIDAS ═══════════════════
    ['codigo' => '5', 'nombre' => 'PÉRDIDAS', 'hijas' => [

        ['codigo' => '5.1', 'nombre' => 'Costos', 'hijas' => [
            ['codigo' => '5.1.01', 'nombre' => 'Costo de Ventas', 'hijas' => [
                ['codigo' => '5.1.01.001', 'nombre' => 'Costo de ventas'],
                ['codigo' => '5.1.01.002', 'nombre' => 'Costo de servicios'],
            ]],
        ]],

        ['codigo' => '5.2', 'nombre' => 'Gastos de Administración y Ventas', 'hijas' => [

            ['codigo' => '5.2.01', 'nombre' => 'Gastos del Personal', 'hijas' => [
                ['codigo' => '5.2.01.001', 'nombre' => 'Sueldos'],
                ['codigo' => '5.2.01.002', 'nombre' => 'Leyes sociales'],
                ['codigo' => '5.2.01.003', 'nombre' => 'Honorarios'],
                ['codigo' => '5.2.01.004', 'nombre' => 'Gratificaciones y bonos'],
                ['codigo' => '5.2.01.005', 'nombre' => 'Indemnizaciones'],
            ]],

            ['codigo' => '5.2.02', 'nombre' => 'Gastos de Operación', 'hijas' => [
                ['codigo' => '5.2.02.001', 'nombre' => 'Arriendos'],
                ['codigo' => '5.2.02.002', 'nombre' => 'Servicios básicos'],
                ['codigo' => '5.2.02.003', 'nombre' => 'Telefonía e internet'],
                ['codigo' => '5.2.02.004', 'nombre' => 'Materiales de oficina'],
                ['codigo' => '5.2.02.005', 'nombre' => 'Mantención y reparaciones'],
                ['codigo' => '5.2.02.006', 'nombre' => 'Seguros'],
                ['codigo' => '5.2.02.007', 'nombre' => 'Publicidad y marketing'],
                ['codigo' => '5.2.02.008', 'nombre' => 'Gastos de movilización'],
                ['codigo' => '5.2.02.009', 'nombre' => 'Patentes y contribuciones'],
                ['codigo' => '5.2.02.010', 'nombre' => 'Gastos generales'],
            ]],

            ['codigo' => '5.2.03', 'nombre' => 'Depreciación y Amortización', 'hijas' => [
                ['codigo' => '5.2.03.001', 'nombre' => 'Depreciación del ejercicio'],
                ['codigo' => '5.2.03.002', 'nombre' => 'Amortización de intangibles'],
            ]],
        ]],

        ['codigo' => '5.3', 'nombre' => 'Gastos No Operacionales', 'hijas' => [
            ['codigo' => '5.3.01', 'nombre' => 'Gastos Financieros y Otros', 'hijas' => [
                ['codigo' => '5.3.01.001', 'nombre' => 'Intereses y comisiones bancarias'],
                ['codigo' => '5.3.01.002', 'nombre' => 'Multas e intereses fiscales'],
                ['codigo' => '5.3.01.003', 'nombre' => 'Corrección monetaria (pérdida)'],
                ['codigo' => '5.3.01.004', 'nombre' => 'Pérdida en venta de activos'],
                ['codigo' => '5.3.01.005', 'nombre' => 'Otros gastos'],
            ]],
        ]],
    ]],
];
