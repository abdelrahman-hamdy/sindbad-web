<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TechnicianHolidaysRelationManager extends RelationManager
{
    protected static string $relationship = 'holidays';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Holidays / Unavailability');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isTechnician();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label(__('End Date'))
                ->required()
                ->afterOrEqual('start_date'),
            Forms\Components\TextInput::make('reason')
                ->label(__('Reason'))
                ->nullable()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('End'))
                    ->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->default('-')
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading(__('No holidays recorded'));
    }
}
