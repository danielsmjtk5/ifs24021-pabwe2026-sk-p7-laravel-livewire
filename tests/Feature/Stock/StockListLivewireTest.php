<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_stocks(): void
    {
        $this->get('/stocks')->assertRedirect(route('login'));
    }

    public function test_member_can_view_own_stocks_only(): void
    {
        $user = User::factory()->member()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Barang Milik Member']);

        $otherUser = User::factory()->member()->create();
        Stock::factory()->create(['user_id' => $otherUser->id, 'name' => 'Barang Rahasia Orang Lain']);

        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->assertOk()
            ->assertSee('Barang Milik Member')
            ->assertDontSee('Barang Rahasia Orang Lain')
            ->assertViewHas('summary', fn (array $summary) => $summary['total_items'] === 1);
    }

    public function test_empty_state_is_rendered(): void
    {
        $this->actingAs(User::factory()->member()->create());

        Livewire::test(StockList::class)
            ->assertSee('Belum ada barang ditemukan');
    }

    public function test_create_stock_validation_errors(): void
    {
        $this->actingAs(User::factory()->member()->create());

        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->assertSet('showFormModal', true)
            ->set('formName', '')
            ->set('formQuantity', -1)
            ->set('formUnit', '')
            ->set('formPrice', 'abc')
            ->set('formMinStock', 'x')
            ->set('formStatus', 'invalid_status')
            ->call('saveStock')
            ->assertHasErrors(['formName', 'formQuantity', 'formUnit', 'formPrice', 'formMinStock', 'formStatus']);
    }

    public function test_create_stock_with_automatic_status(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('formName', 'Kertas HVS A4')
            ->set('formSku', 'ATK-HVS')
            ->set('formDescription', '**Kertas** 80gsm')
            ->set('formQuantity', 3)
            ->set('formUnit', 'rim')
            ->set('formPrice', '52000')
            ->set('formMinStock', 5)
            ->set('formStatus', '')
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Kertas HVS A4',
            'sku' => 'ATK-HVS',
            'quantity' => 3,
            'unit' => 'rim',
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);
    }

    public function test_create_stock_with_explicit_status(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('formName', 'Barang Diblokir')
            ->set('formQuantity', 50)
            ->set('formStatus', 'out_of_stock')
            ->call('saveStock')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Barang Diblokir',
            'status' => 'out_of_stock',
            'price' => null,
        ]);
    }

    public function test_edit_stock_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Nama Lama',
            'quantity' => 20,
            'min_stock' => 5,
            'status' => 'available',
        ]);

        Livewire::test(StockList::class)
            ->call('openEditModal', $stock->id)
            ->assertSet('editingStockId', $stock->id)
            ->assertSet('formName', 'Nama Lama')
            ->assertSet('formQuantity', 20)
            ->assertSet('formStatus', '')
            ->set('formName', 'Nama Baru')
            ->set('formQuantity', 0)
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'name' => 'Nama Baru',
            'quantity' => 0,
            'status' => 'out_of_stock',
        ]);
    }

    public function test_edit_modal_keeps_manual_status(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'quantity' => 100,
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);

        Livewire::test(StockList::class)
            ->call('openEditModal', $stock->id)
            ->assertSet('formStatus', 'low_stock');
    }

    public function test_member_cannot_edit_or_adjust_or_delete_other_member_stock(): void
    {
        $user = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $other->id, 'quantity' => 10]);

        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->call('openEditModal', $stock->id)
            ->assertForbidden();

        Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, 1)
            ->assertForbidden();

        Livewire::test(StockList::class)
            ->call('deleteStock', $stock->id)
            ->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 10]);
    }

    public function test_adjust_quantity_syncs_status(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'quantity' => 6,
            'min_stock' => 5,
            'status' => 'available',
        ]);

        $component = Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, -1);
        $this->assertSame(5, $stock->fresh()->quantity);
        $this->assertSame('low_stock', $stock->fresh()->status);

        $component->call('adjustQuantity', $stock->id, -10);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame('out_of_stock', $stock->fresh()->status);

        $component->call('adjustQuantity', $stock->id, 10);
        $this->assertSame(10, $stock->fresh()->quantity);
        $this->assertSame('available', $stock->fresh()->status);
    }

    public function test_delete_stock_directly(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create(['user_id' => $user->id]);

        Livewire::test(StockList::class)
            ->call('deleteStock', $stock->id);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_confirm_cancel_and_delete_stock(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create(['user_id' => $user->id]);

        Livewire::test(StockList::class)
            ->assertSet('confirmingStockDeletionId', null)
            ->call('deleteStock') // no target → no-op
            ->call('confirmDelete', $stock->id)
            ->assertSet('confirmingStockDeletionId', $stock->id)
            ->call('cancelDelete')
            ->assertSet('confirmingStockDeletionId', null)
            ->call('confirmDelete', $stock->id)
            ->call('deleteStock')
            ->assertSet('confirmingStockDeletionId', null);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_search_and_status_filter(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Pulpen Hitam', 'sku' => 'PEN-001', 'status' => 'available']);
        Stock::factory()->outOfStock()->create(['user_id' => $user->id, 'name' => 'Spidol Merah', 'sku' => 'SPD-002']);

        Livewire::test(StockList::class)
            ->set('search', 'Pulpen')
            ->assertSee('Pulpen Hitam')
            ->assertDontSee('Spidol Merah')
            ->set('search', 'SPD-002')
            ->assertSee('Spidol Merah')
            ->assertDontSee('Pulpen Hitam')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Spidol Merah')
            ->assertDontSee('Pulpen Hitam');
    }

    public function test_export_excel(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Exportable Stock']);

        Livewire::test(StockList::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_import_excel_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->call('openImportModal')
            ->call('importExcel')
            ->assertHasErrors(['importFile']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No', 'Name', 'SKU', 'Description', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status'],
            [1, 'Imported Excel Stock', 'IMP-001', 'Excel Desc', 25, 'box', 15000, 5, ''],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $fileContent = file_get_contents($tempPath);
        @unlink($tempPath);

        $uploadedFile = UploadedFile::fake()->createWithContent('stocks.xlsx', $fileContent);

        Livewire::test(StockList::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->set('importFile', $uploadedFile)
            ->call('importExcel')
            ->assertHasNoErrors()
            ->assertSet('showImportModal', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Imported Excel Stock',
            'sku' => 'IMP-001',
            'quantity' => 25,
            'unit' => 'box',
            'status' => 'available',
        ]);
    }

    public function test_ai_generation_preview_select_and_save(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $aiClient = $this->createStub(AiClientInterface::class);
        $aiClient->method('generateStocks')->willReturn([
            new GeneratedStockItem('Kertas A4', 'ATK-A4', 'Kertas 80gsm', 40, 'rim', 52000.0, 10, 'available'),
            new GeneratedStockItem('Tinta Hitam', null, null, 2, 'botol', null, 5, 'low_stock'),
        ]);
        $this->app->instance(AiClientInterface::class, $aiClient);

        // Validation for prompt and count
        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', '')
            ->set('aiCount', 0)
            ->call('generateWithAi')
            ->assertHasErrors(['aiPrompt', 'aiCount']);

        $component = Livewire::test(StockList::class)
            ->call('openAiModal')
            ->assertSet('showAiModal', true)
            ->assertSet('aiCount', 3)
            ->set('aiPrompt', 'Perlengkapan ATK kantor')
            ->set('aiCount', 2)
            ->call('generateWithAi')
            ->assertSet('aiIsGenerating', false)
            ->assertCount('aiGeneratedItems', 2)
            ->assertCount('aiSelectedIndices', 2)
            ->assertSee('Kertas A4');

        // Saving with nothing selected shows an error
        $component->set('aiSelectedIndices', [])
            ->call('saveAiGenerated')
            ->assertSet('aiErrorMessage', 'Pilih minimal satu item stok yang ingin disimpan.');

        // Save only the first item (plus an out-of-range index which is ignored)
        $component->set('aiSelectedIndices', [0, 99])
            ->call('saveAiGenerated')
            ->assertSet('showAiModal', false);

        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Kertas A4', 'quantity' => 40, 'status' => 'available']);
        $this->assertDatabaseMissing('stocks', ['user_id' => $user->id, 'name' => 'Tinta Hitam']);
    }

    public function test_ai_generation_handles_exception(): void
    {
        $this->actingAs(User::factory()->member()->create());

        $aiClient = $this->createStub(AiClientInterface::class);
        $aiClient->method('generateStocks')->willThrowException(new \RuntimeException('API quota exceeded'));
        $this->app->instance(AiClientInterface::class, $aiClient);

        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', 'Error test')
            ->call('generateWithAi')
            ->assertSet('aiErrorMessage', 'API quota exceeded')
            ->assertSet('aiIsGenerating', false);
    }

    public function test_close_modals(): void
    {
        $this->actingAs(User::factory()->member()->create());

        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->call('closeFormModal')
            ->assertSet('showFormModal', false)
            ->call('openImportModal')
            ->call('closeImportModal')
            ->assertSet('showImportModal', false)
            ->call('openAiModal')
            ->call('closeAiModal')
            ->assertSet('showAiModal', false);
    }
}
