const phpApiUrl = "connection.php";
const defaultCity = "Bharatpur";

// Fetch and display weather data
async function fetchData(cityName) {
  let data;

  if (navigator.onLine) {
    // Online: Fetch data from API
    try {
      const response = await fetch(`${phpApiUrl}?q=${cityName}`);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      data = await response.json();

      // Store data in Local Storage
      localStorage.setItem(cityName, JSON.stringify(data));
    } catch (error) {
      console.error("Error fetching data:", error);
      alert("Error Fetching City Data");
      return;
    }
  } else {
    // Offline: Retrieve data from Local Storage
    const cachedData = localStorage.getItem(cityName);
    if (cachedData) {
      data = JSON.parse(cachedData);
    } else {
      alert("No cached data available. Please connect to the internet.");
      return;
    }
  }

  updateWeather(data);
}

// Update weather display
function updateWeather(data) {
  const weather = data[0];

  document.getElementById("cityName").textContent = weather.city || "N/A";
  document.getElementById("temperature").textContent =
    weather.temperature || "-";
  document.getElementById("pressure").textContent = weather.pressure || "N/A";
  document.getElementById("humidity").textContent = weather.humidity || "N/A";
  document.getElementById("windSpeed").textContent = weather.wind || "N/A";

  document.getElementById(
    "weatherIcon"
  ).src = `https://openweathermap.org/img/wn/${
    weather.icon_code || "01d"
  }@2x.png`;

  document.getElementById("mainWeather").textContent =
    weather.weather_description || "N/A";
  document.getElementById("weatherCondition").textContent =
    weather.weather_description || "N/A";

  setInterval(() => {
    document.getElementById("dayDate").textContent =
      new Date().toLocaleString();
  }, 1000);
}

// Search weather
function searchWeather() {
  const searchInput = document.getElementById("searchInput");
  const city = searchInput.value.trim();

  if (city === "") {
    alert("Enter City Name");
    return;
  }

  fetchData(city);
  searchInput.value = "";
}

// Event listeners
document.getElementById("searchh").addEventListener("click", searchWeather);
document
  .getElementById("searchbox")
  .addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      searchWeather();
    }
  });

// Fetch default city on load
fetchData(defaultCity);
