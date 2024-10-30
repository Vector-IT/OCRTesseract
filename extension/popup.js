const dropZone = document.getElementById("drop-zone");
const resultado = document.getElementById("resultado");
const fileInput = document.getElementById("file-input");

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

// Función para enviar el archivo a la API
function enviarArchivo(archivo) {
	const url = "https://desarrollo.vector-it.com.ar/VectorPDF/damsp2.php";
	const formData = new FormData();
	formData.append("file", archivo);

	dropZone.innerHTML = '<img src="./loading4.gif" style="height: 100%;">';
	fetch(url, {
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
				resultado.innerHTML += '<strong>File:</strong> ' + data.file;
				resultado.innerHTML += '<br><strong>Type:</strong> ' + data.type;
				resultado.innerHTML += '<br><strong>CNPJ:</strong> ' + data.cpf_cnpj;
				resultado.innerHTML += '<br><strong>Date:</strong> ' + data.period;
				resultado.innerHTML += '<br><a href="' + data.pdf_file + '" download>Download PDF</a>';

				// downloadPDF(data.pdf_file);
			}
			else {
				resultado.innerHTML = "<h3>Error</h3>";
				resultado.innerHTML += "<strong>" + data.status + "</strong>";
			}
			
			resultado.innerHTML += ' <a href="#" id="restart" style="float: right;">Restart</a>';

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

function downloadPDF(pdfURL) {
	// Create an invisible anchor element
	const link = document.createElement('a');

	// Set the href attribute to the PDF URL
	link.href = pdfURL;

	// Set the download attribute with the desired file name
	link.download = "";
	// link.download = pdfURL.substring(pdfURL.lastIndexOf('/') + 1);

	// Append the link to the body
	document.body.appendChild(link);

	// Programmatically click the link to trigger the download
	link.click();

	// Remove the link from the DOM
	document.body.removeChild(link);
} 

function restart() {
	resultado.innerHTML = '';
	dropZone.innerHTML = 'Suelta el archivo aquí';
	dropZone.classList.remove('hide');
}