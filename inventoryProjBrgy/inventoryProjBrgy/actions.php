<?php

function addItem(){
    include __DIR__ . '/dbcon.php';
        if (isset($_POST['btn-send'])){
            $iname=$_POST['iname'];
            $category=$_POST['category'];
            $quantity=$_POST['quantity'];
        $exp=date('Y-m-d', strtotime($_POST['exp']));
        $rcv=date('Y-m-d', strtotime($_POST['rcv']));
    $q=mysqli_query($con,"INSERT INTO medsupply (iname,category,quantity,expdate,datereceived) VALUES ('$iname','$category','$quantity','$exp','$rcv')");
    
    if(!isset($q)){
         die ("Error $q" .mysqli_connect_error());
        
    }
    else
    {
         header("location: items.php");
        
    }
     }
    }
    
function addPatient(){
include __DIR__ . '/dbcon.php';
    if (isset($_POST['btn-send'])){
        $fname=$_POST['fname'];
        $lname=$_POST['lname'];
        $mname=$_POST['mname'];
    $address=$_POST['adress']; 
    $age=$_POST['age']; 
    $gender=$_POST['gender']; 
    $Bday=date('Y-m-d', strtotime($_POST['Bday'])); 
    $medication=$_POST['medication'];
    $brgy=$_POST['brgy'];
$q=mysqli_query($con,"INSERT INTO patient (Lname,Fname,Mname,addres,age,Gender,Medication,brgy) VALUES ('$lname','$fname','$mname','$address','$age','$gender','$medication','$brgy')");

if(!isset($q)){
     die ("Error $q" .mysqli_connect_error());
    
}
else
{
     header("location: patients.php?loc=$brgy");
    
}
 }
}

function barangay(){
    include __DIR__ . '/dbcon.php';
$s=mysqli_query($con,"SELECT * FROM barangays");
if ($s === false) {
    return;
}

while($row=mysqli_fetch_assoc($s)){
    $x=$row['brgy'];
    echo "<tr><td><a href='patients.php?loc=".$x."'>".$x."<a><br></td></tr>";
};
}


function barangay2(){
    include __DIR__ . '/dbcon.php';
$s=mysqli_query($con,"SELECT * FROM barangays");
if ($s === false) {
    return;
}

while($row=mysqli_fetch_assoc($s)){
    $x=$row['brgy'];
    echo "<tr><td><a href='history4.php?loc=".$x."'>".$x."<a><br></td></tr>";
};
}




function changeuser($uss){
    include __DIR__ . '/dbcon.php';
    
    if (isset($_POST['btn-send'])){
        $user=$_SESSION['user'];
    $usr=$_POST['User']; 
    $pass=$_POST['Pass'];
    
    $update = mysqli_query($con, "UPDATE users SET Username='$usr',PaSS='$pass' WHERE '$user'=Username");
    
    if (isset($q)){
    header("location: Logout.php");}
     }
    }


function getLogs(){
    include __DIR__ . '/dbcon.php';
    $s=mysqli_query($con,"SELECT * FROM logs");
    
    while($row=mysqli_fetch_assoc($s)){
        echo $row[''];
        echo $row['']; 
        //itemcode,itemname,quantity,transaction
    };
    }

function getsupply(){
    include __DIR__ . '/dbcon.php';
$s=mysqli_query($con,"SELECT * FROM medsupply");

while($row=mysqli_fetch_assoc($s)){
    echo $row['name'];
    echo $row['quantity']; 
    //category,expdate,datereceived,itemcode
};
}
function getusers(){
    include __DIR__ . '/dbcon.php';
$s=mysqli_query($con,"SELECT * FROM users");

while($row=mysqli_fetch_assoc($s)){
    echo $row['User_Name'];
    echo $row['PaSS']; 
};
}

function inventory(){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM medsupply");

while($row=mysqli_fetch_assoc($s)){
    $quantity=$row['quantity'];
    if ($quantity!=0){
    echo "<tr><td><a href='release.php?id=".$row['n']."'>".$row['iname']."</a></td>
    <td>".$row['category']."</td>
    <td>".$quantity."</td>
    <td>".$row['expdate']."</td>
    <td>".$row['datereceived']."</td></tr>";
    }
};
}



function inventory2($id){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM rhusupply");

while($row=mysqli_fetch_assoc($s)){
    $quantity=$row['quantity'];
    $itemcode=$row['itemcode'];
    if ($quantity!=0){
    echo "<form method='POST' action='release.php?itemc=".$itemcode."&id=".$id."'><tr><td>".$row['iname']."</td>
    <td>".$row['category']."</td>
    <td><input type='number' name='quantity' min = 1 max =".$quantity." required ><button type='submit'>add</button></td>
      <div style='display:none'>  <input type='text' id='itemcode' value=".$itemcode." disabled>
        <input type='text' id='name' value=".$id." disabled>
    <td>".$row['expdate']."</td>
    <td>".$row['datereceived']."</td></tr></form>";
    }
};
}



function inventorylog(){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM medsupply");

while($row=mysqli_fetch_assoc($s)){
    $quantity=$row['quantity'];
   $id=$row['n'];
        $n=$row['iname'];
    echo "<tr><td><a href='logs2.php?id=".$id."'>".$n."</a></td>
    <td>".$row['category']."</td>
    <td>".$quantity."</td>
    <td>".$row['expdate']."</td>
    <td>".$row['datereceived']."</td></tr>";
    
};
}


function logs(){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM logs");

while($row=mysqli_fetch_assoc($s)){
    echo "<tr><td>".$row['itemname']."</td>
    <td>".$row['quantity']."</td>
    <td>".$row['receiver']."</td>
    <td>".$row['transactionD']."</td></tr>";
   //n	itemcode		quantity	transaction	receiver	
};
}

function logs2($id){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM logs WHERE itemname='$id'");

while($row=mysqli_fetch_assoc($s)){
    echo "<tr><td><a href='release2.php?id=".$row['n']."&id2=".$row['itemcode']."'>".$row['transactionD']."</a></td>
    <td>".$row['receiver']."</td>
    <td>".$row['quantity']."</td>
</tr>";
   //n	itemcode		quantity	transaction	receiver	
};
}

function medlog($item,$n,$expdate,$category){
    include __DIR__ . '/dbcon.php';
    if (isset($_POST['btn-send'])){
        $iname=$_POST['iname'];
        $quantity=$_POST['quantity'];
    $date=date("m/d/y");
    $date2=date('Y-m-d', strtotime($date));
    $q=mysqli_query($con,"INSERT INTO logs(itemname,receiver,quantity,transactionD,itemcode) VALUES('$item','$iname','$quantity','$date2','$n')");
    $update = mysqli_query($con, "UPDATE medsupply SET quantity=quantity-$quantity WHERE '$n'=n");
   
    $q3=mysqli_query($con,"SELECT * FROM rhusupply WHERE itemcode='$n'");
    $r=mysqli_fetch_assoc($q3);
    if (!isset($r)){
        $q2=mysqli_query($con,"INSERT INTO rhusupply(iname,category,quantity,expdate,datereceived,itemcode) VALUES('$item','$category','$quantity','$expdate','$date2','$n')");
}
    else{
        $q2=mysqli_query($con,"UPDATE rhusupply SET quantity=quantity+$quantity WHERE '$n'=itemcode");
}




    
    


    if(!isset($q)&&!isset($update)){
        die ("Error $q" .mysqli_connect_error());
       
   }
   else
   {
        header("location: logs.php");
       
   }
    
}}

function patient($loc){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM patient WHERE brgy='$loc'");

while($row=mysqli_fetch_assoc($s)){
    echo "<tr><td><a href=profile.php?id=".$row['n'].">".$row['Lname'].", ".$row['Fname']." ".$row['Mname']."<a></td>
    <td>".$row['age']."</td>
    <td>".$row['Birthdate']."</td>
    <td>".$row['Gender']."</td></tr>";
    //Lname,Mname,adress,Birthday,age,gender,Medication
};
}

function patient2($loc){
    include __DIR__ . '/dbcon.php';
    
$s=mysqli_query($con,"SELECT * FROM patient WHERE brgy='$loc'");

while($row=mysqli_fetch_assoc($s)){
    echo "<tr><td><a href=history3.php?id=".$row['n'].">".$row['Lname'].", ".$row['Fname']." ".$row['Mname']."<a></td>
    <td>".$row['age']."</td>
    <td>".$row['Birthdate']."</td>
    <td>".$row['Gender']."</td></tr>";
    //Lname,Mname,adress,Birthday,age,gender,Medication
};
}


function patient3($loc){
    include __DIR__ . '/dbcon.php';
    $s1=mysqli_query($con,"SELECT * FROM released");
    
    while($r=mysqli_fetch_assoc($s1)){
        $ben=$r['ben'];
    $s=mysqli_query($con,"SELECT * FROM patient WHERE brgy='$loc' AND n='$ben'");

$row=mysqli_fetch_assoc($s);
    echo "<tr><td><a href=history3.php?id=".$row['n'].">".$row['Lname'].", ".$row['Fname']." ".$row['Mname']."<a></td>
    <td>".$row['age']."</td>
    <td>".$row['Birthdate']."</td>
    <td>".$row['Gender']."</td></tr>";
    //Lname,Mname,adress,Birthday,age,gender,Medication
    }
}



function release($id){
    include __DIR__ . '/dbcon.php';
    $q=mysqli_query($con,"SELECT * FROM supply2 WHERE beneficiary='$id'");
    
    
    while($r=mysqli_fetch_assoc($q)){
        $med=$r['medicine'];
    $q2=mysqli_query($con, "SELECT * FROM rhusupply WHERE itemcode='$med'");
    $r2=mysqli_fetch_assoc($q2);
        echo "<tr><td></td><td>".$r2['iname']." ".$r['quanti']."</td></tr>";
    }
    
}


?>