# EXIF & Video Container Metadata Handling

## 1. Zero-Trust Extraction Architecture (`MediaMetadataService`)
ALERTO APP parses media headers natively without relying exclusively on optional host extensions:

### Photographs (JPEG / PNG / WebP)
1. **Native EXIF + Pure-PHP Fallback:**
   - Queries PHP `exif_read_data()` when `ext-exif` is compiled.
   - Falls back automatically to a bounds-checked **Pure-PHP JPEG APP1 EXIF/TIFF IFD0 & GPS Sub-IFD binary parser** (`parseJpegExifPurePhp`), supporting both Little-Endian (`II`) and Big-Endian (`MM`) byte ordering.
2. **Extracted Tags:**
   - `GPSLatitude` (`0x0002`) + `GPSLatitudeRef` (`N`/`S`)
   - `GPSLongitude` (`0x0004`) + `GPSLongitudeRef` (`E`/`W`)
   - `GPSHPositioningError` / Accuracy (`0x001F`)
   - Camera `Make` (`0x010F`), `Model` (`0x0110`), and `DateTimeOriginal` (`0x9003`) / `DateTime` (`0x0132`).
3. **Dimensions:** Extracted via `getimagesize()` or native PNG `IHDR` binary header parser.

### Videos (MP4 / QuickTime MOV / WebM)
1. **MP4/MOV Container Atoms:**
   - Extracts timescale & duration directly from `mvhd` boxes (`duration_seconds`).
   - Searches for embedded QuickTime / ISO-6709 GPS boxes (`©xyz` / `\xA9xyz`, e.g., `+09.5936+122.4542/`).
2. **FFprobe Integration:** When `ffprobe` is installed on the host system, extracts stream width, height, codec, duration, and container location tags safely via `Process::timeout(10)`.
3. **Missing Video GPS:** Many mobile browsers and compressed messaging videos strip container GPS atoms. When absent, ALERTO strictly stores `LOCATION_UNAVAILABLE` (`latitude = null, longitude = null`). Upload coordinates are **never** disguised as capture coordinates.

---

## 2. Coordinate Sanitation & Security Rules
- **Null-Island Rejection:** `(0, 0)` coordinates and NaN/non-finite floats are rejected as invalid.
- **WGS84 Bounds Enforcement:** Latitude must satisfy `[-90, 90]`, Longitude `[-180, 180]`.
- **String Sanitization:** Binary EXIF ASCII tags are stripped of control bytes (`[^\x20-\x7E]`) and length-capped before JSON serialization in `raw_metadata`.

