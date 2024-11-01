<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class wee_weemailClass {


	function __construct(){}
	
      // ATRIBUTOS
      // ---------
      VAR $cod_html;
      VAR $campos = array();
      VAR $ini_car = '{'; // Carácter de escape al inicio del campo
      VAR $fin_car = '}'; // Carácter de escape al final del campo

      // MÉTODOS
      // -------
      // Dado un fichero de plantilla, lo carga en el atributo $cod_html
      function wee_cargarPlantilla($nom_fich)
      {
          // Abrimos el fichero que contiene la plantilla
          $fh = fopen($nom_fich, "r") or die("Error: No se ha podido abrir el fichero $nom_fich");
          
          // Cargamos su contenido
          $contenido = fread($fh, filesize($nom_fich));
          
          // Lo almacenamos en el atributo $cod_html
          $this->cod_html = $contenido;
      }

      // Registramos los campos a parsear junto con sus valores en el atributo
      // $campos
      function wee_registrarCampo($nombre, $valor)
      {
          $this->campos[$nombre] = $valor;
      }

      // También damos la opción al usuario a parsear varios campos de una sóla
      // tacada. ..->wee_registrarCampos("campo1,campo2,..", $valor1, $valor2, ..)
      // ¡Ojo! El nombre de los campos va separado por comas pero sin espacios
      // El espacio en blanco se considera un carácter como otro cualquiera.
      function wee_registrarCampos()
      {
          // Sacamos el número de argumentos
          $numargs = func_num_args();

          // El número de argumentos debe ser, como mínimo de dos:
          if ($numargs < 2) die("Error: No se han pasado valores a los campos");

          $args_v = func_get_args();

          // Al menos tenemos un argumento que es el que contendrá el nombre
          // de todos los campos a parsear, separados por comas
          $nombre_campos = explode(",", $args_v[0]);

          // Miramos ahora que haya tantos campos como valores
          if (($numargs-1) != count($nombre_campos)) die ("Error: El número de campos y valores no coincide");
          
          // Todo va bien, metemos cada campo y su valor en el atributo $campos
          for ($i=1; $i<$numargs; $i++)
          {
              $this->campos[$nombre_campos[$i-1]] = $args_v[$i];
          }
      }

      // Parsea el código html sustituyendo los campos por sus valores
      function wee_parsearCodigoHtml()
      {
          foreach($this->campos as $nombre => $valor)
          {
              // Creamos el campo a buscar con sus caracteres de escape: ini_car y fin_car
              $campo_con_car_esc = $this->ini_car.$nombre.$this->fin_car;
              
              // Reemplazamos
              $this->cod_html = str_replace($campo_con_car_esc, $valor, $this->cod_html);
          }
      }

      // Mostramos el código Html
      function wee_mostrarCodigoHtml()
      {
          echo $this->cod_html;
      }
      
      // Por si nos hiciera falta guardar el código del archivo parseado
      function wee_devolverCodigoHtml()
      {
          return $this->cod_html;
      }

	

}


?>