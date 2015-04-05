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

require_model('albaran_cliente.php');
require_model('factura_cliente.php');
require_model('pago.php');
require_model('pedido_cliente.php');

/**
 * Description of informe_pago
 *
 * @author carlos
 */
class tab_pagos extends fs_controller
{
   public $pagado;
   public $pago;
   public $pagos;
   public $pendiente;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'pago', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->pagado = FALSE;
      $this->pago = new pago();
      $this->pagos = array();
      $this->pendiente = 0;
      
      if( isset($_GET['delete']) ) /// eliminar pago
      {
         $pago = $this->pago->get($_GET['delete']);
         if($pago)
         {
            if( $pago->delete() )
            {
               $this->new_message('Pago eliminado correctamente.');
               
               if( !is_null($pago->idfactura) )
               {
                  $fact0 = new factura_cliente();
                  $factura = $fact0->get($pago->idfactura);
                  if($factura)
                  {
                     if($factura->pagada)
                     {
                        $factura->pagada = FALSE;
                        $factura->save();
                     }
                  }
               }
            }
            else
               $this->new_error_msg('Error al eliminar el pago.');
         }
         else
            $this->new_error_msg('Pago no encontrado.');
      }
      else if( isset($_POST['idpago']) ) /// modificar pago
      {
         $pago = $this->pago->get($_POST['idpago']);
         if($pago)
         {
            $pago->fecha = $_POST['fecha'];
            $pago->importe = floatval($_POST['importe']);
            $pago->nota = $_POST['nota'];
            
            if( $pago->save() )
            {
               $this->new_message('Pago modificado correctamente.');
            }
            else
               $this->new_error_msg('Error al modificar el pago.');
         }
         else
            $this->new_error_msg('Pago no encontrado.');
      }
      else if( isset($_POST['importe']) ) /// nuevo pago
      {
         if( isset($_REQUEST['factura']) )
         {
            $this->pago->fase = 'Factura';
            $this->pago->idfactura = $_REQUEST['id'];
         }
         else if( isset($_REQUEST['albaran']) )
         {
            $this->pago->fase = ucfirst(FS_ALBARAN);
            $this->pago->idalbaran = $_REQUEST['id'];
         }
         else if( isset($_REQUEST['pedido']) )
         {
            $this->pago->fase = ucfirst(FS_PEDIDO);
            $this->pago->idpedido = $_REQUEST['id'];
         }
         
         $this->pago->fecha = $_POST['fecha'];
         $this->pago->importe = floatval($_POST['importe']);
         $this->pago->nota = $_POST['nota'];
         
         if( $this->pago->save() )
         {
            $this->new_message('Pago guardado correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar el pago.');
      }
      
      if( isset($_REQUEST['factura']) )
      {
         /// esto es la fase de factura
         $fact0 = new factura_cliente();
         $factura = $fact0->get($_REQUEST['id']);
         if($factura)
         {
            /// buscamos pagos de la fase albarán
            /// una factura puede ser una agrupación de muchos albaranes
            $idalbaran = NULL;
            foreach($factura->get_lineas() as $linea)
            {
               /// el idalbaran lo tienes en las lineas de la factura
               if($linea->idalbaran != $idalbaran)
               {
                  $idalbaran = $linea->idalbaran;
                  $this->db->exec("UPDATE pagos SET idfactura = ".$fact0->var2str($_REQUEST['id'])." WHERE idalbaran = ".$fact0->var2str($idalbaran).";");
               }
            }

            $this->pagos = $this->pago->all_from_factura($_REQUEST['id']);
            $this->pendiente = $factura->total;
            foreach($this->pagos as $i => $value)
            {
               $this->pendiente -= $value->importe;
               $this->pagos[$i]->pendiente = $this->pendiente;
            }
            
            /// si nos han pagado el total, marcamos la factura como pagada
            if( !$factura->pagada AND abs($this->pendiente) < 0.1 )
            {
               $factura->pagada = TRUE;
               $factura->save();
            }
            
            $this->pagado = $factura->pagada;
         }
      }
      else if( isset($_REQUEST['albaran']) )
      {

         $this->pagos = $this->pago->all_from_albaran($_REQUEST['id']);



         $alb0 = new albaran_cliente();
         $albaran = $alb0->get($_REQUEST['id']);


         if($albaran)
         {

            $idpedido = NULL;
            foreach($albaran->get_lineas() as $linea)
            {

               if($linea->idpedido != $idpedido)
               {
                  $idpedido = $linea->idpedido;
                  $this->db->exec("UPDATE pagos SET idalbaran = ".$alb0->var2str($_REQUEST['id'])." WHERE idpedido = ".$alb0->var2str($idpedido).";");
               }
            }

            $this->pagos = $this->pago->all_from_albaran($_REQUEST['id']);
            $this->pendiente = $albaran->total;

            foreach($this->pagos as $i => $value)
            {
               $this->pendiente -= $value->importe;
               $this->pagos[$i]->pendiente = $this->pendiente;
            }
            
            if( abs($this->pendiente) < 0.1 )
            {
               $this->pagado = TRUE;
            }
         }

      }
      else if( isset($_REQUEST['pedido']) )
      {
         /// fose de pedido
         $this->pagos = $this->pago->all_from_pedido($_REQUEST['id']);
         
         $ped0 = new pedido_cliente();
         $pedido = $ped0->get($_REQUEST['id']);
         if($pedido)
         {
            $this->pendiente = $pedido->total;
            foreach($this->pagos as $i => $value)
            {
               $this->pendiente -= $value->importe;
               $this->pagos[$i]->pendiente = $this->pendiente;
            }
            
            if( abs($this->pendiente) < 0.1 )
            {
               $this->pagado = TRUE;
            }
         }
      }
      
      $this->share_extensions();
   }
   
   public function url()
   {
      if( isset($_REQUEST['factura']) )
      {
         return 'index.php?page='.__CLASS__.'&factura=TRUE&id='.$_REQUEST['id'];
      }
      else if( isset($_REQUEST['albaran']) )
      {
         return 'index.php?page='.__CLASS__.'&albaran=TRUE&id='.$_REQUEST['id'];
      }
      else if( isset($_REQUEST['pedido']) )
      {
         return 'index.php?page='.__CLASS__.'&pedido=TRUE&id='.$_REQUEST['id'];
      }
      else
         return parent::url();
   }
   
   private function share_extensions()
   {
      $extensiones = array(
          array(
              'name' => 'pago_factura',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'tab',
              'text' => 'Pagos',
              'params' => '&factura=TRUE'
          ),
          array(
              'name' => 'pago_albaran',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_albaran',
              'type' => 'tab',
              'text' => 'Pagos',
              'params' => '&albaran=TRUE'
          ),
          array(
              'name' => 'pago_pedido',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_pedido',
              'type' => 'tab',
              'text' => 'Pagos',
              'params' => '&pedido=TRUE'
          )
      );
      foreach($extensiones as $ext)
      {
         $fsext = new fs_extension($ext);
         if( !$fsext->save() )
         {
            $this->new_error_msg('Imposible guardar los datos de la extensión '.$ext['name'].'.');
         }
      }
   }
}
