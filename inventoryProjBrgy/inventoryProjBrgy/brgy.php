<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';
?><!DOCTYPE html>
<html>
<?php include __DIR__ . '/head.php'; ?>
<body>
<div class="pane">
<?php include __DIR__ . '/nav.php'; ?>
   <div class="content">
     
          </div><div class="content">
          <H1>Barangay</h1>
        <center>
            <table class="brgy"  align="center" style="line-height:60px;">
<?php
barangay();
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
