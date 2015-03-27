<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('factura_cliente.php');
require_model('pago.php');

/**
 * Guarda el pago de todas las facturas antiguas que están marcadas como pagadas,
 * pero que no tienen su entrada en pagos.
 */
$data = $db->select("SELECT * FROM facturascli WHERE pagada AND idfactura NOT IN (SELECT idfactura FROM pagos);");
foreach($data as $d)
{
   $factura = new factura_cliente($d);
   $pago = new pago();
   $pago->idfactura = $factura->idfactura;
   $pago->fecha = $factura->fecha;
   $pago->fase = 'Factura';
   $pago->importe = $factura->total;
   $pago->save();
}