<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>DAMSP Tester</title>
</head>

<body>
	<form id="form" method="post" enctype="multipart/form-data" onsubmit="submitForm(); return false;">
		<label for="file">Select file:</label>
		<input type="file" name="file" id="file">
		<br>
		<div style="display: none;">
			<label for="page_segmentation">Page Segmentation:</label>
			<input type="number" name="page_segmentation" id="page_segmentation" value="3">
			<br>
			<label for="resolution">Resolution:</label>
			<input type="number" name="resolution" id="resolution" value="155">
			<br>
		</div>
		<label for="debug">Debug:</label>
		<input type="number" name="debug" id="debug" value="0">
		<br>
		<button type="submit" name="submit">Submit</button>
	</form>
	<iframe id="iframePDF" src="" style="display: inline; float: left; width: 50%; margin-right: 10px;"></iframe>
	<div id="divResult" style="width: 48%; float: left;"></div>

	<script>
		async function submitForm() {
			document.getElementById("divResult").innerHTML = "Processing...";

			if (!document.getElementById("file").files[0]) {
				document.getElementById("divResult").innerHTML = "No file selected";
				return;
			}

			let headersList = {
				"Accept": "*/*"
			}

			let bodyContent = new FormData();
			bodyContent.append("page_segmentation", document.getElementById("page_segmentation").value);
			bodyContent.append("resolution", document.getElementById("resolution").value);
			bodyContent.append("debug", document.getElementById("debug").value);
			bodyContent.append("file", document.getElementById("file").files[0]);

			let response = await fetch("damsp.php", {
				method: "POST",
				body: bodyContent,
				headers: headersList
			});

			let data = await response.text();

			try {
				let dataJSON = JSON.parse(data);

				document.getElementById("divResult").innerHTML = '';

				for (const key in dataJSON) {
					if (dataJSON.hasOwnProperty(key)) {
						if (key !== 'output') {
							document.getElementById("divResult").innerHTML+= `<strong>${key}:</strong> ${dataJSON[key]}<br>`;
						}
					}
				}

				if (dataJSON.output !== undefined) {
					document.getElementById("divResult").innerHTML+= '<strong>Output:</strong><br>';
					let I = 0;
					for (let item of dataJSON.output) {
						document.getElementById("divResult").innerHTML+= I + ': ' + item + '<br>';
						I++;
					}
				}
			} catch (error) {
				document.getElementById("divResult").innerHTML = data;
			}

		}

		const iframePDF = document.getElementById('iframePDF');
		const formu = document.querySelector('form');

		const height = window.innerHeight - formu.clientHeight - 45;
		iframePDF.style.height = `${height}px`;
		
		document.getElementById("file").addEventListener("change", (e) => {
			document.getElementById("divResult").innerHTML = "";
			document.getElementById("iframePDF").src = URL.createObjectURL(e.target.files[0]);
			document.getElementById("page_segmentation").focus();
		});
	</script>
</body>

</html>