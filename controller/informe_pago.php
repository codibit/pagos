<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2014, Carlos García Gómez. All Rights Reserved. 
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
class informe_pago extends fs_controller
{
   public $pagado;
   public $pago;
   public $pagos;
   public $pendiente;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'pago', 'informes', FALSE, FALSE);
   }
   
   protected function process()
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
            }
            else
               $this->new_error_msg('Error al eliminar el pago.');
         }
         else
            $this->new_error_msg('Pago no encontrado.');
      }
      else if( isset($_POST['idpago']) )
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
         $this->pago->save();
      }
      
      if( isset($_REQUEST['factura']) )
      {
         $fact0 = new factura_cliente();
         $factura = $fact0->get($_REQUEST['id']);
         if($factura)
         {
            /// buscamos pagos de la fase albarán
            $idalbaran = NULL;
            foreach($factura->get_lineas() as $linea)
            {
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
