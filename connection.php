<?php
date_default_timezone_set("Asia/Kathmandu");

// Establish database connection for infinityfree
$serverName = "sql312.infinityfree.com"; 
$userName = "if0_38286196"; 
$password = "vcBkSxhXYjKP3";
$dbName = "if0_38286196_weatherdata"; 

$conn = mysqli_connect($serverName, $userName, $password, $dbName);

// Check connection
if (!$conn) {
    die(json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]));
}

// Ensure weather table exists (cannot create DB in InfinityFree)
$createTable = "CREATE TABLE IF NOT EXISTS weather (
    city VARCHAR(255) PRIMARY KEY,
    temperature VARCHAR(50),
    humidity VARCHAR(50),
    wind VARCHAR(50),
    pressure VARCHAR(50),
    icon_code VARCHAR(5),
    weather_description VARCHAR(50),
    last_fetched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

// Fetch city name from URL parameter
$cityName = isset($_GET['q']) ? $_GET['q'] : "Bharatpur";

// Function to fetch and insert/update weather data
function fetchAndInsert($cityName, $conn, $operation) {
    $apiKey = "ab9942e22966c2f3fecd5096b16c72f6"; // OpenWeather API Key
    $url = "https://api.openweathermap.org/data/2.5/weather?q=".$cityName."&units=metric&appid=".$apiKey;
    
    $response = file_get_contents($url);
    if (!$response) {
        echo json_encode(["error" => "Failed to fetch data from API"]);
        return;
    }

    $data = json_decode($response, true);
    
    if (!isset($data['main'])) {
        echo json_encode(["error" => "Invalid city or API error"]);
        return;
    }

    // Extract data
    $temperature = $data['main']['temp'];
    $humidity = $data['main']['humidity'];
    $wind = $data['wind']['speed'];
    $pressure = $data['main']['pressure'];
    $icon_code = $data['weather'][0]['icon'];
    $weather_description = $data['weather'][0]['description'];

    // Insert or update weather data
    if ($operation == "insert") {
        $query = "INSERT INTO weather (city, temperature, humidity, wind, pressure, icon_code, weather_description, last_fetched) 
                  VALUES ('$cityName', '$temperature', '$humidity', '$wind', '$pressure', '$icon_code', '$weather_description', NOW())";
    } else {
        $query = "UPDATE weather SET temperature='$temperature', humidity='$humidity', wind='$wind', pressure='$pressure', 
                  icon_code='$icon_code', weather_description='$weather_description', last_fetched=NOW() WHERE city='$cityName'";
    }

    if (!mysqli_query($conn, $query)) {
        echo json_encode(["error" => "Database update failed"]);
    }
}

// Check if city exists in the database
$selectData = "SELECT * FROM weather WHERE city = '$cityName'";
$result = mysqli_query($conn, $selectData);

if (mysqli_num_rows($result) == 0) {
    fetchAndInsert($cityName, $conn, "insert");
} else {
    $row = mysqli_fetch_assoc($result);
    $last_fetched = strtotime($row["last_fetched"]);
    $difference = time() - $last_fetched;

    if ($difference > 2 * 3600) { // Update if data is older than 2 hours
        fetchAndInsert($cityName, $conn, "update");
    }
}

// Fetch latest data and return as JSON response
$result = mysqli_query($conn, $selectData);
$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rows);

// Close connection
mysqli_close($conn);
?>
