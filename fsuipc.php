<!doctype html>

<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
		<link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
		<link rel="stylesheet" href="css/style.css">
		<title>IVAO FSUIPC checker</title>
	</head>

	<body>
		<div class="container">
			<h1>IVAO FSUIPC checker</h1>

			<form class="form-inline mb-5" id="frmVid">
				<div class="input-group mb-2 mr-sm-2">
					<div class="input-group-prepend">
						<div class="input-group-text">VID</div>
					</div>
					<input type="text" class="form-control" id="vid">
				</div>
				<button type="submit" class="btn btn-outline-primary mb-2">Send</button>
			</form>

			<div class="card" id="monitor" style="display: none">
				<div class="card-header">Position monitoring</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-4">
							Callsign:
							<h2 id="callsign"></h2>
						</div>
						<div class="col-lg-4">
							<strong>Last:</strong>
							<div id="last"></div><br>
							<strong>Current:</strong>
							<div id="current"></div>
						</div>
						<div class="col-lg-4">
							<div id="status"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="loader"></div>
		<script src="node_modules/jquery/dist/jquery.min.js"></script>
		<script src="node_modules/popper.js/dist/umd/popper.min.js"></script>
		<script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
		<script src="js/main.js"></script>
		<script src="js/fsuipc.js"></script>
	</body>
</html>
