<html>
	<head>
		<title>Wassup</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
		<script>
		$(document).ready(function(){
			var image = $('#im').html();
			if(image) {
				$('#ha').html("<img src='loading.gif' alt='loading' />");
				$.get('convert.php?im='+image,function(data){
					$('#ha').html(data);
				});
			} 
		});
		</script>
	</head>
	<body>
		<?php
		if(isset($_POST['submit'])){
			if(($_FILES['file']['type'] == 'image/png' || $_FILES['file']['type'] == 'image/jpeg') && $_FILES['file']['error'] == 0){
				$rand = 'images/';
				for($i = 0;$i < 6;$i++)
				$rand .= rand() % 10;
				// creates random directory and stores image file into it
				mkdir($rand);
				$image = $rand . "/". $_FILES['file']['name'];
				move_uploaded_file($_FILES['file']['tmp_name'],$image);
				
				echo "<div id='im' style='display:none;'>" .  $image . "</div>";
				
			} else {
				echo "not supported file type";
			}
		}
		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
		<input type='file' name='file' /><input type='submit' name='submit' value='Upload' />
		</form>
		<br /><br />
		<div id='ha'>
		</div>
	</body>
</html>	