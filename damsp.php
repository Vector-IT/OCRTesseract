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

						$image = new Imagick();
						
						$image->setResolution($resolution, $resolution);
						$image->readImage($myurl);

						// Flatten all the images - prevent black background
						$image = $image->flattenImages();

						$image->setImageFormat('jpeg');
						// $image->setImageFormat( "png" );

						$image->setImageCompression(Imagick::COMPRESSION_JPEG);
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

					$finalOutput = [];
					foreach ($output as $line) {
						if (trim($line) !== '') {
							$finalOutput[] = trim($line);
						}
					}
					$output = $finalOutput;
					
					// READ DATA
					define('iDAMSP', [1, 2, 3]);
					define('iCNPJ' , [6, 7]);
					
					// Check if the file is a DAMSP
					$blnDAMSP = false;
					for ($i = 0; $i < count(iDAMSP); $i++) {
						if (
							count($output) > iDAMSP[$i] &&
							(
							$output[iDAMSP[$i]] == 'DAMSP - Documento de Arrecadação do Município de São Paulo' ||
							(strcasecmp(substr($output[iDAMSP[$i]], 0, 5), 'DAMSP') == 0 && strcasecmp(substr($output[iDAMSP[$i]], -5), 'Paulo') == 0) ||
							(strpos($output[iDAMSP[$i]], 'DAMSP - Documento de Arrecadação do Município de São Paulo') !== false) ||
							(strpos($output[iDAMSP[$i]], 'DAMSP') !== false && strpos($output[iDAMSP[$i]], 'Paulo') !== false)
							)
						) {	
							$blnDAMSP = true;
							break;
						}
					}

					if ($blnDAMSP) {
						// IS A DAMSP OF SP
						$response['status'] = 'success';

						// Get CPF/CNPJ
						$cnpj = '';
						$iSpace = 0;
						$isValidCNPJ = false;
						for ($i = 0; $i < count(iCNPJ); $i++) {
							do {
								$iSpace = strpos($output[iCNPJ[$i]], ' ', $iSpace + 1);
								if ($iSpace !== false) {
									$cnpj = substr($output[iCNPJ[$i]], 0, $iSpace);
								}
								
								$isValidCNPJ = validateCNPJ($cnpj) || validateCPF($cnpj);

							} while (!$isValidCNPJ && $iSpace !== false);

							$cnpj = preg_replace('/[^0-9]/', '', $cnpj);
							if (validateCNPJ($cnpj)) {
								$cnpj = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
							}
							elseif (validateCPF($cnpj)) {
								$cnpj = substr($cnpj, 0, 3) . '.' . substr($cnpj, 3, 3) . '.' . substr($cnpj, 6, 3) . '-' . substr($cnpj, 9, 2);
							}

							// Get Period
							$iSpaceBegin = strpos($output[iCNPJ[$i]], ' ', $iSpace + 2) + 1;
							$iSpaceEnd = strpos($output[iCNPJ[$i]], ' ', $iSpaceBegin);
							$iSpaceEnd = strpos($output[iCNPJ[$i]], ' ', $iSpaceEnd + 1);
							$iSpaceEnd = strpos($output[iCNPJ[$i]], ' ', $iSpaceEnd + 1);
							
							$period = substr($output[iCNPJ[$i]], $iSpaceBegin, $iSpaceEnd - $iSpaceBegin);
							if (substr($period, 0, 3) == 'FEY') {
								$period = 'FEV'.substr($period, 3);
							}
							$period = str_replace(' ', '', $period);
							$response['validDate'] = validateDate($period, ['M/Y', 'M/y']);
							
							$response['cpf/cnpj'] = $cnpj;
							$response['period'] = $period;

							if ($isValidCNPJ && $response['validDate']) {
								break;
							}
						}

					}
					else {
						$response['status'] = 'error - model not found';
					}
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

function validateCNPJ($cnpj): bool {
	// Elimina cualquier carácter no numérico
	$cnpj = preg_replace('/[^0-9]/', '', $cnpj);

	// Verifica si el CNPJ tiene 14 dígitos
	if (strlen($cnpj) != 14) {
		return false;
	}

	// Primer paso: multiplicación por los factores 5,4,3,2,9,8,7,6,5,4,3,2
	$pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
	$suma1 = 0;
	for ($i = 0; $i < 12; $i++) {
		$suma1 += $cnpj[$i] * $pesos1[$i];
	}
	$resto1 = $suma1 % 11;
	$digito1 = $resto1 < 2 ? 0 : 11 - $resto1;

	// Verifica el primer dígito
	if ($cnpj[12] != $digito1) {
		return false;
	}

	// Segundo paso: multiplicación por los factores 6,5,4,3,2,9,8,7,6,5,4,3,2
	$pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
	$suma2 = 0;
	for ($i = 0; $i < 13; $i++) {
		$suma2 += $cnpj[$i] * $pesos2[$i];
	}
	$resto2 = $suma2 % 11;
	$digito2 = $resto2 < 2 ? 0 : 11 - $resto2;

	// Verifica el segundo dígito
	return $cnpj[13] == $digito2;
}

function validateCPF(string $cpf): bool {
	// Elimina cualquier carácter no numérico
	$cpf = preg_replace('/[^0-9]/', '', $cpf);

	if (strlen($cpf) != 11) {
		return false;
	}

	$elementos = (array)str_split($cpf);
	$elementos[10] = 0; // Reduz uma comparação no calculo de $somaB
	$somaA = 0;
	$somaB = 0;
	foreach ($elementos as $indice => $elemento) {
		$multiplicador = count($elementos) - $indice;
		$somaA += (int)$elemento * (int)($multiplicador > 2 ? $multiplicador - 1 : 0);
		$somaB += (int)$elemento * (int)$multiplicador;
	}

	$moduloA = (($somaA * 10) % 11) % 10;
	$moduloB = (($somaB * 10) % 11) % 10;

	return preg_replace('#\d{9}(\d{2})$#', '$1', $cpf) == $moduloA . $moduloB;
}

function validateDate($dateString, $formats, $locale = 'pt_BR'): bool {
	$generator = new IntlDatePatternGenerator($locale);
	$formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

	foreach ($formats as $format) {
		$pattern = $generator->getBestPattern($format);
		$formatter->setPattern($pattern);

		if ($formatter->parse($dateString)) {
            return true;
        }
	}
	return false;
}