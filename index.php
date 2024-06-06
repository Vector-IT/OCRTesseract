<?php
header('Content-Type: application/json');
header('Application: Vector PDF Reader');

define('default_lang', 'por');
define('default_preserve_spaces', 0);
define('default_page_segmentation', 3);
define('default_resolution', 500);

// Verifica si es un POST y si hay archivos en la petición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
	$file = $_FILES['file'];

	$language = $_POST['language'] ?? default_lang;
	$preserve_spaces = $_POST['preserve_spaces'] ?? default_preserve_spaces;
	$page_segmentation = $_POST['page_segmentation'] ?? default_page_segmentation;
	$resolution = $_POST['resolution'] ?? default_resolution;
	
	$allowedExts = array("pdf");

	// Verifica si hubo algún error en la subida del archivo
	if ($file['error'] === UPLOAD_ERR_OK) {
		$uploadDir = 'uploads/';

		// Creo el directorio si no existe
		if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
			$arRuta = explode('/', $uploadDir);

			$uploadDirParcial = '';
			for ($I = 0; $I < count($arRuta); $I++) {
				if ($I > 0) {
					$uploadDirParcial.= '/';
				}

				$uploadDirParcial.= $arRuta[$I];
				if (!file_exists($uploadDirParcial) && !is_dir($uploadDirParcial)) {
					mkdir($uploadDirParcial);
				}
			}
		}
		
		$temp = explode(".", basename($file['name']));
		$extension = strtolower(end($temp));
		
		if (in_array(strtolower($extension), $allowedExts)) {
			$uploadFile = $uploadDir . basename($file['name']);
			// Mueve el archivo subido a la ubicación final
			if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
				try {
					if ($extension == 'pdf') {
						$horaInicio = microtime(true);
						$pagenumber = 0;

						$myurl = $uploadFile.'['.$pagenumber.']';

						$image = new \Imagick();
						
						$image->setResolution($resolution, $resolution);
						$image->readImage($myurl);

						// Flatten all the images - prevent black background
						$image = $image->flattenImages();

						$image->setImageFormat('jpeg');
						// $image->setImageFormat( "png" );

						$image->setImageCompression(\Imagick::COMPRESSION_JPEG);
						$image->setImageCompressionQuality(50);

						$archivo = $uploadDir.str_ireplace($extension, 'jpg', basename($file['name']));
						$image->writeImage($archivo);

						$image->clear();
						$image->destroy();

						$horaFin = microtime(true);
						$timeConversion = round($horaFin - $horaInicio, 3);
					}
					else {
						$archivo = $uploadFile;
					}

					$img = imagecreatefromjpeg($archivo);

					$datos = [];

					// READ ENTIRE FILE
					$horaInicio = microtime(true);
					if (PHP_OS == 'WINNT') {
						exec('"C:\Program Files\Tesseract-OCR\tesseract.exe" "'.$archivo.'" stdout --psm '.$page_segmentation.' -c preserve_interword_spaces='.$preserve_spaces.' -l '.$language, $output);
					}
					else {
						exec('tesseract "'.$archivo.'" stdout', $output);
					}

					$horaFin = microtime(true);
					$timeReading = round($horaFin - $horaInicio, 3);

					$response = [];
					$response['status'] = 'success';
					$response['file'] = basename($file['name']);
					
					$log = $response;
					
					if (isset($_POST['debug']) && $_POST['debug'] == '1') {
						$response['timeConversion'] = $timeConversion.'secs';
						$response['timeReading'] = $timeReading.'secs';
						$response['totalTime'] = ($timeConversion + $timeReading).'secs';
					}
					else {
						$log['timeConversion'] = $timeConversion.'secs';
						$log['timeReading'] = $timeReading.'secs';
						$log['totalTime'] = ($timeConversion + $timeReading).'secs';
					}

					error_log('['.date('Y-m-d H:i:s').'] IP: '.$_SERVER['REMOTE_ADDR'].' | Params: '.json_encode($_POST).' | Response: '.json_encode($log).PHP_EOL, 3, 'logs.txt');

					$response['data'] = $output;
				}
				finally {
					if (file_exists($uploadFile)) {
						unlink($uploadFile);
					}
					
					if (isset($archivo) && file_exists($archivo)) {
						unlink($archivo);
					}
				}
			} else {
				$response = [
					'status' => 'error',
					'file' => basename($file['name']),
					'message' => 'Failed to move uploaded file'
				];
			}
		} else {
			$response = [
				'status' => 'error',
				'file' => basename($file['name']),
				'message' => 'Extension not allowed'
			];
		}

	} else {
		$response = [
			'status' => 'error',
			'file' => basename($file['name']),
			'message' => 'File upload error: ' . $file['error']
		];
	}
} else {
	$response = [
		'status' => 'error',
		'message' => 'No file uploaded'
	];
}

// Devuelve la respuesta en formato JSON
echo json_encode($response);
