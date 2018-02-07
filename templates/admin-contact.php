<!doctype html>
<html>
<head>
	<title>Mail Capture: '<?=$this->_name;?>' Signup Admin</title>
	<style>
		body {
			text-align: center;
			font-family: sans-serif;
		}

		.logo {
			margin: 10px;
			height: 64px;
			width: 124px;
		}
		.btn {
		  background: #3498db;
		  background-image: -webkit-linear-gradient(top, #3498db, #2980b9);
		  background-image: -moz-linear-gradient(top, #3498db, #2980b9);
		  background-image: -ms-linear-gradient(top, #3498db, #2980b9);
		  background-image: -o-linear-gradient(top, #3498db, #2980b9);
		  background-image: linear-gradient(to bottom, #3498db, #2980b9);
		  -webkit-border-radius: 28;
		  -moz-border-radius: 28;
		  border-radius: 28px;
		  color: #ffffff;
		  font-size: 20px;
		  padding: 10px 20px 10px 20px;
		  text-decoration: none;
		}

		.btn:hover {
		  background: #3cb0fd;
		  background-image: -webkit-linear-gradient(top, #3cb0fd, #3498db);
		  background-image: -moz-linear-gradient(top, #3cb0fd, #3498db);
		  background-image: -ms-linear-gradient(top, #3cb0fd, #3498db);
		  background-image: -o-linear-gradient(top, #3cb0fd, #3498db);
		  background-image: linear-gradient(to bottom, #3cb0fd, #3498db);
		  text-decoration: none;
		}
		table {
			margin: 20px auto;
		}
		th, td.message {
			padding: 10px 10px 20px;
		}
		td { 
			padding-left: 10px; 
			padding-right: 10px;
		}

	</style>

</head>
<body>
	<h1>Mail Capture: '<?=$this->_name;?>' Contact Form Admin</h1>
	<?php $data = array_reverse( $data ); ?>

	<a class="btn download" href="/<?=$this->_name;?>/download">Download CSV</a>

	<?php if( count($data) ): ?>
	<table>
		<tr>
			<?php $headerRow = $data[0];
				unset($headerRow['message']); 
				foreach( array_keys($headerRow) as $header ): ?>
			<th><?=ucwords( $header );?></th>
			<?php endforeach; ?>
		</tr>

		<?php foreach( $data as $row ): 
			$message = $row['message'];
			unset($row['message']);
		?>
		<tr>
			<?php foreach( $row as $key => $value ): 
				switch( $key ) {
					case "email":
						$value = "<a href=\"mailto:{$value}\">{$value}</a>";
						break;
					case "created":
						$value = date('D dS M, H:i', strtotime($value));
				}
			?>
			<td><?=$value;?></td>
			<?php endforeach; ?>
		</tr>
		<tr>
			<td class="message" colspan="<?=count( $row );?>">
				<?=$message;?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php endif; ?>

</body>
</html>