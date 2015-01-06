<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved. 
 */

require_model('cliente.php');

/**
 * Description of pagar_facturas
 *
 * @author carlos
 */
class pagar_facturas extends fs_controller
{
   public $codcliente;
   public $codserie;
   public $desde;
   public $hasta;
   public $resultados;
   public $serie;
   
   public function __construct() {
      parent::__construct(__CLASS__, 'Pagar facturas', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->serie = new serie();
      $this->share_extensions();
      
      if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      
      $this->desde = Date('01-m-Y');
      if( isset($_POST['desde']) )
      {
         $this->desde = $_POST['desde'];
      }
      
      $this->hasta = Date('d-m-Y');
      if( isset($_POST['hasta']) )
      {
         $this->hasta = $_POST['hasta'];
      }
      
      $this->codcliente = FALSE;
      if( !isset($_POST['todos']) AND isset($_POST['codcliente']) )
      {
         $this->codcliente = $_POST['codcliente'];
      }
      
      $this->codserie = FALSE;
      if( isset($_POST['codserie']) )
      {
         $this->codserie = $_POST['codserie'];
      }
      
      /// ¿Marcamos ya las facturas?
      if( isset($_POST['idfactura']) )
      {
         $num = 0;
         
         $fact0 = new factura_cliente();
         foreach($_POST['idfactura'] as $id)
         {
            $factura = $fact0->get($id);
            if($factura)
            {
               $factura->pagada = TRUE;
               $factura->save();
               $num++;
            }
         }
         
         $this->new_message($num.' facturas marcadas como pagadas, estas son las siguientes.');
      }
      
      $this->resultados = FALSE;
      if( isset($_POST['desde']) )
      {
         $this->resultados = $this->buscar_facturas();
      }
   }
   
   private function share_extensions()
   {
      $extension = array(
          'name' => 'pagar_facturas',
          'page_from' => __CLASS__,
          'page_to' => 'ventas_facturas',
          'type' => 'button',
          'text' => '<span class="glyphicon glyphicon-check" aria-hidden="true"></span> &nbsp; Pagar...',
          'params' => ''
      );
      $fsext = new fs_extension($extension);
      $fsext->save();
   }
   
   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $cliente = new cliente();
      $json = array();
      foreach($cliente->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }
   
   private function buscar_facturas()
   {
      $facturas = array();
      $sql = "SELECT * FROM facturascli WHERE pagada = false AND fecha >= ".$this->serie->var2str($_POST['desde']).
              " AND fecha <= ".$this->serie->var2str($_POST['hasta']).
              " AND codserie = ".$this->serie->var2str($_POST['codserie']);
      if( !isset($_POST['todos']) )
      {
         $sql .= " AND codcliente = ".$this->serie->var2str($_POST['codcliente']);
      }
      
      $data = $this->db->select_limit($sql, 100, 0);
      if($data)
      {
         foreach($data as $d)
            $facturas[] = new factura_cliente($d);
      }
      
      return $facturas;
   }
}
