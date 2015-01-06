<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2014, Carlos García Gómez. All Rights Reserved. 
 */

/**
 * Description of pago
 *
 * @author carlos
 */
class pago extends fs_model
{
   public $id;
   public $idfactura;
   public $idalbaran;
   public $idpedido;
   public $fase;
   public $fecha;
   public $importe;
   public $nota;
   
   public $pendiente;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('pagos', 'plugins/pagos/');
      if($p)
      {
         $this->id = $this->intval($p['id']);
         $this->idfactura = $this->intval($p['idfactura']);
         $this->idalbaran = $this->intval($p['idalbaran']);
         $this->idpedido = $this->intval($p['idpedido']);
         $this->fase = $p['fase'];
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->importe = floatval($p['importe']);
         $this->nota = $p['nota'];
      }
      else
      {
         $this->id = NULL;
         $this->idfactura = NULL;
         $this->idalbaran = NULL;
         $this->idpedido = NULL;
         $this->fase = '-';
         $this->fecha = Date('d-m-Y');
         $this->importe = 0;
         $this->nota = '';
      }
      
      $this->pendiente = 0;
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( !is_null($this->idfactura) )
      {
         return "index.php?page=ventas_factura&id=".$this->idfactura;
      }
      else if( !is_null($this->idalbaran) )
      {
         return "index.php?page=ventas_albaran&id=".$this->idalbaran;
      }
      else if( !is_null($this->idpedido) )
      {
         return "index.php?page=ventas_pedido&id=".$this->idpedido;
      }
      else
         return 'index.php?page=informe_pagos&id='.$this->id;
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM pagos WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new pago($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM pagos WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      $this->nota = $this->no_html($this->nota);
      
      if( $this->exists() )
      {
         $sql = "UPDATE pagos SET idfactura = ".$this->var2str($this->idfactura).", idalbaran = ".$this->var2str($this->idalbaran).",
            idpedido = ".$this->var2str($this->idpedido).", fase = ".$this->var2str($this->fase).",
            fecha = ".$this->var2str($this->fecha).", importe = ".$this->var2str($this->importe).",
            nota = ".$this->var2str($this->nota)." WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO pagos (idfactura,idalbaran,idpedido,fase,fecha,importe,nota) VALUES
            (".$this->var2str($this->idfactura).",".$this->var2str($this->idalbaran).",".$this->var2str($this->idpedido).",
             ".$this->var2str($this->fase).",".$this->var2str($this->fecha).",".$this->var2str($this->importe).",
             ".$this->var2str($this->nota).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM pagos WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_factura($id)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos WHERE idfactura = ".$this->var2str($id)." ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
   
   public function all_from_albaran($id)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos WHERE idalbaran = ".$this->var2str($id)." ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
   
   public function all_from_pedido($id)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos WHERE idpedido = ".$this->var2str($id)." ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
   
   public function all()
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
   
   public function entradas()
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos WHERE importe > 0 ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
   
   public function salidas()
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM pagos WHERE importe < 0 ORDER BY fecha ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pago($d);
      }
      
      return $plist;
   }
}
