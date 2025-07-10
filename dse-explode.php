<?php

//$con=mysqli_connect("localhost","id20526357_root","Monju_4065","id20526357_my_database");
// Database configuration for localhost
$hostname = "localhost";
$username = "root";      // Default XAMPP/WAMP user
$password = "";          // Default XAMPP/WAMP root password is often empty
$database = "monju";

// Create a connection
$conn = mysqli_connect($hostname, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully to the database!",'<br><br>';

// You can now perform database operations using $conn

// Example: Close the connection when you're done (good practice)
// mysqli_close($conn);




$html= file_get_contents('http://localhost/monju/dse.html');

libxml_use_internal_errors(true);
$dom=new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xPath =new DOMXPath ($dom);


$all_a_tags= $xPath->query('//div[contains (@class, "scroll-item")]//a');

foreach ($all_a_tags as $tag) {

$text =$tag->textContent;

//$cleanText= str_replace ('\xC5\xA0',' ', $text); // UTF-8 non-breaking space4
$cleanText = str_replace("\xC2\xA0", ' ', $text);

//$cleanText = preg_replace('/\a+/u','', $cleanText);
$cleanText = preg_replace('/\s\s+/u', ' ', $cleanText);

$cleanText=trim($cleanText);

//echo $cleanText;

$info =explode(' ',$cleanText);
if(count($info)>=4){
	$name = $info[0];
	$price = $info[1];
	$change = $info[2];
	$percent = $info[3];
	
	$sql="SELECT * FROM dse_list WHERE name LIKE '$name'";
	$result = mysqli_query($conn,$sql);
	$count =mysqli_num_rows($result);
	if($count>=1){
		$sql2= "UPDATE dse_list SET price='$price',price_change='$change',percent='$percent'WHERE name LIKE '$name'";
		$result2 = mysqli_query($conn,$sql2);
		if ($result2) echo 'Data Updated.';
	}else{
		$sql2= "INSERT INTO dse_list ( name,price,price_change, percent)VALUES('$name','$price','$change','$percent')";
		$result2 = mysqli_query($conn,$sql2);
		if ($result2) echo 'Data Inserted.';
	}
	
	
//echo 'Name: ',$name,'<br>';
//echo 'Price: ',$price,'<br>';
//echo 'Change: ',$change,'<br>';
//echo 'Percent: ',$percent,'<br>';
//echo 'Name: ', htmlspecialchars($name), '<br>';
//echo 'Price: ', htmlspecialchars($price), '<br>';
//echo 'Change: ', htmlspecialchars($change), '<br>';
//echo 'Percent: ', htmlspecialchars($percent), '<br>';
	

}

//echo Stext/

echo '<br><br>';
}
?>