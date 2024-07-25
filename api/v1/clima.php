<?php

declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

use DB\SQL;
use DB\SQL\Mapper;

// ---------------------------------------- ff setup
$f3 = \Base::instance();
$f3->set('DEBUG', 3);

$conn_str = 'mysql:host=' . $_ENV["DB_HOST"] . ';port=3306;dbname=' . $_ENV["DB_NAME"];
$f3->set('DB', new DB\SQL($conn_str, $_ENV["DB_USER"], $_ENV["DB_PASS"]));

$f3->set('ONERROR', function ($f3) {
    $err = $f3->get('ERROR');
    if ($f3->get('DEBUG') == 0) {
        unset($err["trace"]);
    }
    $err["debug_level"] = $f3->get('DEBUG');;
    echo  json_encode($err);
    $f3->error($f3->get('ERROR.code'));
});


function getWeather($lat, $lon, $units) {

    $lang = $units === "metric" ? "sp" : "en";
        
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=$units&appid=9314e40cec5a554c9070943a84440eb8&lang=$lang";
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    return $data;
}

function saveToWeatherTable(string $city_name, $dataC, $dataF) {
    $f3 = \Base::instance();
    $db = $f3->get('DB');
    $mapper = new Mapper($db, 'weather');
    
    $mapper->cityName = $city_name;
    $mapper->lat = $dataC["coord"]["lat"];
    $mapper->lon = $dataC["coord"]["lon"];
    $mapper->weatherId = $dataC["weather"][0]["id"];
    $mapper->iconId = $dataC["weather"][0]["icon"];
    $mapper->iconSmallURL = "http://openweathermap.org/img/wn/" . $dataC["weather"][0]["icon"] . ".png";
    $mapper->iconBigURL = "http://openweathermap.org/img/wn/" . $dataC["weather"][0]["icon"] . "@2x.png";
    $mapper->humidity = $dataC["main"]["humidity"];
    $mapper->windSpeed = $dataC["wind"]["speed"];

    $mapper->tempC = $dataC["main"]["temp"];
    $mapper->feelsLikeC = $dataC["main"]["feels_like"];
    $mapper->tempMinC = $dataC["main"]["temp_min"];
    $mapper->tempMaxC = $dataC["main"]["temp_max"];
    $mapper->descC = $dataC["weather"][0]["description"];
    
    $mapper->tempF = $dataF["main"]["temp"];
    $mapper->feelsLikeF = $dataF["main"]["feels_like"];
    $mapper->tempMinF = $dataF["main"]["temp_min"];
    $mapper->tempMaxF = $dataF["main"]["temp_max"];
    $mapper->descF = $dataF["weather"][0]["description"];
    
    $mapper->save();
}

$climaJuarezC = getWeather(31.679315, -106.417367, "metric");
$climaJuarezF = getWeather(31.679315, -106.417367, "imperial");
saveToWeatherTable("Juarez", $climaJuarezC, $climaJuarezF);
$climaElPasoC = getWeather(31.780131, -106.377575, "metric");
$climaElPasoF = getWeather(31.780131, -106.377575, "imperial");
saveToWeatherTable("El Paso", $climaElPasoC, $climaElPasoF);

echo "Clima guardado\n\n";