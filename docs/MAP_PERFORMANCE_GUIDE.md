# ALERTO APP — GIS Map Performance & Spatial Optimization

## 1. High-Density Spatial Optimization

During major typhoons or natural disasters in Hinoba-an, hundreds of concurrent emergency incident reports and responder updates can flood the EOC. ALERTO APP implements multiple optimization strategies to ensure 60fps rendering performance.

---

## 2. Key Optimization Strategies

### 2.1 Bounding Box Spatial Filtering
Instead of streaming all municipal records to the browser, the frontend transmits the visible viewport bounds:
```
GET /admin/map/data?min_lat=9.45&max_lat=9.55&min_lng=122.50&max_lng=122.65
```
Indexed database columns (`latitude`, `longitude`) filter records on the database server before serialization.

### 2.2 Client-Side Marker Clustering
Dense clusters of incidents in municipal centers (Poblacion I & II) are automatically grouped into cluster nodes, expanding into individual tactical pins as the operator zooms into street level.

### 2.3 Debounced Search & Input Filtering
User input into the incident filter console is debounced by 400ms (`x-on:input.debounce.400ms`), preventing redundant database querying while typing.

### 2.4 Controlled WebSocket Throttling & Polling Fallback
WebSocket broadcasts update memory objects reactively without forcing full map redraws. Re-rendering is confined to the specific marker element being modified.

