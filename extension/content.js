// Crear una ventana emergente flotante
const overlay = document.createElement("div");
overlay.id = "dragOverlay";
overlay.style.position = "fixed";
overlay.style.top = "0";
overlay.style.right = "0";
overlay.style.width = "50%";
overlay.style.height = "50%";
overlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
overlay.style.display = "flex";
overlay.style.flexDirection = "column"; // Cambia la dirección del flex a columna
overlay.style.alignItems = "center";
overlay.style.justifyContent = "center";
overlay.style.zIndex = "9999";
overlay.style.color = "#fff";
overlay.style.fontSize = "20px";
overlay.style.display = "none"; // Inicia como oculto

// Añadir la imagen y el texto en divs separados
const imgDiv = document.createElement("div");
const textDiv = document.createElement("div");

imgDiv.innerHTML = "<img src='https://desarrollo.vector-it.com.ar/VectorPDF/extension/icon128.png'>";
textDiv.textContent = "Suelta el archivo para procesarlo con la extensión";

overlay.appendChild(imgDiv);
overlay.appendChild(textDiv);

// Prevenir el comportamiento predeterminado del drag and drop
overlay.addEventListener("dragover", (event) => {
	event.preventDefault(); // Evita que el navegador abra el archivo
});

// Manejar el evento de soltar el archivo
overlay.addEventListener("drop", (event) => {
	event.preventDefault(); // Prevenir el comportamiento predeterminado

	const archivo = event.dataTransfer.files[0]; // Obtener el primer archivo soltado
	if (archivo) {
		enviarArchivo(archivo); // Llama a la función con el archivo
	}
});

// Agregar la ventana al cuerpo del documento
document.body.appendChild(overlay);

// Mostrar la ventana emergente cuando el usuario arrastra un archivo
window.addEventListener("dragenter", (event) => {
	if (event.dataTransfer.types.includes("Files")) {
		chrome.storage.local.get(["DropZone_Activated"], (result) => {
			if (result.DropZone_Activated) {
				overlay.style.display = "flex";
			}
		});
	}
});

// Ocultar la ventana emergente cuando el usuario cancela el arrastre
window.addEventListener("dragleave", (event) => {
	if (event.clientX === 0 && event.clientY === 0) {
		overlay.style.display = "none";
	}
});

// También ocultar la ventana emergente al soltar el archivo
window.addEventListener("drop", (event) => {
	overlay.style.display = "none";
});

function enviarArchivo(archivo) {
	const url = "https://vbot.vector-it.com.ar/";
	const formData = new FormData();
	formData.append("file", archivo);
	formData.append("extension", true);

	fetch(url + 'damsp.php', {
		method: "POST",
		body: formData,
		headers: {
			"Authorization": "Bearer TU_TOKEN"  // Agrega tu token de autenticación si es necesario
		}
	})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				// downloadPDF(url + data.pdf_file, data.period + '/' + data.type + '/' + data.pdf_file.substring(data.pdf_file.lastIndexOf('/') + 1));
				window.open(url + data.pdf_file, '_blank');
			}
			else {
				alert(data.status);
			}

			console.log("Respuesta de la API:", data);
		})
		.catch(error => {
			alert("Error al consultar la API:", error.message);
			console.error("Error al consultar la API:", error);
		});
}

function downloadPDF(pdfURL, filename) {
	// Create an invisible anchor element
	const link = document.createElement('a');

	// Set the href attribute to the PDF URL
	link.href = pdfURL;

	// Set the download attribute with the desired file name
	link.download = filename;

	// Append the link to the body
	document.body.appendChild(link);

	// Programmatically click the link to trigger the download
	link.click();

	// Remove the link from the DOM
	document.body.removeChild(link);
} 