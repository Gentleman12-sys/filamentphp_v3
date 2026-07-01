<?php

namespace Tests\Feature;

use App\Filament\Dashboard\Resources\LinkResource\Pages\CreateLink;
use App\Filament\Dashboard\Resources\LinkResource\Pages\ListLinks;
use App\Filament\Dashboard\Resources\LinkResource\Pages\ViewLink;
use App\Filament\Dashboard\Resources\LinkResource\RelationManagers\ClicksRelationManager;
use App\Models\Link;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UrlShortenerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    }

    public function test_registration_page_renders(): void
    {
        $this->get('/dashboard/register')->assertSuccessful();
    }

    public function test_user_can_create_a_link_and_it_is_scoped_to_them(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner);

        Livewire::test(CreateLink::class)
            ->fillForm(['original_url' => 'https://example.com/test-page'])
            ->call('create')
            ->assertHasNoFormErrors();

        $link = Link::first();

        $this->assertNotNull($link);
        $this->assertSame($owner->id, $link->user_id);
        $this->assertSame(6, strlen($link->code));
        $this->assertSame('https://example.com/test-page', $link->original_url);

        Livewire::actingAs($owner)
            ->test(ListLinks::class)
            ->assertCanSeeTableRecords([$link]);

        $this->actingAs($other);
        Livewire::actingAs($other)
            ->test(ListLinks::class)
            ->assertCanNotSeeTableRecords([$link]);
    }

    public function test_short_link_redirects_and_records_a_click(): void
    {
        $owner = User::factory()->create();
        $link = Link::create([
            'user_id' => $owner->id,
            'original_url' => 'https://example.com/test-page',
        ]);

        $response = $this->get('/'.$link->code, ['REMOTE_ADDR' => '203.0.113.7']);

        $response->assertRedirect('https://example.com/test-page');

        $this->assertSame(1, $link->clicks()->count());
        $this->assertSame('203.0.113.7', $link->clicks()->first()->ip_address);
    }

    public function test_view_page_shows_click_stats(): void
    {
        $owner = User::factory()->create();
        $link = Link::create([
            'user_id' => $owner->id,
            'original_url' => 'https://example.com/test-page',
        ]);
        $link->clicks()->create(['ip_address' => '198.51.100.9']);

        $this->actingAs($owner);

        Livewire::test(ViewLink::class, ['record' => $link->getRouteKey()])
            ->assertOk();

        Livewire::test(ClicksRelationManager::class, [
            'ownerRecord' => $link,
            'pageClass' => ViewLink::class,
        ])->assertCanSeeTableRecords($link->clicks);
    }

    public function test_user_can_delete_their_link(): void
    {
        $owner = User::factory()->create();
        $link = Link::create([
            'user_id' => $owner->id,
            'original_url' => 'https://example.com/test-page',
        ]);

        $this->actingAs($owner);

        Livewire::test(ListLinks::class)
            ->callTableAction('delete', $link);

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_another_user_cannot_view_someone_elses_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = Link::create([
            'user_id' => $owner->id,
            'original_url' => 'https://example.com/test-page',
        ]);

        $this->actingAs($other);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ViewLink::class, ['record' => $link->getRouteKey()]);
    }
}
