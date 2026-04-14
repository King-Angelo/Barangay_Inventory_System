
<!DOCTYPE html>
<html>
<?php include'head.php';
include 'dbcon.php';
include 'actions.php';
 ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div><div class="content">
          <H1>Baranggay</h1>
        <center>
            <table class="brgy"  align="center" style="line-height:60px;">
<?php
barangay2();
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
