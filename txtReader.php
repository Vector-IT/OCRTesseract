<!DOCTYPE html>
<html lang="por">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>txtReader</title>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        Choose Your File
        <input type="file" name="file" />
        <br>
        <input type="submit" value="Read PDF" name="readpdf" />
    </form>

<?php
ini_set('log_errors', 'on');
ini_set('error_log', 'php_errors.log');

require('class.pdf2text.php');
extract($_POST);

if(isset($readpdf)){
    
    if($_FILES['file']['type']=="application/pdf") {
		$horaInicio = microtime(true);

        $a = new PDF2Text();
        $a->setFilename($_FILES['file']['tmp_name']); 
        $a->decodePDF();
        $text = $a->output(); 
		echo nl2br($text);

		$horaFin = microtime(true);
		$timeReading = round($horaFin - $horaInicio, 3);
		error_log('['.date('Y-m-d H:i:s').'] TXTReader / IP: '.$_SERVER['REMOTE_ADDR'].' | Response: '.$timeReading.PHP_EOL, 3, 'logs.txt');
    }
     
    else {
        echo "<p style='color:red; text-align:center'>
            Wrong file format</p>";
    }
}    
?>
</body>

</html>
