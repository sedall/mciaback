<?php

namespace Modules\CustomerDocuments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Access\Database\Seeders\RoleSeeder;

class CustomerDocumentsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'mobile' => fake()->unique()->numerify('09#########'),
            'password' => bcrypt('password'),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }
    public function test_customer_can_upload_document(): void
    {
        Storage::fake('public');

        $customer = $this->createUserWithRole('customer');

        $response = $this->actingAs($customer, 'sanctum')
            ->post('/api/customer/documents', [
                'type' => 'national_card_front',
                'file' => UploadedFile::fake()->image('card.jpg'),
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('customer_documents', [
            'user_id' => $customer->id,
            'type' => 'national_card_front',
            'status' => 'pending',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'file_path',
                'file_url',
                'status',
                'rejection_reason',
                'reviewed_by',
                'reviewed_at',
                'created_at',
            ]
        ]);
    }

    public function test_customer_cannot_upload_duplicate_document_type(): void
    {
        Storage::fake('public');

        $customer = $this->createUserWithRole('customer');

        CustomerDocument::create([
            'user_id' => $customer->id,
            'type' => 'national_card_front',
            'file_path' => 'customer-documents/existing.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->post('/api/customer/documents', [
                'type' => 'national_card_front',
                'file' => UploadedFile::fake()->image('card.jpg'),
            ]);

        // اگر کنترلر duplicate را هندل نکند ممکن است 500/SQL exception بدهد
        // حالت مطلوب 422 یا 409 است
        $response->assertStatus(422);
    }

    public function test_admin_can_approve_pending_document(): void
    {
        $admin = $this->createUserWithRole('admin');
        $customer = $this->createUserWithRole('customer');

        $document = CustomerDocument::create([
            'user_id' => $customer->id,
            'type' => 'national_card_front',
            'file_path' => 'customer-documents/test.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patch("/api/admin/documents/{$document->id}/approve");

        $response->assertOk();

        $this->assertDatabaseHas('customer_documents', [
            'id' => $document->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_pending_document_with_reason(): void
    {
        $admin = $this->createUserWithRole('admin');
        $customer = $this->createUserWithRole('customer');

        $document = CustomerDocument::create([
            'user_id' => $customer->id,
            'type' => 'national_card_front',
            'file_path' => 'customer-documents/test.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patch("/api/admin/documents/{$document->id}/reject", [
                'rejection_reason' => 'تصویر ناخواناست',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('customer_documents', [
            'id' => $document->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'rejection_reason' => 'تصویر ناخواناست',
        ]);
    }
}
