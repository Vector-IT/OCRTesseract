const dropZone = document.getElementById("drop-zone");
const resultado = document.getElementById("resultado");
const fileInput = document.getElementById("file-input");
const chkDropZone = document.getElementById("chkDropZone");

// document.addEventListener("DOMContentLoaded", () => {
// 	// Código que deseas ejecutar al cargar el popup
// 	chrome.storage.sync.get('carpetaDescargas').then(function (result) { 
// 		document.getElementById("folder-input").value = result.carpetaDescargas; 
// 	});
// });

// Estilo al arrastrar
dropZone.addEventListener("dragover", (e) => {
	e.preventDefault();
	dropZone.classList.add("dragover");
});

dropZone.addEventListener("dragleave", () => {
	dropZone.classList.remove("dragover");
});

dropZone.addEventListener("click", () => {
	fileInput.click();
});

// Maneja el archivo al soltarlo
dropZone.addEventListener("drop", (e) => {
	e.preventDefault();
	dropZone.classList.remove("dragover");

	const archivo = e.dataTransfer.files[0];
	if (archivo) {
		enviarArchivo(archivo);
	}
});

fileInput.addEventListener("change", () => {
	const archivo = fileInput.files[0];
	if (archivo) {
		enviarArchivo(archivo);
	}
});

chkDropZone.addEventListener("change", () => {
	chrome.storage.local.set({ DropZone_Activated: chkDropZone.checked });
});

// On document ready
document.addEventListener("DOMContentLoaded", () => {
	// chrome storage read
	chrome.storage.local.get(["DropZone_Activated"], (result) => {
		if (result.DropZone_Activated) {
			chkDropZone.checked = true;
		}
		else {
			chkDropZone.checked = false;
		}
	});
});

// Función para enviar el archivo a la API
function enviarArchivo(archivo) {
	const url = "https://desarrollo.vector-it.com.ar/VectorPDF/";
	const formData = new FormData();
	formData.append("file", archivo);

	dropZone.innerHTML = '<img src="./loading4.gif" style="height: 100%;">';
	fetch(url + 'damsp2.php', {
		method: "POST",
		body: formData,
		headers: {
			"Authorization": "Bearer TU_TOKEN"  // Agrega tu token de autenticación si es necesario
		}
	})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				resultado.innerHTML =  '<h3>Success</h3>';
				resultado.innerHTML += '<div><strong>File:</strong> ' + data.file + '</div>';
				resultado.innerHTML += '<div><strong>Type:</strong> ' + data.type + '</div>';
				resultado.innerHTML += '<div><strong>CNPJ:</strong> ' + data.cpf_cnpj + '</div>';
				resultado.innerHTML += '<div><strong>Date:</strong> ' + data.period + '</div>';
				resultado.innerHTML += '<div><a id="linkDownload" href="' + url + data.pdf_file + '" download="' + data.period + '/' + data.type + '/' + data.pdf_file.substring(data.pdf_file.lastIndexOf('/') + 1) + '">Download PDF</a><a href="#" id="restart" style="float: right;">Restart</a></div>';

				// document.getElementById('linkDownload').addEventListener('click', () => {
				// 	downloadPDF(this.dataset.url, this.dataset.filename);
				// });

				// get all string after /
				const anio = data.period.substring(data.period.lastIndexOf('/') + 1);
				const mes = data.period.substring(0, data.period.lastIndexOf('/'));
				 
				downloadPDF(url + data.pdf_file, anio + '/' + mes + '/' + data.type + '/' + data.pdf_file.substring(data.pdf_file.lastIndexOf('/') + 1));
			}
			else {
				resultado.innerHTML = "<h3>Error</h3>";
				resultado.innerHTML += "<div><strong>" + data.status + "</strong></div>";
				resultado.innerHTML += '<div><a href="#" id="restart" style="float: right;">Restart</a></div>';

			}
			
			document.getElementById('restart').addEventListener('click', () => {
				restart();
			});
			console.log("Respuesta de la API:", data);

			dropZone.classList.add('hide');
		})
		.catch(error => {
			resultado.innerText = "Error al enviar el archivo";
			console.error("Error al consultar la API:", error);

			dropZone.innerHTML = 'Suelta el archivo aquí';
		});
}

function downloadPDF(pdfURL, filename) {
	chrome.downloads.download({
		url: pdfURL,
		filename: filename,
		conflictAction: "overwrite"
	}, function (downloadId) {
		if (chrome.runtime.lastError) {
			console.error("Error al descargar:", chrome.runtime.lastError.message);
		} else {
			console.log("Descarga iniciada con ID:", downloadId);
		}
	});
} 

function restart() {
	resultado.innerHTML = '';
	dropZone.innerHTML = 'Suelta el archivo aquí';
	dropZone.classList.remove('hide');
}