<?php
/**
 * orcae_upload.ctp layout is the main layout of orcae_upload
 * It defines page structure vlid for every page
 * @param subtitle defines the subtitle of the current page to create the title "Orcae-Upload <page name>"
 */
// Defines page title
$title = "Orcae-Upload ".trim($this->fetch('subtitle'));
?>

<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- page title (in this form: "ORCAE-upload <subtitle>") -->
  <title><?php echo $title; ?></title>

  <!-- Default resources for every orcae-upload's layout -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
  <!-- Popper -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

  <?php
		// Outputs orcae main stylesheet
		echo $this->Html->css('orcae_upload');
    // Outputs resources included by specific page
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
</head>
<body>
	<!-- Outputs page-specific content -->
  <?php echo $this->fetch('content'); ?>
</body>
</html>
