<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Sitra API Proxy test</title>

		<!-- Bootstrap -->
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
		<style type="text/css">
			.map-container {
				position: absolute;
				width: 100%;
				height: 100%;
				top: 0px;
				left: 0px;
				background-color: #DDD;
			}
			#map {
				width: 100%;
				height: 100%;
			}

			#nav {
				position: absolute;
				bottom: 10px;
				right: 10px;
				width: 220px;
			}
		</style>
	</head>
	<body>
		<div class="map-container">
			<div id="map"></div>
		</div>

		<div class="panel panel-default panel-primary" id="nav">
			<div class="panel-heading">
				<h3 class="panel-title">
					<a data-toggle="collapse" href="#filters">
						Filtres
						<i class="glyphicon glyphicon-chevron-down pull-right"></i>
					</a>
				</h3>
			</div>
			<div id="filters" class="panel-collapse collapse in">
				<div class="panel-body">
					<ul class="nav nav-pills nav-stacked">
						<li><a href="#">Tous les partenaires</a></li>
						<li><a href="#">Profile</a></li>
				  	<li><a href="#">Messages</a></li>
					</ul>
				</div>
			</div>
		</div>

		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<!-- Include all compiled plugins (below), or include individual files as needed -->
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
		<script type="text/javascript">
			var map;
			function initialize() {
				var mapOptions = {
					zoom: 8,
					center: new google.maps.LatLng(45.750000, 4.850000)
				};
				map = new google.maps.Map(document.getElementById('map'),
						mapOptions);
			}

			google.maps.event.addDomListener(window, 'load', initialize);

			$('#filters').on('hidden.bs.collapse', function () {
			  $(this).parent().find('i').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
			});
			$('#filters').on('shown.bs.collapse', function () {
			  $(this).parent().find('i').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
			});
		</script>
	</body>
</html>