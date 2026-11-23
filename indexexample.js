import React, { useEffect, useState } from "react";
import {
  MapContainer,
  TileLayer,
  Marker,
  Polyline,
  Popup,
  Tooltip,
  useMap,
} from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-polylinedecorator";

// Fix Leaflet default marker icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl:
    "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png",
  iconUrl:
    "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png",
  shadowUrl:
    "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
});

// Auto-fit map
function AutoFitMap({ points }) {
  const map = useMap();
  useEffect(() => {
    if (points.length === 0) return;
    const bounds = L.latLngBounds(points);
    map.flyToBounds(bounds, { padding: [50, 50] });
  }, [points, map]);
  return null;
}

const startIcon = L.icon({
  iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png",
  iconSize: [32, 32],
  iconAnchor: [16, 32],
});
const endIcon = L.icon({
  iconUrl: "https://cdn-icons-png.flaticon.com/512/149/149060.png",
  iconSize: [32, 32],
  iconAnchor: [16, 32],
});

export default function Wapelement({ vin, start, end }) {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!vin || !start || !end) return;
    setLoading(true);
    async function fetchData() {
      try {
        const res = await fetch(
          `https://ble.nerdherdlab.com/geolocationhistory.php?vin=${vin}&start=${encodeURIComponent(
            start
          )}&end=${encodeURIComponent(end)}`
        );
        const json = await res.json();
        if (json.status === "success") setData(json.data);
        else setData([]);
      } catch (err) {
        console.error(err);
        setData([]);
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, [vin, start, end]);

  const positions = data.map((d) => [d.lat, d.lng]);
  const center =
    positions.length > 0 ? positions[0] : { lat: 20.5937, lng: 78.9629 };

  return (
    <div style={{ height: "100%", width: "100%" }}>
      {loading && <div className="text-white p-4">Loading data...</div>}

      <MapContainer
        center={center}
        zoom={14}
        minZoom={5}
        maxZoom={25}
        style={{ height: "100%", width: "100%" }}
        scrollWheelZoom={true}
      >
        
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        <AutoFitMap points={positions} />

        {positions.length > 0 && (
          <>
            <Polyline
              positions={positions}
              pathOptions={{ color: "blue", weight: 4, opacity: 0.8 }}
            >
              {data.map((point, i) => (
                <Tooltip
                  key={i}
                  direction="top"
                  offset={[0, -10]}
                  opacity={0.9}
                >
                  <div style={{ minWidth: 150 }}>
                    <div><strong>Time:</strong> {point.time}</div>
                    <div><strong>SOC:</strong> {point.soc ?? "N/A"}%</div>
                    <div><strong>Speed:</strong> {point.speed_kmph ?? "N/A"} km/h</div>
                    <div><strong>Consumption:</strong> {point.currentconsumption ?? "N/A"} kW</div>
                    <div><strong>Trip:</strong> {point.tripkm ?? "N/A"} km</div>
                  </div>
                </Tooltip>
              ))}
            </Polyline>

            <Marker position={positions[0]} icon={startIcon}>
              <Popup>Start Point</Popup>
            </Marker>
            <Marker position={positions[positions.length - 1]} icon={endIcon}>
              <Popup>End Point</Popup>
            </Marker>
          </>
        )}
      </MapContainer>
    </div>
  );
}
