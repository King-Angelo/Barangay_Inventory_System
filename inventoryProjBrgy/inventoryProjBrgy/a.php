<?php 
include __DIR__ . '/dbcon.php';
$n=1;
$q3=mysqli_query($con,"SELECT * FROM rhusupply WHERE itemcode='$n'");
$r=mysqli_fetch_assoc($q3);
if (!isset($r)){
echo "No";
}
else{
  echo "Yes";
}



// include __DIR__ . '/dbcon.php';
// $quantity=1;
// $iname="biogesic";
// $a=mysqli_query($con, "SELECT * FROM medsupply WHERE iname ='$iname'");
// if (isset($a)){
//     $r=mysqli_fetch_assoc($a);
//     echo $r['iname'];

// }
// else{
//     echo "no";
// // }
// if (isset($_POST['btn-send'])){
//     echo 1;
//     }

// include __DIR__ . '/dbcon.php';
// include __DIR__ . '/head.php';
// if (isset($_POST['btn-send'])){
// $usr=$_POST['User'];
// $pass=$_POST['Pass'];
// $user=$_SESSION['user'];
// echo $usr." ".$pass;

// $update = mysqli_query($con, "UPDATE users SET Username='$usr',PaSS='$pass' WHERE '$user'=Username");

// if (isset($update)){
// echo "YES";

//  }else{
//      echo "NO";
//  }
// }


// 3. function function1(){
//     echo "NO";
// }
// ?>

<!-- // <html>
// <head>
// <script type="text/javascript"> -->
 <!--
// function sayHello() {
//     var alx="
<?php 
// function1(); 
?>";
//  alert(alx)
// }
// //-->
<!-- // </script>
// </head>
// <body>
// Click here for the result
// <input type="button" onclick="sayHello()" value="Say Hello" />
// </body>
// </html>
 -->



