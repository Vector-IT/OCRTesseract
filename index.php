<?php

header('Content-Type: application/json');
header('Application: Vector PDF Reader');

// Verifica si hay archivos en la petición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
	$file = $_FILES['file'];

	$language = $_POST['language'] ?? 'eng';
	$preserve_spaces = $_POST['preserve_spaces'] ?? 0;
	
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
					$horaInicio = microtime(true);

					if ($extension == 'pdf') {
						$pagenumber = 0;

						$myurl = $uploadFile.'['.$pagenumber.']';

						$image = new \Imagick();

						$image->setResolution(400, 400);

						$image->readImage($myurl);

						// Flatten all the images - prevent black background
						$image = $image->flattenImages();

						$image->setImageFormat('jpeg');
						// $image->setImageFormat( "png" );

						$image->setImageCompression(\Imagick::COMPRESSION_JPEG);
						$image->setImageCompressionQuality(100);

						$archivo = $uploadDir.str_ireplace($extension, 'jpg', basename($file['name']));
						$image->writeImage($archivo);

						$image->clear();
						$image->destroy();
					}
					else {
						$archivo = $uploadFile;
					}

					$img = imagecreatefromjpeg($archivo);

					$datos = [];

					// READ ENTIRE FILE
					if (PHP_OS == 'WINNT') {
						// exec('"C:\Program Files\Tesseract-OCR\tesseract.exe" "'.$archivo.'" stdout --psm 6 -c preserve_interword_spaces=1 -l '.$language, $output);
						exec('"C:\Program Files\Tesseract-OCR\tesseract.exe" "'.$archivo.'" stdout --psm 6 -c preserve_interword_spaces='.$preserve_spaces.' -l '.$language, $output);
					}
					else {
						exec('tesseract "'.$archivo.'" stdout', $output);
					}

					$horaFin = microtime(true);

					$interval = round($horaFin - $horaInicio, 3);

					$response = [
						'status' => 'success',
						'time' => $interval. 'secs',
						'data' => $output
					];

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
					'message' => 'Failed to move uploaded file'
				];
			}
		} else {
			$response = [
				'status' => 'error',
				'message' => 'Extension not allowed'
			];
		}

	} else {
		$response = [
			'status' => 'error',
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
