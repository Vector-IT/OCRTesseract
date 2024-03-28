var canvas = document.querySelector('canvas')
const image = document.getElementById('image');
const dibujar = document.getElementById('dibujar');

// canvas.width = 500
// canvas.height = 325
// Set canvas size to match the image
canvas.width = image.width;
canvas.height = image.height;

canvas.top = canvas.offsetTop
canvas.left = canvas.offsetLeft
var ctx = canvas.getContext('2d')
var y = 0, x = 0
var isClicked = false

// Add event listener for selecting an area
let startX, startY, endX, endY;
let isDrawing = false;

canvas.onmousedown = (e) => {
	isClicked = true;
	if (dibujar.checked) {
		startX = e.offsetX;
		startY = e.offsetY;
		isDrawing = true;
	}
  };
  
  canvas.onmouseup = (e) => {
	isClicked = false;
  };

canvas.onmousemove = (e) => {
	if (!dibujar.checked) {
		if (isClicked) {
			x = e.pageX - canvas.left, y = e.pageY - canvas.top
			createImage(intensity,x,y)
		}
	}
	else {
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
	}
}

canvas.addEventListener('mouseup', () => {
	if (dibujar.checked) {
		isDrawing = false;
		// Calculate selected area
		const selectedArea = ctx.getImageData(startX, startY, endX - startX, endY - startY);
		console.log(selectedArea);
		// You can further process the selected area here

		dibujar.checked = false;
	}
});

function createImage(intensity, posX, posY) {
	var width = canvas.width + (intensity * (canvas.width / canvas.height))
	var height = canvas.height + intensity
	
	var x = 0 - (posX / canvas.width) * (width - canvas.width)
	var y = 0 - (posY / canvas.height) * (height - canvas.height)

	var img = new Image
	img.onload = () => {
		ctx.drawImage(img,x,y,width,height)
	}
	img.src = '1.jpg'
}
createImage(0, 0, 0)

var intensity = 0
canvas.onwheel = (e) => {
	if (e.deltaY < 0) {
		intensity = (intensity + e.deltaY > 0) ? intensity + e.deltaY : 0
	} else {
		intensity += e.deltaY
	}
	createImage(intensity, x, y)
}

canvas.onmouseover = () => {
	document.querySelector('body').style.overflow = 'hidden'
}

canvas.onmouseleave = () => {
	document.querySelector('body').style.overflow = 'visible'
}