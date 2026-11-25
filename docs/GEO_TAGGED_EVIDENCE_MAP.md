# ALERTO APP — Geo-Tagged Evidence & Media Attachment Mapping

## 1. Overview

The **Geo-Tagged Evidence Layer** visualizes photo and video evidence submitted by citizens, responding personnel, and agency offices directly upon the GIS map.

---

## 2. GPS Location Source Integrity

To maintain legal admissibility and chain of custody, ALERTO APP strictly distinguishes the origin of evidence coordinates:

| Location Source | Technical Meaning | Legal Admissibility |
|---|---|---|
| `exif_embedded` | Extracted directly from JPEG/HEIC camera hardware metadata | High (Original Capture Coordinates) |
| `device_live_upload` | Captured via browser Geolocation API at time of submission | High (Upload Location) |
| `incident_inherited` | Inherited from the parent incident report pin | Auxiliary Reference |
| `none` | No GPS telemetry available | Unpositioned |

> [!CAUTION]
> The system **never** claims an upload coordinate or inherited pin is the original capture location if EXIF hardware GPS metadata is absent.

---

## 3. Interactive Evidence Inspection

Clicking an evidence pin on the Operations Map displays:
- File name and MIME type.
- Media thumbnail and high-resolution modal preview.
- Parent incident reference number.
- Uploader identity and role.
- Capture timestamp and upload timestamp.
- Verified GPS accuracy in meters.

