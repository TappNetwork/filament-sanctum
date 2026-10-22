<?php

namespace Devtical\Sanctum\Tests\Feature;

use Devtical\Sanctum\Pages\Sanctum;
use Devtical\Sanctum\SanctumPlugin;
use Devtical\Sanctum\Tests\TestbenchTestCase;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Mockery;
use ReflectionMethod;

class ExtensionPointsTest extends TestbenchTestCase
{
    public function test_abilities_field_offers_select_all(): void
    {
        $field = $this->abilitiesField(new Sanctum);

        $this->assertTrue($field->isBulkToggleable());
    }

    public function test_a_subclass_can_supply_the_ability_options(): void
    {
        $page = new CustomAbilitiesPage;

        $this->assertSame(['deploy' => 'Deploy the app'], $this->abilitiesField($page)->getOptions());

        $label = new ReflectionMethod($page, 'formatAbilityLabel');
        $this->assertSame('Deploy the app', $label->invoke($page, 'deploy'));
        $this->assertSame('users:read', $label->invoke($page, 'users:read'));
    }

    public function test_a_plugin_subclass_can_register_its_own_page(): void
    {
        $panel = Panel::make()->id('custom')->path('custom');

        (new CustomPagePlugin)->register($panel);

        $this->assertContains(CustomAbilitiesPage::class, $panel->getPages());
        $this->assertNotContains(Sanctum::class, $panel->getPages());
    }

    public function test_user_menu_item_is_hidden_from_users_the_gate_refuses(): void
    {
        config()->set('filament-sanctum.navigation.user_menu.enabled', true);
        config()->set('filament-sanctum.authorization.enabled', true);
        config()->set('filament-sanctum.authorization.gate', 'manage-tokens');
        Gate::define('manage-tokens', fn ($user = null): bool => false);

        $captured = [];
        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('getId')->andReturn('admin');
        $panel->shouldReceive('userMenuItems')->once()->andReturnUsing(function (array $items) use (&$captured, $panel) {
            $captured = $items;

            return $panel;
        });

        SanctumPlugin::make()->boot($panel);

        $items = collect($captured)
            ->filter(fn ($item): bool => $item instanceof Action && $item->getName() === 'sanctum');

        $this->assertCount(1, $items);
        $this->assertFalse($items->first()->isVisible());
    }

    private function abilitiesField(Sanctum $page): CheckboxList
    {
        $schema = (new ReflectionMethod($page, 'getCreateTokenFormSchema'))->invoke($page);

        return collect($schema)->first(fn ($component): bool => $component instanceof CheckboxList);
    }
}

class CustomAbilitiesPage extends Sanctum
{
    protected function getAbilityOptions(): array
    {
        return ['deploy' => 'Deploy the app'];
    }
}

class CustomPagePlugin extends SanctumPlugin
{
    protected function getPage(): string
    {
        return CustomAbilitiesPage::class;
    }
}
