<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Models\Link;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLink extends ViewRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(fn () => LinkResource::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('original_url')
                    ->label('Оригинальный URL'),
                TextEntry::make('short_url')
                    ->label('Короткая ссылка')
                    ->copyable()
                    ->copyMessage('Скопировано!'),
                TextEntry::make('code')
                    ->label('Код'),
                TextEntry::make('created_at')
                    ->label('Создано')
                    ->dateTime(),
                TextEntry::make('clicks_count')
                    ->label('Всего переходов')
                    ->state(fn (Link $record) => $record->clicks()->count()),
            ]);
    }
}
