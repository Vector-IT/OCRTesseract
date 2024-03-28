const canvas = document.getElementById("canvas");
const ctx = canvas.getContext("2d");
const fileInput = document.getElementById("file-input");
const zoomInBtn = document.getElementById("btn-zoom-in");
const zoomOutBtn = document.getElementById("btn-zoom-out");
const moverBtn = document.getElementById("btn-mover");
const dibujarBtn = document.getElementById("btn-dibujar");
const mensajes = document.getElementById("mensajes");

let zoom = 1;
let zoomOld = 1;
let offsetX = 0;
let offsetY = 0;
let isDrawing = false;
let startPoint = null;
let img;
let rect = null;

fileInput.addEventListener("change", (e) => {
	const file = e.target.files[0];
	const reader = new FileReader();
	reader.onload = () => {
		img = new Image();
		img.onload = () => {
			const pageHeight = window.innerHeight;
			const canvasHeight = pageHeight * 0.8;
			const imageAspectRatio = img.width / img.height;
			const canvasWidth = canvasHeight * imageAspectRatio;

			canvas.width = canvasWidth;
			canvas.height = canvasHeight;
			ctx.drawImage(img, 0, 0, canvasWidth, canvasHeight);
		};
		img.src = reader.result;
	};
	reader.readAsDataURL(file);
});

zoomInBtn.addEventListener("click", () => {
	zoomOld = zoom;
	zoom += 0.2;
	console.log("Zoom: " + zoom);

	renderImage(true);
});

zoomOutBtn.addEventListener("click", () => {
	zoomOld = zoom;
	zoom -= 0.2;
	console.log("Zoom: " + zoom);
	
	renderImage(true);
});

moverBtn.addEventListener("click", () => {
	isDrawing = false;
	startPoint = null;
	moverBtn.classList.toggle("activo");

	if (moverBtn.classList.contains("activo")) {
		mensajes.innerHTML = "Mover imagen";
	} else {
		mensajes.innerHTML = "&nbsp;";
	}
});

dibujarBtn.addEventListener("click", () => {
	dibujarBtn.classList.add("activo");
	moverBtn.classList.remove("activo");

	mensajes.innerHTML = "Dibujar recuadro";
});

canvas.addEventListener("mousedown", (e) => {
	if (dibujarBtn.classList.contains("activo")) {
		isDrawing = true;
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		renderImage();

		startPoint = {
			x: (e.offsetX * 1) - offsetX + (9 * zoom / 2),
			y: (e.offsetY * 1) - offsetY + (9 * zoom / 2)
		};
	} else if (moverBtn.classList.contains("activo")) {
		startPoint = {
			x: e.screenX,
			y: e.screenY,
		};
		// offsetX = e.offsetX / zoom;
		// offsetY = e.offsetY / zoom;
	}
});

canvas.addEventListener("mousemove", (e) => {
	if (isDrawing) {
		const currentPoint = {
			x: (e.offsetX * 1) - offsetX + (9 * zoom / 2),
			y: (e.offsetY * 1) - offsetY + (9 * zoom / 2)
		};
		// console.log(currentPoint);

		renderImage();
		ctx.strokeStyle = "red";
		ctx.beginPath();
		ctx.moveTo(startPoint.x, startPoint.y);
		ctx.strokeRect(startPoint.x, startPoint.y, currentPoint.x - startPoint.x, currentPoint.y - startPoint.y);
	} 
	else if (moverBtn.classList.contains("activo") && startPoint != null) {
		offsetY += (e.screenY - startPoint.y) / zoom; // <-- Actualizado
		offsetX += (e.screenX - startPoint.x) / zoom; // <-- Actualizado
		
		if (Math.abs(offsetX) >= (img.width - 50)) {
			offsetX = offsetX < 0 ? 0 - (img.width - 50) : img.width - 50;
		} 
		
		if (Math.abs(offsetY) >= (img.height - 50)) {
			offsetY = offsetY < 0 ? 0 - (img.height - 50) : img.height - 50;
		}
		console.log('offsetY: ' + offsetY);
		console.log('offsetX: ' + offsetX);
			
		renderImage(true);
		
		startPoint = {
			x: e.screenX,
			y: e.screenY,
		};
	}
});

canvas.addEventListener("mouseup", (e) => {
	if (isDrawing) {
		isDrawing = false;
		// dibujarBtn.classList.remove("activo");
		const endPoint = {
			x: e.offsetX / zoom - offsetX,
			y: e.offsetY / zoom - offsetY,
		};
		const width = endPoint.x - startPoint.x;
		const height = endPoint.y - startPoint.y;

		rect = { x: startPoint.x, y: startPoint.y, w: width, h: height};

		console.log(`Coordenadas: (${startPoint.x}, ${startPoint.y})`);
		console.log(`Dimensiones: ${width}x${height}`);
	} 
	else if (moverBtn.classList.contains("activo")) {
		// moverBtn.classList.remove("activo");
		startPoint = null;
	}
});

function renderImage(withRect = false) {
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	ctx.drawImage(
		img,
		offsetX,
		offsetY,
		img.width * (1 / zoom),
		img.height * (1 / zoom),
		0,
		0,
		canvas.width,
		canvas.height
	);
	
	if (withRect && rect != null) {
		ctx.strokeStyle = "red";
		ctx.beginPath();
		ctx.moveTo(rect.x, rect.y);
		ctx.strokeRect(rect.x, rect.y, rect.w, rect.h);
	}
}
