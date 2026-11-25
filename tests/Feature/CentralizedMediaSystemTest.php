<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessMediaAttachmentJob;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Services\MediaManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class CentralizedMediaSystemTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser(string $type): User
    {
        return User::factory()->create([
            'account_type' => $type,
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
    }

    private function createOffice(string $prefix = 'MED'): Office
    {
        $code = substr($prefix.'-'.uniqid(), 0, 32);

        return Office::create([
            'name' => 'Agency '.$code,
            'code' => $code,
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => strtolower($code).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);
    }

    private function createOfficeUser(Office $office): User
    {
        $user = $this->createUser('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        return $user;
    }

    private function createPersonnel(Office $office): User
    {
        $user = $this->createUser('personnel');
        PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'B-'.strtoupper(Str::random(5)),
            'rank_designation' => 'Field Responder',
            'specialization' => 'Rescue',
            'is_active_responder' => true,
        ]);

        return $user;
    }

    private function createIncident(User $reporter, string $status = 'pending_review'): Incident
    {
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-2026-'.strtoupper(Str::random(6)),
            'reporter_id' => $reporter->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'priority' => 'high',
            'status' => $status,
            'description' => 'Emergency test incident.',
            'caller_name' => $reporter->name,
            'caller_phone' => '09171234567',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.58550000,
            'longitude' => 122.48200000,
            'accuracy' => 12.0,
            'location_source' => 'gps_device',
        ]);

        return $incident;
    }

    /**
     * Build a minimal authentic JPEG binary without EXIF metadata.
     */
    private function buildMinimalJpegBinary(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";
    }

    /**
     * Build an authentic JPEG binary embedding a standard little-endian APP1 TIFF/EXIF GPS IFD block.
     * Coordinates encoded: 9°35'36.96" N (9.59360000), 122°27'15.12" E (122.45420000).
     */
    private function buildJpegWithExifGpsBinary(): string
    {
        // Construct little-endian ('II') TIFF body starting at offset 0:
        // Header (8 bytes): 'II' + 0x002A + offset to IFD0 (8)
        $tiff = 'II'.pack('vV', 42, 8);

        // IFD0: 3 entries: Make(0x010F), Model(0x0110), GPSIFDPointer(0x8825)
        // IFD0 starts at 8: 2 bytes count + 3 * 12 bytes entries + 4 bytes next IFD = 42 bytes -> ends at 50
        $make = "Samsung\0"; // 8 bytes at offset 50
        $model = "Galaxy\0\0"; // 8 bytes at offset 58
        $gpsIfdOffset = 66;

        $ifd0 = pack('v', 3);
        $ifd0 .= pack('vvVV', 0x010F, 2, strlen($make), 50); // Make ASCII
        $ifd0 .= pack('vvVV', 0x0110, 2, strlen($model), 58); // Model ASCII
        $ifd0 .= pack('vvVV', 0x8825, 4, 1, $gpsIfdOffset); // GPS IFD Pointer
        $ifd0 .= pack('V', 0); // No next IFD

        // GPS IFD at offset 66: 5 entries -> 2 + 5*12 + 4 = 66 bytes -> ends at 132
        $latRationalsOffset = 132; // 3 rationals = 24 bytes -> 132..155
        $lngRationalsOffset = 156; // 3 rationals = 24 bytes -> 156..179

        $gpsIfd = pack('v', 5);
        $gpsIfd .= pack('vvVa4', 0x0001, 2, 2, "N\0\0\0"); // GPSLatitudeRef
        $gpsIfd .= pack('vvVV', 0x0002, 5, 3, $latRationalsOffset); // GPSLatitude
        $gpsIfd .= pack('vvVa4', 0x0003, 2, 2, "E\0\0\0"); // GPSLongitudeRef
        $gpsIfd .= pack('vvVV', 0x0004, 5, 3, $lngRationalsOffset); // GPSLongitude
        $gpsIfd .= pack('vvVV', 0x001F, 4, 1, 8); // GPSHPositioningError = 8m (inline ULONG)
        $gpsIfd .= pack('V', 0);

        // Encode 9.5936 = 9 deg, 35 min, 36.96 sec (3696/100)
        $latRationals = pack('VVVVVV', 9, 1, 35, 1, 3696, 100);
        // Encode 122.4542 = 122 deg, 27 min, 15.12 sec (1512/100)
        $lngRationals = pack('VVVVVV', 122, 1, 27, 1, 1512, 100);

        $tiffFull = $tiff.$ifd0.$make.$model.$gpsIfd.$latRationals.$lngRationals;
        $app1Body = "Exif\x00\x00".$tiffFull;
        $app1Segment = "\xFF\xE1".pack('n', strlen($app1Body) + 2).$app1Body;

        return "\xFF\xD8".$app1Segment."\xFF\xD9";
    }

    /**
     * Build a minimal authentic MP4 binary with ftyp + optional mvhd duration + ©xyz ISO 6709 GPS box.
     */
    private function buildMinimalMp4Binary(bool $withGps = false): string
    {
        $ftyp = pack('N', 20).'ftypisom'.pack('N', 512).'isommp41';

        // Build mvhd v0 atom with timescale = 1000, duration = 12500 (12.5 seconds)
        $mvhdPayload = "\x00\x00\x00\x00" // version + flags
            .pack('N', 0) // creation time
            .pack('N', 0) // mod time
            .pack('N', 1000) // timescale
            .pack('N', 12500) // duration (12.5s)
            .str_repeat("\x00", 80);
        $mvhd = pack('N', 8 + strlen($mvhdPayload)).'mvhd'.$mvhdPayload;

        $gpsBox = '';
        if ($withGps) {
            $iso6709 = '+09.5936+122.4542/';
            $gpsBox = pack('N', 12 + strlen($iso6709))."\xA9xyz".pack('n', strlen($iso6709))."\x15\xC7".$iso6709;
        }

        return $ftyp.$mvhd.$gpsBox;
    }

    /** 1. Citizen photo upload */
    public function test_citizen_photo_upload(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $photo = UploadedFile::fake()->createWithContent('flood_scene.jpg', $this->buildMinimalJpegBinary());

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'caller_phone' => '09171234567',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
            'attachments' => [$photo],
        ]);

        $response->assertSessionHasNoErrors();
        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $media = MediaAttachment::where('incident_id', $incident->id)->first();

        $this->assertNotNull($media);
        $this->assertEquals('photo', $media->media_type);
        $this->assertEquals('private', $media->disk);
        $this->assertEquals('citizen_portal', $media->source_channel);
        Storage::disk('private')->assertExists($media->file_path);
    }

    /** 2. Citizen video upload */
    public function test_citizen_video_upload(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $video = UploadedFile::fake()->createWithContent('storm_surge.mp4', $this->buildMinimalMp4Binary(false));

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'caller_phone' => '09171234567',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
            'attachments' => [$video],
        ]);

        $response->assertSessionHasNoErrors();
        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $media = MediaAttachment::where('incident_id', $incident->id)->first();

        $this->assertNotNull($media);
        $this->assertEquals('video', $media->media_type);
        $this->assertEquals(12.50, (float) $media->duration_seconds);
        Storage::disk('private')->assertExists($media->file_path);
    }

    /** 3. Personnel photo upload */
    public function test_personnel_photo_upload(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $office = $this->createOffice('PERS');
        $personnel = $this->createPersonnel($office);
        $incident = $this->createIncident($this->createUser('citizen'), 'dispatched');

        $conv = Conversation::create(['type' => 'incident', 'incident_id' => $incident->id, 'title' => 'Field Chat', 'created_by' => $admin->id]);
        $conv->participants()->createMany([['user_id' => $admin->id], ['user_id' => $personnel->id]]);

        $photo = UploadedFile::fake()->createWithContent('scene_rescue.jpg', $this->buildMinimalJpegBinary());

        $this->actingAs($personnel)->post(route('personnel.messages.send', $conv->id), [
            'body' => 'On scene arrival confirmation.',
            'attachment' => $photo,
            'incident_id' => $incident->id,
        ])->assertRedirect();

        $media = MediaAttachment::where('uploader_id', $personnel->id)->latest('id')->first();
        $this->assertNotNull($media);
        $this->assertEquals('photo', $media->media_type);
        $this->assertEquals('internal_messenger_personnel', $media->source_channel);
        $this->assertEquals('personnel', $media->uploader_account_type);
        $this->assertEquals($conv->id, $media->conversation_id);
        $this->assertEquals($incident->id, $media->incident_id);
    }

    /** 4. Personnel video upload */
    public function test_personnel_video_upload(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $office = $this->createOffice('PVID');
        $personnel = $this->createPersonnel($office);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $admin->id]);
        $conv->participants()->createMany([['user_id' => $admin->id], ['user_id' => $personnel->id]]);

        $video = UploadedFile::fake()->createWithContent('river_level.mp4', $this->buildMinimalMp4Binary(false));

        $this->actingAs($personnel)->post(route('personnel.messages.send', $conv->id), [
            'body' => 'Flood current assessment video.',
            'attachment' => $video,
        ])->assertRedirect();

        $media = MediaAttachment::where('uploader_id', $personnel->id)->latest('id')->first();
        $this->assertNotNull($media);
        $this->assertEquals('video', $media->media_type);
        Storage::disk('private')->assertExists($media->file_path);
    }

    /** 5. Office photo upload */
    public function test_office_photo_upload(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $office = $this->createOffice('OFPH');
        $officeUser = $this->createOfficeUser($office);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $admin->id]);
        $conv->participants()->createMany([['user_id' => $admin->id], ['user_id' => $officeUser->id]]);

        $photo = UploadedFile::fake()->createWithContent('inspection.jpg', $this->buildMinimalJpegBinary());

        $this->actingAs($officeUser)->post(route('office.messages.send', $conv->id), [
            'body' => 'Agency inspection snapshot.',
            'attachment' => $photo,
        ])->assertRedirect();

        $media = MediaAttachment::where('uploader_id', $officeUser->id)->latest('id')->first();
        $this->assertNotNull($media);
        $this->assertEquals('internal_messenger_office', $media->source_channel);
        $this->assertEquals('office', $media->uploader_account_type);
    }

    /** 6. Office video upload */
    public function test_office_video_upload(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $office = $this->createOffice('OFVD');
        $officeUser = $this->createOfficeUser($office);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $admin->id]);
        $conv->participants()->createMany([['user_id' => $admin->id], ['user_id' => $officeUser->id]]);

        $video = UploadedFile::fake()->createWithContent('bridge_damage.mp4', $this->buildMinimalMp4Binary(false));

        $this->actingAs($officeUser)->post(route('office.messages.send', $conv->id), [
            'body' => 'Structural assessment video.',
            'attachment' => $video,
        ])->assertRedirect();

        $media = MediaAttachment::where('uploader_id', $officeUser->id)->latest('id')->first();
        $this->assertNotNull($media);
        $this->assertEquals('video', $media->media_type);
    }

    /** 7. Admin attachment access & Evidence Viewer / Media Governance Console */
    public function test_admin_attachment_access(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen);

        $photo = UploadedFile::fake()->createWithContent('citizen_evidence.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
            'source_channel' => 'citizen_portal',
        ]);

        // Admin streams, downloads, inspects JSON, views Evidence Viewer & Governance Console
        $this->actingAs($admin)->get(route('media.stream', $media->id))->assertOk();
        $this->actingAs($admin)->get(route('media.download', $media->id))->assertOk();
        $this->actingAs($admin)->getJson(route('admin.media.show', $media->id))
            ->assertOk()
            ->assertJsonPath('integrity_verified', true);
        $this->actingAs($admin)->get(route('admin.incidents.evidence'))->assertOk()->assertSee($incident->reference_number);
        $this->actingAs($admin)->get(route('admin.media.index'))->assertOk()->assertSee('citizen_evidence.jpg');
    }

    /** 8. File extension validation */
    public function test_file_extension_validation(): void
    {
        Storage::fake('private');
        $user = $this->createUser('citizen');
        $maliciousFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $this->expectException(InvalidArgumentException::class);
        app(MediaManagementService::class)->storeUploadedFile($maliciousFile, $user);
    }

    /** 9. MIME type validation */
    public function test_mime_type_validation(): void
    {
        Storage::fake('private');
        $user = $this->createUser('citizen');
        // Spoofed .jpg declaring executable MIME
        $spoofedMimeFile = UploadedFile::fake()->create('payload.jpg', 10, 'application/x-msdownload');

        $this->expectException(InvalidArgumentException::class);
        app(MediaManagementService::class)->storeUploadedFile($spoofedMimeFile, $user);
    }

    /** 10. File content validation */
    public function test_file_content_validation(): void
    {
        Storage::fake('private');
        $user = $this->createUser('citizen');
        // PHP web shell disguised as image/jpeg with .jpg extension
        $phpDisguisedAsJpg = UploadedFile::fake()->createWithContent('innocent.jpg', '<?php system($_GET["cmd"]); ?>');

        $this->expectException(InvalidArgumentException::class);
        app(MediaManagementService::class)->storeUploadedFile($phpDisguisedAsJpg, $user);
    }

    /** 11. Upload size limits */
    public function test_upload_size_limits(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');

        // Configure max photo size = 1 MB
        app(MediaManagementService::class)->updateSettings([
            'max_photo_size_mb' => 1,
            'max_video_size_mb' => 50,
            'max_document_size_mb' => 20,
            'max_upload_count' => 5,
            'allowed_photo_types' => 'jpeg,jpg,png,webp',
            'allowed_video_types' => 'mp4,webm,mov',
            'allowed_document_types' => 'pdf,doc,docx',
            'retention_days' => 365,
        ], $admin);

        // Create a 2MB JPEG payload (> 1MB limit)
        $oversizedJpeg = UploadedFile::fake()->createWithContent(
            'huge.jpg',
            $this->buildMinimalJpegBinary().str_repeat('A', 2 * 1024 * 1024)
        );

        $this->expectException(InvalidArgumentException::class);
        app(MediaManagementService::class)->storeUploadedFile($oversizedJpeg, $citizen);
    }

    /** 12. Multiple attachment uploads */
    public function test_multiple_attachment_uploads(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $files = [
            UploadedFile::fake()->createWithContent('angle1.jpg', $this->buildMinimalJpegBinary()),
            UploadedFile::fake()->createWithContent('angle2.jpg', $this->buildMinimalJpegBinary()),
            UploadedFile::fake()->createWithContent('video1.mp4', $this->buildMinimalMp4Binary(false)),
        ];

        $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'caller_phone' => '09171234567',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
            'attachments' => $files,
        ])->assertSessionHasNoErrors();

        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $this->assertEquals(3, $incident->evidenceRecords()->count());
        $this->assertEquals(3, MediaAttachment::where('incident_id', $incident->id)->count());
    }

    /** 13. EXIF GPS extraction */
    public function test_exif_gps_extraction(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');

        $jpegWithExif = UploadedFile::fake()->createWithContent('camera_exif.jpg', $this->buildJpegWithExifGpsBinary());

        $media = app(MediaManagementService::class)->storeUploadedFile($jpegWithExif, $citizen, [
            'source_channel' => 'citizen_portal',
        ]);

        $geo = $media->geoMetadata;
        $this->assertNotNull($geo);
        $this->assertEquals(MediaGeoMetadata::SOURCE_EXIF_CAPTURE, $geo->location_source);
        $this->assertEquals('exif_embedded', $geo->gps_source);
        $this->assertEquals('Samsung', $geo->camera_make);
        $this->assertEquals('Galaxy', $geo->camera_model);
        $this->assertEqualsWithDelta(9.5936, (float) $geo->latitude, 0.0001);
        $this->assertEqualsWithDelta(122.4542, (float) $geo->longitude, 0.0001);
        $this->assertEqualsWithDelta(9.5936, (float) $geo->exif_latitude, 0.0001);
        $this->assertEqualsWithDelta(122.4542, (float) $geo->exif_longitude, 0.0001);
    }

    /** 14. GPS capture location */
    public function test_gps_capture_location(): void
    {
        Storage::fake('private');
        $personnel = $this->createPersonnel($this->createOffice('GPS'));

        $photo = UploadedFile::fake()->createWithContent('live_snap.jpg', $this->buildMinimalJpegBinary());

        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $personnel, [
            'source_channel' => 'internal_messenger_personnel',
            'geo_context' => [
                'latitude' => 9.60125000,
                'longitude' => 122.46110000,
                'accuracy' => 6.5,
                'location_source' => MediaGeoMetadata::SOURCE_GPS_CAPTURE,
                'is_live_capture' => true,
            ],
        ]);

        $geo = $media->geoMetadata;
        $this->assertEquals(MediaGeoMetadata::SOURCE_GPS_CAPTURE, $geo->location_source);
        $this->assertEquals('device_live_upload', $geo->gps_source);
        $this->assertFalse($geo->is_inherited);
        $this->assertEquals(6.50, (float) $geo->accuracy_meters);
    }

    /** 15. Upload location handling distinguishes gallery capture location vs current upload location */
    public function test_upload_location_handling(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');

        // Gallery photo without EXIF uploaded from user's current upload position
        $galleryPhotoNoExif = UploadedFile::fake()->createWithContent('gallery_pic.jpg', $this->buildMinimalJpegBinary());

        $media = app(MediaManagementService::class)->storeUploadedFile($galleryPhotoNoExif, $citizen, [
            'source_channel' => 'citizen_portal',
            'geo_context' => [
                'latitude' => 9.55550000,
                'longitude' => 122.44440000,
                'accuracy' => 18.0,
                'is_live_capture' => false,
            ],
        ]);

        $geo = $media->geoMetadata;
        $this->assertEquals(MediaGeoMetadata::SOURCE_UPLOAD_LOCATION, $geo->location_source);
        $this->assertNull($geo->exif_latitude);
        $this->assertNull($geo->exif_longitude);
        $this->assertEqualsWithDelta(9.5555, (float) $geo->upload_latitude, 0.0001);
        $this->assertEqualsWithDelta(122.4444, (float) $geo->upload_longitude, 0.0001);
    }

    /** 16. Incident-inherited location & non-overwrite of verified EXIF capture coordinates */
    public function test_incident_inherited_location(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen); // Confirmed location: 9.5855, 122.4820

        // Case A: Photo has NO EXIF and NO live capture -> inherits incident location
        $plainPhoto = UploadedFile::fake()->createWithContent('no_gps.jpg', $this->buildMinimalJpegBinary());
        $inheritedMedia = app(MediaManagementService::class)->storeUploadedFile($plainPhoto, $citizen, [
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
            'geo_context' => ['allow_incident_inherit' => true],
        ]);

        $this->assertEquals(MediaGeoMetadata::SOURCE_INCIDENT_INHERITED, $inheritedMedia->geoMetadata->location_source);
        $this->assertEquals('incident_inherited', $inheritedMedia->geoMetadata->gps_source);
        $this->assertTrue($inheritedMedia->geoMetadata->is_inherited);
        $this->assertEqualsWithDelta(9.5855, (float) $inheritedMedia->geoMetadata->latitude, 0.0001);

        // Case B: Photo HAS genuine EXIF GPS (9.5936, 122.4542) -> NEVER overwritten by incident location (9.5855, 122.4820)!
        $exifPhoto = UploadedFile::fake()->createWithContent('with_exif.jpg', $this->buildJpegWithExifGpsBinary());
        $exifMedia = app(MediaManagementService::class)->storeUploadedFile($exifPhoto, $citizen, [
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
            'geo_context' => ['allow_incident_inherit' => true],
        ]);

        $this->assertEquals(MediaGeoMetadata::SOURCE_EXIF_CAPTURE, $exifMedia->geoMetadata->location_source);
        $this->assertFalse($exifMedia->geoMetadata->is_inherited);
        $this->assertEqualsWithDelta(9.5936, (float) $exifMedia->geoMetadata->latitude, 0.0001);
        $this->assertEqualsWithDelta(9.5855, (float) $exifMedia->geoMetadata->inherited_latitude, 0.0001);
    }

    /** 17. Missing GPS metadata recorded strictly as LOCATION_UNAVAILABLE (never 0,0) */
    public function test_missing_gps_metadata(): void
    {
        Storage::fake('private');
        $personnel = $this->createPersonnel($this->createOffice('NOGPS'));

        $photo = UploadedFile::fake()->createWithContent('no_coords.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $personnel, [
            'source_channel' => 'internal_messenger_personnel',
        ]);

        $geo = $media->geoMetadata;
        $this->assertEquals(MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE, $geo->location_source);
        $this->assertEquals('none', $geo->gps_source);
        $this->assertNull($geo->latitude);
        $this->assertNull($geo->longitude);
        $this->assertFalse($media->hasAvailableCoordinates());
    }

    /** 18. Coordinate accuracy handling */
    public function test_coordinate_accuracy_handling(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $photo = UploadedFile::fake()->createWithContent('acc.jpg', $this->buildMinimalJpegBinary());

        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'geo_context' => [
                'latitude' => 9.5910,
                'longitude' => 122.4510,
                'accuracy' => 14.75,
                'location_source' => MediaGeoMetadata::SOURCE_GPS_CAPTURE,
            ],
        ]);

        $this->assertEquals(14.75, (float) $media->geoMetadata->accuracy_meters);
        $this->assertEquals(14.75, (float) $media->geoMetadata->upload_accuracy_meters);
    }

    /** 19. Video metadata handling (with & without embedded container GPS) */
    public function test_video_metadata_handling(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');

        $mp4WithGps = UploadedFile::fake()->createWithContent('geo_clip.mp4', $this->buildMinimalMp4Binary(true));
        $mediaWithGps = app(MediaManagementService::class)->storeUploadedFile($mp4WithGps, $citizen);

        $this->assertEquals(MediaGeoMetadata::SOURCE_EXIF_CAPTURE, $mediaWithGps->geoMetadata->location_source);
        $this->assertEqualsWithDelta(9.5936, (float) $mediaWithGps->geoMetadata->latitude, 0.0001);
        $this->assertEquals(12.50, (float) $mediaWithGps->duration_seconds);

        $mp4NoGps = UploadedFile::fake()->createWithContent('plain_clip.mp4', $this->buildMinimalMp4Binary(false));
        $mediaNoGps = app(MediaManagementService::class)->storeUploadedFile($mp4NoGps, $citizen);

        $this->assertEquals(MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE, $mediaNoGps->geoMetadata->location_source);
        $this->assertNull($mediaNoGps->geoMetadata->latitude);
    }

    /** 20. Original file preservation & lock against citizen modification after acceptance */
    public function test_original_file_preservation(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen, 'pending_review');

        $rawBinary = $this->buildMinimalJpegBinary();
        $photo = UploadedFile::fake()->createWithContent('evidence_original.jpg', $rawBinary);
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
        ]);

        // Accept incident -> locks all incident evidence
        $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
        ])->assertRedirect();

        $media->refresh();
        $this->assertTrue($media->is_locked_evidence);

        // Citizen attempts to replace or delete accepted evidence -> 403 Forbidden
        $this->actingAs($citizen)
            ->delete(route('citizen.records.evidence.delete', [$incident->reference_number, $media->id]))
            ->assertStatus(403);

        $this->actingAs($citizen)
            ->post(route('citizen.records.evidence.add', $incident->reference_number), [
                'attachment' => UploadedFile::fake()->createWithContent('replacement.jpg', $rawBinary),
            ])
            ->assertStatus(403);

        // Stored binary remains byte-identical to original
        Storage::disk('private')->assertExists($media->file_path);
        $this->assertEquals(hash('sha256', $rawBinary), hash('sha256', Storage::disk('private')->get($media->file_path)));
    }

    /** 21. Thumbnail generation & processing queue transition */
    public function test_thumbnail_generation(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');

        $photo = UploadedFile::fake()->createWithContent('process_me.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'skip_processing' => true,
        ]);

        $this->assertEquals('uploaded', $media->processing_status);

        $job = new ProcessMediaAttachmentJob($media->id);
        app()->call([$job, 'handle']);

        $media->refresh();
        $this->assertEquals('ready', $media->processing_status);
        Storage::disk('private')->assertExists($media->file_path);
    }

    /** 22. Processing queue failures mark 'failed' WITHOUT deleting original evidence */
    public function test_processing_queue_failures(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');

        $photo = UploadedFile::fake()->createWithContent('important_evidence.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'skip_processing' => true,
        ]);

        // Run job with failure simulated
        $failingJob = new ProcessMediaAttachmentJob($media->id, true);
        app()->call([$failingJob, 'handle']);

        $media->refresh();
        $this->assertEquals('failed', $media->processing_status);
        $this->assertNotNull($media->processing_error);
        // Critical: original evidence file must STILL exist!
        Storage::disk('private')->assertExists($media->file_path);

        // Admin retries failed processing -> recovers cleanly to ready
        $this->actingAs($admin)->post(route('admin.media.retry', $media->id))->assertRedirect();
        $media->refresh();
        $this->assertEquals('ready', $media->processing_status);
    }

    /** 23. Unauthorized media access blocked across roles */
    public function test_unauthorized_media_access(): void
    {
        Storage::fake('private');
        $citizenA = $this->createUser('citizen');
        $citizenB = $this->createUser('citizen');
        $officeA = $this->createOffice('OFFA');
        $officeB = $this->createOffice('OFFB');
        $officeUserB = $this->createOfficeUser($officeB);
        $personnel = $this->createPersonnel($officeA);

        $incidentA = $this->createIncident($citizenA);
        $photo = UploadedFile::fake()->createWithContent('private_evidence.jpg', $this->buildMinimalJpegBinary());

        $mediaA = app(MediaManagementService::class)->storeUploadedFile($photo, $citizenA, [
            'incident_id' => $incidentA->id,
            'link_as_evidence' => true,
        ]);

        // Citizen B cannot stream or download Citizen A's media
        $this->actingAs($citizenB)->get(route('media.stream', $mediaA->id))->assertStatus(403);
        $this->actingAs($citizenB)->get(route('media.download', $mediaA->id))->assertStatus(403);

        // Citizen A can stream their own incident media
        $this->actingAs($citizenA)->get(route('media.stream', $mediaA->id))->assertOk();

        // Internal Messenger media uploaded by Personnel cannot be accessed by Citizen
        $internalPhoto = UploadedFile::fake()->createWithContent('internal.jpg', $this->buildMinimalJpegBinary());
        $internalMedia = app(MediaManagementService::class)->storeUploadedFile($internalPhoto, $personnel, [
            'source_channel' => 'internal_messenger_personnel',
        ]);

        $this->actingAs($citizenA)->get(route('media.stream', $internalMedia->id))->assertStatus(403);
        $this->actingAs($officeUserB)->get(route('media.stream', $internalMedia->id))->assertStatus(403);
    }

    /** 24. Media-to-incident polymorphic & direct relationships */
    public function test_media_to_incident_relationships(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen);

        $photo = UploadedFile::fake()->createWithContent('rel.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($photo, $citizen, [
            'attachable' => $incident,
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
        ]);

        $this->assertEquals(Incident::class, $media->attachable_type);
        $this->assertEquals($incident->id, $media->attachable_id);
        $this->assertEquals($incident->id, $media->incident->id);
        $this->assertTrue($incident->mediaAttachments()->where('id', $media->id)->exists());
        $this->assertTrue($incident->evidenceRecords()->where('media_id', $media->id)->exists());
    }

    /** 25. Evidence map marker permissions & role-based scoping */
    public function test_evidence_map_marker_permissions(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');

        $officeA = $this->createOffice('MAPA');
        $officeB = $this->createOffice('MAPB');
        $officeUserA = $this->createOfficeUser($officeA);
        $officeUserB = $this->createOfficeUser($officeB);

        $personnelA = $this->createPersonnel($officeA);
        $incidentA = $this->createIncident($citizen, 'dispatched');

        IncidentAssignment::create([
            'incident_id' => $incidentA->id,
            'office_id' => $officeA->id,
            'personnel_id' => $personnelA->personnelProfile->id,
            'assigned_by' => $admin->id,
            'assignment_type' => 'manual',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $evPhoto = UploadedFile::fake()->createWithContent('map_marker.jpg', $this->buildMinimalJpegBinary());
        $media = app(MediaManagementService::class)->storeUploadedFile($evPhoto, $personnelA, [
            'source_channel' => 'internal_messenger_personnel',
            'incident_id' => $incidentA->id,
            'link_as_evidence' => true,
            'geo_context' => [
                'latitude' => 9.5912,
                'longitude' => 122.4588,
                'location_source' => MediaGeoMetadata::SOURCE_GPS_CAPTURE,
            ],
        ]);

        // Admin map exposes the evidence marker with personnel style
        $adminMap = $this->actingAs($admin)->getJson(route('admin.map.data'))->assertOk()->json();
        $foundAdmin = collect($adminMap['evidence'])->firstWhere('id', $media->id);
        $this->assertNotNull($foundAdmin);
        $this->assertEquals('personnel_evidence', $foundAdmin['marker_style']);
        $this->assertEquals($incidentA->reference_number, $foundAdmin['incident_reference']);

        // Office A (assigned) sees the evidence marker on Office Map
        $officeAMap = $this->actingAs($officeUserA)->getJson(route('office.map.data'))->assertOk()->json();
        $this->assertNotNull(collect($officeAMap['evidence'])->firstWhere('id', $media->id));

        // Office B (unassigned & not responsible) does NOT see Office A's private evidence marker
        $officeBMap = $this->actingAs($officeUserB)->getJson(route('office.map.data'))->assertOk()->json();
        $this->assertNull(collect($officeBMap['evidence'])->firstWhere('id', $media->id));

        // Personnel A (assigned) sees the evidence marker on Personnel Map
        $personnelMap = $this->actingAs($personnelA)->getJson(route('personnel.map.data'))->assertOk()->json();
        $this->assertNotNull(collect($personnelMap['evidence'])->firstWhere('id', $media->id));
    }

    /** 26. SHA-256 integrity verification & audited archival retention lifecycle */
    public function test_sha256_integrity_verification(): void
    {
        Storage::fake('private');
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen, 'resolved');

        $raw = $this->buildMinimalJpegBinary();
        $photo = UploadedFile::fake()->createWithContent('hash_check.jpg', $raw);
        $mediaService = app(MediaManagementService::class);
        $media = $mediaService->storeUploadedFile($photo, $citizen, ['incident_id' => $incident->id]);

        $this->assertEquals(hash('sha256', $raw), $media->checksum_sha256);
        $this->assertTrue($mediaService->verifyFileIntegrity($media));

        // Simulate file tampering on storage
        Storage::disk('private')->put($media->file_path, 'tampered_bytes');
        $this->assertFalse($mediaService->verifyFileIntegrity($media));

        // Audited archival & retention purge flow
        $this->actingAs($admin)->post(route('admin.media.archive', $media->id), [
            'reason' => 'Case resolved and moved to cold storage archive.',
        ])->assertRedirect();

        $media->refresh();
        $this->assertTrue($media->is_archived);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'media:archived',
            'auditable_id' => $media->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.media.purge', $media->id), [
            'justification' => 'Formal retention window disposal authorized by EOC.',
        ])->assertRedirect();

        $this->assertSoftDeleted('media_attachments', ['id' => $media->id]);
        $this->assertNotNull(AuditLog::where('event_type', 'media:retention_purged')->where('auditable_id', $media->id)->first());
    }
}
