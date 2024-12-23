<?php
header('Content-Type: application/json');
header('Application: Vector PDF Reader');

// TODO: en donde esta el * poner el id de la extension
// header("Access-Control-Allow-Origin: chrome-extension://jedphgnngcokeenmpcbbfhjijgmpojdh");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

define('default_lang', 'por');
define('default_preserve_spaces', 0);
define('default_page_segmentation', 3);
define('default_resolution', 155);

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

						$archivo = $uploadDir.str_ireplace($extension, 'jpg', basename($file['name']));

						// $myurl = $uploadFile.'['.$pagenumber.']';

						// $image = new Imagick();
						
						// $image->setResolution($resolution, $resolution);
						// $image->trim();
						// $image->sharpenImage(0, 1.0);

						// $image->readImage($myurl);

						// // Flatten all the images - prevent black background
						// $image = $image->flattenImages();

						// $image->setImageFormat('jpeg');
						// // $image->setImageFormat( "png" );

						// $image->setImageCompression(Imagick::COMPRESSION_JPEG);
						// $image->setImageCompressionQuality(50);

						// $image->writeImage($archivo);

						// $image->clear();
						// $image->destroy();

						if (PHP_OS == 'WINNT') {
							// exec('"C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\convert.exe" -density '.$resolution.' -trim "'.$uploadFile.'" -quality 100 -flatten -sharpen 0x1.0 "'. $archivo.'"');
							exec('magick -density '.$resolution.' "'.$uploadFile.'" -trim -quality 100 -flatten -sharpen 0x1.0 "'. $archivo.'"');
						}
						else {
							exec('convert -density '.$resolution.' "'.$uploadFile.'" -trim -quality 100 -flatten -sharpen 0x1.0 "'. $archivo.'"');
						}

						$horaFin = microtime(true);
						$timeConversion = round($horaFin - $horaInicio, 3);
						
					}
					else {
						$archivo = $uploadFile;
					}

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
					// $response['output'] = $output;
					
					$log = $response;
					
					$log['timeConversion'] = $timeConversion.'secs';
					$log['timeReading'] = $timeReading.'secs';

					$finalOutput = [];
					foreach ($output as $line) {
						if (trim($line) !== '') {
							$finalOutput[] = trim($line);
						}
					}
					$output = $finalOutput;
					
					$horaInicio = microtime(true);

					// READ DATA
					// Array de arrays con el numero de linea y la posicion de inicio
					define('iDAMSP', [3, 4, 2, 1, 5]);
					define('iCNPJ' , [5, 6, 7, 16, 8, 9, 14]);
					define('iPeriod', [5, 10, 12, 6, 7, 8, 14, 16, 11, 9]);
					define('iPeriodSep', ['-', '/', 'I', '[', '#', 'f']);
					
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
						$response['type'] = 'damsp';
						$response['status'] = 'error';

						#region Get CPF/CNPJ
						$cnpj = '';
						$iSpace = 0;
						$isValidCNPJ = false;
						for ($i = 0; $i < count(iCNPJ); $i++) {
							do {
								$iSpace = strpos($output[iCNPJ[$i]], ' ', $iSpace + 1);
								if ($iSpace !== false) {
									$cnpj = substr($output[iCNPJ[$i]], 0, $iSpace);
								}
								else {
									$cnpj = substr($output[iCNPJ[$i]], 0);
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
							$response['valid_cpf_cnpj'] = $isValidCNPJ;
							$response['cpf_cnpj'] = $cnpj;

							if ($isValidCNPJ) {
								break;
							}
						}
						#endregion

						#region Get Period
						$blnValidDate = false;

						for ($i = 0; $i < count(iPeriod); $i++) {
							$period = '';
							$iBarra = 0;
							
							do {
								$iBarraOld = $iBarra;

								// Buscar separador
								foreach (iPeriodSep as $separador) {
									$iBarra = strpos($output[iPeriod[$i]], $separador, $iBarraOld + 1);
									
									if ($iBarra !== false) {
										break;
									}
								}

								$iSpaceBegin = strrpos(substr($output[iPeriod[$i]], 0, $iBarra - 1), ' ');

								if ($iSpaceBegin === false) {
									$iSpaceBegin = 0;
								}
								else {
									$iSpaceBegin;
								}

								$iSpaceEnd = $iSpaceBegin;

								do {
									$iSpaceEnd = strpos($output[iPeriod[$i]], ' ', $iSpaceEnd + 1);
									
									if ($iSpaceEnd !== false) {
										$period = substr($output[iPeriod[$i]], $iSpaceBegin, $iSpaceEnd - $iSpaceBegin);
									}
									else {
										$period = substr($output[iPeriod[$i]], $iSpaceBegin);
									}
									$period = str_replace(' ', '', $period);

									if (validateDate($period)) {
										$blnValidDate = true;
									}

								} while ($iSpaceEnd !== false);
								
							} while (!$blnValidDate && $iBarra !== false);
							

							if ($iSpaceEnd !== false || $period != '') {
								$response['validDate'] = $blnValidDate;
								$response['period'] = $period;
							}
							else {
								$response['validDate'] = false;
								$response['period'] = $period;	
							}

							if ($response['validDate']) {
								break;
							}
						}
						#endregion

						// Check if is valid
						if (!$isValidCNPJ) {
							$response['status'] = 'error - invalid CNPJ';
						}
						elseif (!$response['validDate']) {
							$response['status'] = 'error - invalid period';
						}
						else {
							$response['status'] = 'success';

							// Si la llamada viene de la extension
							if (isset($_POST['extension'])) {
								// Creo el directorio si no existe
								if (!file_exists('processed') && !is_dir('processed')) {
									mkdir('processed');
								}
								$archivoPDF = 'processed/'.basename($uploadFile);
							
								require_once 'fpdf/fpdf.php';

								$pdf = new FPDF();
								$pdf->AddPage();

								$pdf->SetFont('Arial', '', 1);
								$pdf->SetTextColor(255, 255, 255);

								$pdf->SetXY(0, 1);
								$pdf->MultiCell(0, 1, "damsp".PHP_EOL.$response['cpf_cnpj'].PHP_EOL.$response['period']);
								// $pdf->Write(0, 'damsp'.PHP_EOL);
								// $pdf->SetXY(0, 2);
								// $pdf->Write(0, trim($response['cpf_cnpj']).PHP_EOL);
								// $pdf->SetXY(0, 3);
								// $pdf->Write(0, trim($response['period']).PHP_EOL);

								$pdf->Image($archivo, 5, 5, 200, 0, 'JPG');
								$pdf->Output('F', $archivoPDF);
								$response['pdf_file'] = $archivoPDF;
							}
						}
					}
					else {
						$response['type'] = 'unknown';
						$response['status'] = 'error - model not found';
					}

					if (isset($_POST['debug']) && $_POST['debug'] == '1') {
						$response['output'] = $output;
					}

					$horaFin = microtime(true);
					$timeModeling = round($horaFin - $horaInicio, 3);

					$log['timeModeling'] = $timeModeling.'secs';
					$log['totalTime'] = ($timeConversion + $timeReading + $timeModeling).'secs';
					error_log('['.date('Y-m-d H:i:s').'] IP: '.$_SERVER['REMOTE_ADDR'].' | Params: '.json_encode($_POST).' | Response: '.json_encode($log).PHP_EOL, 3, 'logs.txt');

					if (isset($_POST['debug']) && $_POST['debug'] == '1') {
						$response['timeConversion'] = $timeConversion.'secs';
						$response['timeReading'] = $timeReading.'secs';
						$response['timeModeling'] = $timeModeling.'secs';
						$response['totalTime'] = ($timeConversion + $timeReading + $timeModeling).'secs';
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

function validateDate(&$dateString, $formats = ['M/Y', 'M/y']): bool {
	$meses = [
		["01", "JAN", 1 , "JANEIRO"  , "JANUARY"  , "JAN"], 
		["02", "FEV", 2 , "FEVEREIRO", "FEBRUARY" , "FEB" , "FEY"], 
		["03", "MAR", 3 , "MARÇO"    , "MARCH"    , "MAR"], 
		["04", "ABR", 4 , "ABRIL"    , "APRIL"    , "APR"], 
		["05", "MAI", 5 , "MAIO"     , "MAY"      , "MAY"], 
		["06", "JUN", 6 , "JUNHO"    , "JUNE"     , "JUN"], 
		["07", "JUL", 7 , "JULHO"    , "JULY"     , "JUL"], 
		["08", "AGO", 8 , "AGOSTO"   , "AUGUST"   , "AUG"], 
		["09", "SET", 9 , "SETEMBRO" , "SEPTEMBER", "SEP"], 
		["10", "OUT", 10, "OUTUBRO"  , "OCTOBER"  , "OCT"], 
		["11", "NOV", 11, "NOVEMBRO" , "NOVEMBER" , "NOY"], 
		["12", "DEZ", 12, "DEZEMBRO" , "DECEMBER" , "DEC"]
	];
	
	$valMes = false;
	$valAno = false;
	
	// Buscar separador
	foreach (iPeriodSep as $separador) {
		$iBarra = strpos($dateString, $separador);
		if ($iBarra !== false) {
			$dateString = str_replace($separador, iPeriodSep[0], $dateString);
			break;
		}
	}

	// Controlar mes
	if ($iBarra !== false) {
		$mes = strtoupper(substr($dateString, 0, $iBarra));

		foreach($meses as $arrayMes) {
			if(in_array($mes, $arrayMes)) {
				$valMes = true;
				break;
			}
		}

		// Controlar año
		$anio = substr($dateString, strpos($dateString, iPeriodSep[0]) + 1);

		if (is_numeric($anio)) {
			if (strlen($anio) == 2) {
				if ($anio > 0 && $anio < 100) {
					$valAno = true;
				}
			}
			elseif (strlen($anio) == 4) {
				if ($anio > 1900 && $anio < 2100) {
					$valAno = true;
				}
			}
			else {
				$valAno = false;
			}
		}

		if ($valMes && $valAno) {
			// Corrigir mes
			foreach($meses as $arrayMes) {
				if(in_array($mes, $arrayMes)) {
					$mes = $arrayMes[0];
					break;
				}
			}
			$dateString = $anio . iPeriodSep[0] . $mes;
		}

		return $valMes && $valAno;
	}

	return false;
}

function validateDate1($dateString, $formats = ['M/Y', 'M/y'], $locale = 'pt_BR'): bool {
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