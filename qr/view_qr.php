<?php
include '../includes/header.php';
?>

<div class="container mt-4">

<h3>QR Codes</h3>

<div class="row">

<?php

$files =
glob(
"../uploads/qr_codes/*.png"
);

foreach($files as $file)
{
?>

<div class="col-md-3">

<div class="card mb-3">

<img
src="<?php echo $file; ?>"
class="card-img-top">

</div>

</div>

<?php
}
?>

</div>

</div>

<?php
include '../includes/footer.php';
?>