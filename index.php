<?php
	
	//user es usuario de nextcloud user='digite usuario'
	$user = '';
	//password es el que se utiliza para nextcloud  $password='digite password'
	$password = '';
	
	// al final de la url va el usuario con el que se  accede en nextcloud
	//$url_raiz = "http://nextcloud.itsmovilidadcali.com.co/remote.php/dav/files/nombre de usuario  aqui/";


	$url_raiz = "http://nextcloud.itsmovilidadcali.com.co/remote.php/dav/files/ /";


	function crear_directorio($recurso) {
		global $url_raiz, $user, $password;
		
		//echo $url_raiz."<br>";

		$rslt = 0;
		$array = explode('/', ltrim($recurso, '/'));

		$tm_path = $url_raiz;
		//echo $tm_path."<br>";
		//echo "Recurso: ".$recurso."<br>";
		foreach($array as $item) {
			$tm_path = $tm_path.$item.'/';
			//echo $tm_path."<br>";
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $tm_path);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');

			curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo json_encode( "Error:" . curl_error($ch));
			}
			else {
				++$rslt;
			}

			curl_close($ch);
		}

		return $rslt;
	}

	function eliminar_recurso($recurso) {
		global $url_raiz, $user, $password;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_raiz . $recurso);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo json_encode( "Error:" . curl_error($ch));
			curl_close($ch);
			return false;
		}
		else {
			curl_close($ch);
			return true;
		}
	}

	function mover_recurso($recurso, $nueva_ruta) {
		global $url_raiz, $user, $password;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_raiz . $recurso);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Destination: ' . $url_raiz . $nueva_ruta));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MOVE');

		curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			header("HTTP/1.1 504 Error");
			echo json_encode( "Error:" . curl_error($ch));
			curl_close($ch);
			return false;
		}
		else {
			curl_close($ch);
			return true;
		}
	}


	/* ENDPOINT PARA CREAR DIRECTORIOS */
	if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		header("HTTP/1.1 200 OK");
		#echo json_encode($_POST);
		
		# REVISAR QUE EL USUARIO HAYA ENVIADO EL PARAMETRO DE LA ACCION A EJECUTAR
		# MKDIR, UPLOAD, DELDIR, DELFILE, RENDIR, RENFILE
		# *************** TENER EN CUENTA EL CONTROL DE VERSIONES CUANDO SE MODIFIQUE UN ARCHIVO ***************
		#                 AL CARGAR EL ARCHIVO DEBE EXISTIR UN PARAMETRO CON EL PATH DEL ARCHIVO
		if ( isset($_POST['action']) ) {
			$action = $_POST['action'];

			if ( isset($_POST['recurso']) ) {
				$recurso = $_POST['recurso'];

				switch ($action) {
					case "MKDIR":
						if (crear_directorio($recurso)) {
							echo json_encode("Directorio ".$recurso." creado con Exito");
						}
						else {
							echo json_encode("No se pudo crear el Directorio ".$recurso);
						}

						break;
					case "UPLOAD":
						$target_dir = "tmp/";
						$target_file = $target_dir . basename($_FILES["pmt_file"]["name"]);

						//GUARDAR EL ARCHIVO TEMPORALMENTE EN EL SERVIDOR
						if (move_uploaded_file($_FILES["pmt_file"]["tmp_name"], $target_file)) {
							//RUTA DE DESTINO DEL ARCHIVO
							$datos_ruta = pathinfo($_POST['recurso']);
							$recurso = $datos_ruta['dirname']; //dirname($_POST['recurso']);
							$fileName = $datos_ruta['basename']; //basename($_POST['recurso']);

							//CREAR LA DURA DE DESTINO
							crear_directorio($recurso);
							echo "Final:" . $url_raiz . $recurso . "/" . $fileName ."<br>";

							//SUBIR EL ARCHIVO
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url_raiz . $recurso . "/" . $fileName);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

							curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);

							$result = curl_exec($ch);
							if (curl_errno($ch)) {
								echo json_encode( "Error:" . curl_error($ch));
							}
							else {
								echo json_encode("Archivo ".$recurso." subido con Exito");
							}

							curl_close($ch);
							
							//ELIMINAR EL ARCHIVO TEMPORAL DEL EL SERVIDOR
							if (!unlink($target_file)) { 
								//
							}
						}

						break;
					case "DELDIR":
						if (eliminar_recurso($recurso)) {
							echo json_encode("Se ha eliminado el recurso ".$recurso);
						}

						break;
					case "DELFILE":
						if (eliminar_recurso($recurso)) {
							echo json_encode("Se ha eliminado el recurso ".$recurso);
						}

						break;
					case "RENDIR":
						$nueva_ruta = $_POST['nueva_ruta'];
						crear_directorio($nueva_ruta);

						if (mover_recurso($recurso, $nueva_ruta)) {
							echo json_encode("Se ha movido el recurso " . $recurso . " a " . $nueva_ruta);
						}

						break;
					case "RENFILE":
						$nueva_ruta = $_POST['nueva_ruta'];
						$datos_ruta = pathinfo($nueva_ruta);
						
						crear_directorio($datos_ruta['dirname']);

						if (mover_recurso($recurso, $nueva_ruta)) {
							echo json_encode("Se ha movido el recurso " . $recurso . " a " . $nueva_ruta);
						}

						break;
					default:
						header("HTTP/1.1 400 Bad Request");
						echo json_encode("03. Error en los parametros de la peticion");
				}
			}
			else {
				header("HTTP/1.1 400 Bad Request");
				echo json_encode("01. Error en los parametros de la peticion");
			}
		}
		else{
			header("HTTP/1.1 400 Bad Request");
			echo json_encode("00. Error en los parametros de la peticion");
		}

		exit();
	}
	//SI LA PETICION NO ES POST, SE GENERA UN ERROR EN LA RESPUESTA
	header("HTTP/1.1 400 Bad Request");
?>