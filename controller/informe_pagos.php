<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2014, Carlos García Gómez. All Rights Reserved. 
 */

require_model('pago.php');

/**
 * Description of informe_pagos
 *
 * @author carlos
 */
class informe_pagos extends fs_controller
{
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pagos', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $pago = new pago();
      $this->resultados = $pago->all();
   }
}
