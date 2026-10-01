<?php

namespace Tests\Feature;

use App\Models\FarmerImportBatch;
use App\Services\FarmerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FarmerImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_commit_passes_the_staged_batch_to_the_batch_commit_service(): void
    {
        $storedPath = tempnam(sys_get_temp_dir(), 'farmer-import-');
        file_put_contents($storedPath, "first_name,last_name,barangay\nAna,Cruz,Baluarte\n");

        $batch = FarmerImportBatch::create([
            'uuid' => 'f6bd356e-40a3-4c34-b54b-01f3284e80de',
            'original_filename' => 'farmers.csv',
            'stored_path' => $storedPath,
            'status' => 'previewed',
        ]);

        $service = Mockery::mock(FarmerImportService::class);
        $service->shouldReceive('commitBatch')
            ->once()
            ->with(Mockery::type(FarmerImportBatch::class))
            ->andReturn([
                'createdUsers' => 1,
                'createdFarms' => 1,
                'skippedFarms' => 0,
            ]);
        $this->app->instance(FarmerImportService::class, $service);

        $response = $this->withoutMiddleware()->post(route('admin.farmers.import.commit'), [
            'batch_uuid' => $batch->uuid,
        ]);

        $response->assertRedirect(route('admin.farmers.credentials'));
        $this->assertSame('committed', $batch->fresh()->status);
        $this->assertFileDoesNotExist($storedPath);
    }
}
