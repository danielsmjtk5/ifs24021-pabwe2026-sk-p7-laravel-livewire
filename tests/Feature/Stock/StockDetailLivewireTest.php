<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_stock_detail(): void
    {
        $stock = Stock::factory()->create();

        $this->get(route('stocks.show', $stock->id))
            ->assertRedirect(route('login'));
    }

    public function test_member_cannot_access_other_member_stock(): void
    {
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember)
            ->get(route('stocks.show', $stock->id))
            ->assertStatus(403);
    }

    public function test_member_can_view_own_stock_detail_page(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kabel LAN Cat6',
            'sku' => 'NET-CAT6',
            'description' => '# Deskripsi Markdown',
            'quantity' => 12,
            'unit' => 'roll',
            'price' => 1250000,
            'min_stock' => 2,
            'status' => 'available',
        ]);

        $this->actingAs($user)
            ->get(route('stocks.show', $stock->id))
            ->assertOk()
            ->assertSee('Kabel LAN Cat6')
            ->assertSee('NET-CAT6')
            ->assertSee('Penyesuaian Stok');

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertOk()
            ->assertSet('name', 'Kabel LAN Cat6')
            ->assertSet('sku', 'NET-CAT6')
            ->assertSet('description', '# Deskripsi Markdown')
            ->assertSet('quantity', 12)
            ->assertSet('unit', 'roll')
            ->assertSet('minStock', 2)
            ->assertSet('status', '')
            ->assertViewHas('canManage', true);
    }

    public function test_detail_keeps_manual_status_in_form(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'quantity' => 50, 'min_stock' => 5, 'status' => 'out_of_stock', 'description' => null]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertSet('status', 'out_of_stock')
            ->assertSet('description', '')
            ->assertSee('Belum ada deskripsi untuk barang ini');
    }

    public function test_admin_can_view_member_stock_detail_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id, 'name' => 'Barang Member', 'quantity' => 10]);

        $this->actingAs($admin)
            ->get(route('stocks.show', $stock->id))
            ->assertOk()
            ->assertSee('Barang Member')
            ->assertSee('Mode Lihat Saja (Read-Only)')
            ->assertDontSee('Penyesuaian Stok');

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertViewHas('canManage', false)
            ->call('openEditModal')
            ->assertForbidden();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('increaseQuantity')
            ->assertForbidden();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('saveStock')
            ->assertForbidden();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('deleteStock')
            ->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 10]);
    }

    public function test_open_and_close_edit_modal(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'name' => 'Nama Detail', 'description' => 'Deskripsi lama']);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertSet('showEditModal', false)
            ->set('name', 'Diubah sementara')
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('name', 'Nama Detail')
            ->assertSet('description', 'Deskripsi lama')
            ->call('closeEditModal')
            ->assertSet('showEditModal', false);
    }

    public function test_update_stock_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'name' => 'Nama Awal', 'quantity' => 30, 'min_stock' => 5]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('openEditModal')
            ->set('name', 'a')
            ->set('quantity', -5)
            ->set('unit', '')
            ->set('price', -1)
            ->set('minStock', 'abc')
            ->set('status', 'invalid_status')
            ->call('saveStock')
            ->assertHasErrors(['name', 'quantity', 'unit', 'price', 'minStock', 'status']);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('openEditModal')
            ->set('name', 'Nama Diperbarui')
            ->set('sku', 'SKU-BARU')
            ->set('description', '## Spesifikasi Baru')
            ->set('quantity', 4)
            ->set('unit', 'pack')
            ->set('price', '7500.5')
            ->set('minStock', 5)
            ->set('status', '')
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false)
            ->assertSet('name', 'Nama Diperbarui');

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'name' => 'Nama Diperbarui',
            'sku' => 'SKU-BARU',
            'description' => '## Spesifikasi Baru',
            'quantity' => 4,
            'unit' => 'pack',
            'status' => 'low_stock',
        ]);
        $this->assertSame('7500.50', $stock->fresh()->price);
    }

    public function test_increase_and_decrease_quantity(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'quantity' => 5, 'min_stock' => 5, 'status' => 'low_stock']);

        $this->actingAs($user);

        $component = Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('increaseQuantity')
            ->assertSet('quantity', 6);
        $this->assertSame('available', $stock->fresh()->status);

        $component->set('adjustAmount', 10)
            ->call('decreaseQuantity')
            ->assertSet('quantity', 0);
        $this->assertSame('out_of_stock', $stock->fresh()->status);

        $component->set('adjustAmount', 0)
            ->call('increaseQuantity')
            ->assertHasErrors(['adjustAmount']);
        $this->assertSame(0, $stock->fresh()->quantity);
    }

    public function test_cover_photo_upload_and_delete(): void
    {
        Storage::fake('public');

        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'cover_image_path' => null]);

        $this->actingAs($user);

        // Invalid file
        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->set('coverPhoto', UploadedFile::fake()->create('document.pdf', 100))
            ->assertHasErrors(['coverPhoto']);

        // Valid image upload
        $component = Livewire::test(StockDetail::class, ['stock' => $stock])
            ->set('coverPhoto', UploadedFile::fake()->image('cover.jpg', 800, 400))
            ->assertHasNoErrors()
            ->assertSee('Ganti Cover');

        $stock->refresh();
        $this->assertNotNull($stock->cover_image_path);
        Storage::disk('public')->assertExists($stock->cover_image_path);

        // Delete cover photo
        $component->call('deleteCoverPhoto');

        $stock->refresh();
        $this->assertNull($stock->cover_image_path);
    }

    public function test_delete_stock_redirects(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('deleteStock')
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_confirm_and_cancel_modal_actions(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertSet('confirmingStockDeletion', false)
            ->call('confirmDeleteStock')
            ->assertSet('confirmingStockDeletion', true)
            ->call('cancelDeleteStock')
            ->assertSet('confirmingStockDeletion', false)
            ->assertSet('confirmingCoverDeletion', false)
            ->call('confirmDeleteCover')
            ->assertSet('confirmingCoverDeletion', true)
            ->call('cancelDeleteCover')
            ->assertSet('confirmingCoverDeletion', false);
    }
}
