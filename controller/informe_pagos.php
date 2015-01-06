<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved. 
 */

require_model('pago.php');

/**
 * Description of informe_pagos
 *
 * @author carlos
 */
class informe_pagos extends fs_controller
{
   public $pago;
   public $resultados;
   public $tipo;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pagos', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->pago = FALSE;
      $pago = new pago();
      
      if( isset($_REQUEST['id']) )
      {
         $this->pago = $pago->get($_REQUEST['id']);
         
         if( isset($_POST['ajax']) )
         {
            $this->template = 'ajax_pago';
         }
      }
      else if( isset($_POST['nota']) )
      {
         $pago->nota = $_POST['nota'];
         $pago->importe = floatval($_POST['importe']);
         $pago->fecha = $_POST['fecha'];
         
         if( $pago->save() )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar los datos.');
      }
      else if( isset($_GET['delete']) )
      {
         $pago2 = $pago->get($_GET['delete']);
         if($pago2)
         {
            if( $pago2->delete() )
            {
               $this->new_message('Pago '.$_GET['delete'].' eliminado correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar el pago '.$_GET['delete']);
         }
         else
            $this->new_error_msg('Pago '.$_GET['delete'].' no encontrado.');
      }
      
      $this->tipo = FALSE;
      
      if( isset($_REQUEST['tipo']) )
      {
         if($_REQUEST['tipo'] == 'entradas')
         {
            $this->tipo = 'entradas';
            $this->resultados = $pago->entradas();
         }
         else if($_REQUEST['tipo'] == 'salidas')
         {
            $this->tipo = 'salidas';
            $this->resultados = $pago->salidas();
         }
      }
      else
         $this->resultados = $pago->all();
   }
}
