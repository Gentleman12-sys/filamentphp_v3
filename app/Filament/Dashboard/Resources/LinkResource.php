<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\LinkResource\Pages;
use App\Filament\Dashboard\Resources\LinkResource\RelationManagers\ClicksRelationManager;
use App\Models\Link;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $modelLabel = 'Ссылка';

    protected static ?string $pluralModelLabel = 'Ссылки';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('original_url')
                    ->label('Оригинальный URL')
                    ->url()
                    ->required()
                    ->maxLength(2048),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Код'),
                TextColumn::make('short_url')
                    ->label('Короткая ссылка')
                    ->copyable()
                    ->copyMessage('Скопировано!'),
                TextColumn::make('original_url')
                    ->label('Оригинальный URL')
                    ->limit(50),
                TextColumn::make('clicks_count')
                    ->label('Переходы')
                    ->counts('clicks'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ClicksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'view' => Pages\ViewLink::route('/{record}'),
        ];
    }
}
