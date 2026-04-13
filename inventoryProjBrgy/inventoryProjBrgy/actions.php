<?php

require_once __DIR__ . '/barangay_funcs.php';

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

// ═══════════════════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════════════════════
// Task 3 — Resident & Permit functions (Member 3)
// Compatible with PHP 7.0+  (no arrow functions)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * List residents for a barangay. Admin can include archived.
 */
function list_residents($barangay_id, $include_archived = false) {
    include __DIR__ . '/dbcon.php';
    $bid = (int)$barangay_id;
    $status_clause = $include_archived ? '' : "AND r.status = 'active'";
    $sql = "SELECT r.*, b.brgy AS barangay_name
            FROM residents r
            LEFT JOIN barangays b ON b.n = r.barangay_id
            WHERE r.barangay_id = $bid
            $status_clause
            ORDER BY r.last_name, r.first_name";
    $result = mysqli_query($con, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Fetch a single resident by ID.
 */
function get_resident($id) {
    include __DIR__ . '/dbcon.php';
    $id = (int)$id;
    $q = mysqli_query($con, "SELECT * FROM residents WHERE id = $id");
    return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
}

/**
 * Create a new resident. Returns new ID or 0 on failure.
 */
function create_resident($d, $created_by) {
    include __DIR__ . '/dbcon.php';
    $created_by = (int)$created_by;
    $bid   = (int)$d['barangay_id'];
    $lname = mysqli_real_escape_string($con, (string)($d['last_name']    ?? ''));
    $fname = mysqli_real_escape_string($con, (string)($d['first_name']   ?? ''));
    $mname = mysqli_real_escape_string($con, (string)($d['middle_name']  ?? ''));
    $email = mysqli_real_escape_string($con, (string)($d['email']        ?? ''));
    $phone = mysqli_real_escape_string($con, (string)($d['phone']        ?? ''));
    $bday  = mysqli_real_escape_string($con, (string)($d['birthdate']    ?? ''));
    $gend  = mysqli_real_escape_string($con, (string)($d['gender']       ?? ''));
    $addr  = mysqli_real_escape_string($con, (string)($d['address_line'] ?? ''));
    $sql = "INSERT INTO residents
              (barangay_id, last_name, first_name, middle_name, email, phone,
               birthdate, gender, address_line, status, created_by_user_id)
            VALUES ($bid,'$lname','$fname','$mname','$email','$phone',
                    '$bday','$gend','$addr','active',$created_by)";
    if (mysqli_query($con, $sql)) {
        return (int)mysqli_insert_id($con);
    }
    return 0;
}

/**
 * Update an existing resident. Returns true on success.
 */
function update_resident($id, $d) {
    include __DIR__ . '/dbcon.php';
    $id    = (int)$id;
    $lname = mysqli_real_escape_string($con, (string)($d['last_name']    ?? ''));
    $fname = mysqli_real_escape_string($con, (string)($d['first_name']   ?? ''));
    $mname = mysqli_real_escape_string($con, (string)($d['middle_name']  ?? ''));
    $email = mysqli_real_escape_string($con, (string)($d['email']        ?? ''));
    $phone = mysqli_real_escape_string($con, (string)($d['phone']        ?? ''));
    $bday  = mysqli_real_escape_string($con, (string)($d['birthdate']    ?? ''));
    $gend  = mysqli_real_escape_string($con, (string)($d['gender']       ?? ''));
    $addr  = mysqli_real_escape_string($con, (string)($d['address_line'] ?? ''));
    $sql = "UPDATE residents SET
              last_name='$lname', first_name='$fname', middle_name='$mname',
              email='$email', phone='$phone', birthdate='$bday',
              gender='$gend', address_line='$addr'
            WHERE id=$id";
    return (bool)mysqli_query($con, $sql);
}

/**
 * Archive a resident — admin only. Sets status = 'archived'.
 */
function archive_resident($id) {
    include __DIR__ . '/dbcon.php';
    $id = (int)$id;
    return (bool)mysqli_query($con, "UPDATE residents SET status='archived' WHERE id=$id");
}

/**
 * List permits, optionally filtered by resident.
 */
function list_permits($resident_id = null) {
    include __DIR__ . '/dbcon.php';
    $where = ($resident_id !== null) ? 'WHERE p.resident_id = ' . (int)$resident_id : '';
    $sql = "SELECT p.*,
                   r.last_name, r.first_name,
                   pt.name AS permit_type_name,
                   su.UserName AS submitted_by_name,
                   au.UserName AS approved_by_name
            FROM permits p
            JOIN residents r    ON r.id  = p.resident_id
            JOIN permit_types pt ON pt.id = p.permit_type_id
            LEFT JOIN users su  ON su.id  = p.submitted_by
            LEFT JOIN users au  ON au.id  = p.approved_by
            $where
            ORDER BY p.updated_at DESC";
    $result = mysqli_query($con, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Fetch a single permit by ID.
 */
function get_permit($id) {
    include __DIR__ . '/dbcon.php';
    $id = (int)$id;
    $sql = "SELECT p.*, r.last_name, r.first_name, pt.name AS permit_type_name
            FROM permits p
            JOIN residents r    ON r.id  = p.resident_id
            JOIN permit_types pt ON pt.id = p.permit_type_id
            WHERE p.id = $id";
    $q = mysqli_query($con, $sql);
    return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
}

/**
 * Create a permit in 'draft' status. Returns new ID or 0.
 */
function create_permit($resident_id, $permit_type_id, $user_id) {
    include __DIR__ . '/dbcon.php';
    $rid  = (int)$resident_id;
    $ptid = (int)$permit_type_id;
    $uid  = (int)$user_id;
    $ref  = 'REF-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $sql  = "INSERT INTO permits (resident_id, permit_type_id, reference_no, status, submitted_by)
             VALUES ($rid, $ptid, '$ref', 'draft', $uid)";
    return mysqli_query($con, $sql) ? (int)mysqli_insert_id($con) : 0;
}

/**
 * Staff: move draft → submitted.
 */
function submit_permit($permit_id, $user_id) {
    include __DIR__ . '/dbcon.php';
    $pid = (int)$permit_id;
    $uid = (int)$user_id;
    $ok  = mysqli_query($con,
        "UPDATE permits SET status='submitted', submitted_by=$uid, submitted_at=NOW()
         WHERE id=$pid AND status='draft'");
    return $ok && mysqli_affected_rows($con) > 0;
}

/**
 * Admin: approve or reject a submitted permit.
 * $action must be 'approved' or 'rejected'.
 */
function decide_permit($permit_id, $admin_id, $action, $remarks = '') {
    include __DIR__ . '/dbcon.php';
    $pid     = (int)$permit_id;
    $aid     = (int)$admin_id;
    $action  = ($action === 'approved') ? 'approved' : 'rejected';
    $remarks = mysqli_real_escape_string($con, (string)$remarks);
    $ok = mysqli_query($con,
        "UPDATE permits SET status='$action', approved_by=$aid, approved_at=NOW(), remarks='$remarks'
         WHERE id=$pid AND status='submitted'");
    return $ok && mysqli_affected_rows($con) > 0;
}

/**
 * Get all active permit types.
 */
function get_permit_types() {
    include __DIR__ . '/dbcon.php';
    $q    = mysqli_query($con, "SELECT * FROM permit_types WHERE is_active=1 ORDER BY name");
    $rows = array();
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Get all barangays for dropdowns.
 */
function get_barangays() {
    include __DIR__ . '/dbcon.php';
    $q    = mysqli_query($con, "SELECT n, brgy FROM barangays ORDER BY brgy");
    $rows = array();
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
