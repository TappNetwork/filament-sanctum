<?php

namespace Devtical\Sanctum;

use Devtical\Sanctum\Pages\Sanctum;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SanctumPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-sanctum';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                $this->getPage(),
            ]);
    }

    /**
     * @return class-string<Sanctum>
     */
    protected function getPage(): string
    {
        return Sanctum::class;
    }

    public function boot(Panel $panel): void
    {
        $page = $this->getPage();

        if ($page::setting('navigation.user_menu.enabled')) {
            $panel->userMenuItems([
                Action::make('sanctum')
                    ->label(fn (): string => $page::getNavigationLabel())
                    ->url(fn (): string => $page::getUrl(panel: $panel->getId()))
                    ->icon(fn (): string => $page::getNavigationIcon())
                    ->visible(fn (): bool => $page::canAccess()),
            ]);
        }
    }
}
