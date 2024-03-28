const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const image = document.getElementById('image');

// Set canvas size to match the image
canvas.width = image.width;
canvas.height = image.height;

// Draw the image on the canvas
ctx.drawImage(image, 0, 0, image.width, image.height);

// Add event listener for selecting an area
let startX, startY, endX, endY;
let isDrawing = false;

// Set the initial scale
let scale = 1;

canvas.addEventListener('mousedown', (e) => {
	startX = e.offsetX;
	startY = e.offsetY;
	isDrawing = true;
});

canvas.addEventListener('mousemove', (e) => {
	if (isDrawing) {
		endX = e.offsetX;
		endY = e.offsetY;
		// Clear previous selection
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		// Redraw the image
		ctx.drawImage(image, 0, 0, image.width, image.height);
		// Draw the selection rectangle
		ctx.strokeStyle = 'red';
		ctx.lineWidth = 2;
		ctx.strokeRect(startX, startY, endX - startX, endY - startY);
	}
});

canvas.addEventListener('mouseup', () => {
	isDrawing = false;
	// Calculate selected area
	const selectedArea = ctx.getImageData(startX, startY, endX - startX, endY - startY);
	console.log(selectedArea);
	// You can further process the selected area here
});

// Function to zoom in
function zoomIn() {
	scale += 0.1;
	ctx.scale(scale, scale);
	ctx.drawImage(image, 0, 0, image.width, image.height, 0, 0, canvas.width, canvas.height);
	// Set canvas size to match the image
	// canvas.width = image.width;
	// canvas.height = image.height;
}

// Function to zoom out
function zoomOut() {
	if (scale > 1) {
		scale -= 0.1;
		ctx.scale(scale, scale);
		ctx.drawImage(image, 0, 0, image.width, image.height, 0, 0, canvas.width, canvas.height);
		// canvas.width = image.width;
		// canvas.height = image.height;
	}
}

